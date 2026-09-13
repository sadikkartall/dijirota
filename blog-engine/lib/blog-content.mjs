import { marked } from 'marked';
import sanitizeHtml from 'sanitize-html';
import { createHash } from 'node:crypto';
import { readdir, readFile } from 'node:fs/promises';
import path from 'node:path';

export const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
export function slugify(value) {
  return String(value).toLocaleLowerCase('tr').replace(/ı/g, 'i').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 80).replace(/-$/, '') || 'yazi';
}
export function safeUrl(value) {
  try { const u = new URL(value); return ['https:', 'http:'].includes(u.protocol) && !u.username && !u.password ? u.href : ''; } catch { return ''; }
}
export function renderMarkdown(value) {
  return sanitizeHtml(marked.parse(String(value), {gfm: true}), {
    allowedTags: ['p','h2','h3','h4','h5','h6','ul','ol','li','blockquote','pre','code','strong','em','del','a','br','hr','table','thead','tbody','tr','th','td'],
    allowedAttributes: {a:['href','title','rel'], code:['class'], ol:['start']},
    allowedSchemes: ['https','http'], allowProtocolRelative: false,
    transformTags: {h1:'h2', a: (tag, attrs) => ({tagName:'a', attribs:{href:safeUrl(attrs.href), rel:'noopener noreferrer'}})},
  });
}
export const readMinutes = body => Math.max(1, Math.ceil(String(body).split(/\s+/).length / 200));
export function editable(input) {
  const limits = {title:160, slug:80, description:320, body:80000, coverAlt:300, coverPrompt:2500};
  const data = {};
  for (const [key, max] of Object.entries(limits)) {
    if (typeof input[key] !== 'string' || input[key].length > max) throw new Error(`${key} alanı geçersiz veya çok uzun.`);
    data[key] = input[key].trim();
  }
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(data.slug)) throw new Error('Adres yalnızca küçük harf, rakam ve tire içerebilir.');
  if (!Array.isArray(input.tags) || input.tags.length > 8 || input.tags.some(t => typeof t !== 'string' || t.length > 40)) throw new Error('En fazla 8 kısa etiket gir.');
  data.tags = [...new Set(input.tags.map(t => t.trim()).filter(Boolean))];
  if (!Array.isArray(input.sources) || input.sources.length > 30) throw new Error('Kaynak listesi geçersiz.');
  data.sources = input.sources.map(s => {
    const url = safeUrl(s.url);
    if (!url || typeof s.title !== 'string' || s.title.length > 300) throw new Error('Kaynak başlığı veya bağlantısı geçersiz.');
    return {title:s.title.trim() || url, url};
  });
  return data;
}
export function contentHash(post) {
  return createHash('sha256').update(JSON.stringify({...editable(post), cover:post.cover || '', aiGenerated:!!post.aiGenerated})).digest('hex');
}
export function publicationErrors(post) {
  const errors = [];
  if (!post.title?.trim()) errors.push('Başlık gerekli.');
  if (!post.description?.trim()) errors.push('Özet gerekli.');
  if ((post.body?.trim().length || 0) < 100) errors.push('Yazı en az 100 karakter olmalı.');
  // Kapak ve kaynaklar isteğe bağlıdır; editör yazıyı kapaksız veya kaynaksız yayımlamayı seçebilir.
  return errors;
}
export function encodePost(post) {
  const {body, ...meta} = post;
  return `---json\n${JSON.stringify(meta, null, 2)}\n---\n\n${body}\n`;
}
export function decodePost(text) {
  const match = text.match(/^---json\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/);
  if (!match) throw new Error('Blog Markdown üst bilgisi geçersiz.');
  return {...JSON.parse(match[1]), body:match[2].trim()};
}
export async function readPublished(dir) {
  const names = await readdir(dir).catch(e => { if (e.code === 'ENOENT') return []; throw e; });
  const posts = [];
  for (const name of names.filter(n => n.endsWith('.md'))) {
    const p = decodePost(await readFile(path.join(dir,name),'utf8'));
    editable(p);
    if (name !== `${p.slug}.md` || !p.approvedAt || !Number.isFinite(Date.parse(p.publishedAt)) || p.approvedHash !== contentHash(p) || publicationErrors(p).length) throw new Error(`Onaysız veya değiştirilmiş yayın dosyası: ${name}`);
    if (p.cover && !/^[a-f0-9-]{36}\.(png|jpg)$/.test(p.cover)) throw new Error('Kapak dosyası geçersiz.');
    posts.push(p);
  }
  return posts.sort((a,b) => b.publishedAt.localeCompare(a.publishedAt));
}
export function articleBody(post, coverUrl) {
  return `<article class="blog-article"><header><span class="eyebrow">DİJİTAL REHBERLER · ${readMinutes(post.body)} DK OKUMA</span><h1>${escape(post.title)}</h1><p class="blog-lead">${escape(post.description)}</p><div class="blog-meta">DİJİROTA${post.publishedAt ? ` · <time datetime="${escape(post.publishedAt)}">${new Date(post.publishedAt).toLocaleDateString('tr-TR')}</time>` : ' · Taslak'}<div class="tags">${post.tags.map(t=>`<span>${escape(t)}</span>`).join('')}</div></div></header>${coverUrl ? `<figure class="blog-cover"><img src="${escape(coverUrl)}" alt="${escape(post.coverAlt)}" width="1536" height="1024">${post.aiGenerated ? '<figcaption>Yapay zekâ ile üretilmiş kapak görseli.</figcaption>' : ''}</figure>` : ''}<div class="blog-prose">${renderMarkdown(post.body)}</div>${post.sources.length ? `<aside class="blog-sources"><h2>Kaynaklar</h2><ul>${post.sources.map(s=>`<li><a href="${escape(safeUrl(s.url))}" rel="noopener noreferrer">${escape(s.title)}</a></li>`).join('')}</ul></aside>` : ''}${post.aiGenerated ? '<p class="blog-disclosure">Bu yazı yapay zekâ desteğiyle hazırlanmış, DİJİROTA tarafından gözden geçirilerek yayımlanmıştır.</p>' : ''}</article>`;
}
