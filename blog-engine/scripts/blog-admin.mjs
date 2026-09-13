import { readFile } from 'node:fs/promises';
import { randomBytes, timingSafeEqual } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { BlogStore, GenerationQueue, BlogError } from '../lib/blog-store.mjs';
import { createBlogAI } from '../lib/blog-provider.mjs';
import { articleBody, contentHash, publicationErrors } from '../lib/blog-content.mjs';
import { build } from './build.mjs';

const ROOT=fileURLToPath(new URL('../',import.meta.url));
export async function createAdmin({root=ROOT,port=4180,ai=createBlogAI(),rebuild=()=>build(),base=""}={}) {
  const store=new BlogStore({root,rebuild}); await store.init();
  const queue=new GenerationQueue(store,ai), token=randomBytes(32).toString('hex');
  const json=(res,status,data)=> {res.writeHead(status,{'Content-Type':'application/json; charset=utf-8'});res.end(JSON.stringify(data));};
  const publicDraft=d=>({...d,hash:contentHash(d),publicationErrors:publicationErrors(d),coverUrl:d.cover?`${base}/covers/${d.cover}`:''});
  async function body(req) {
    if(!req.headers['content-type']?.startsWith('application/json')) throw new BlogError('JSON gövdesi gerekli.',415);
    let size=0; const chunks=[];
    for await(const chunk of req) {size+=chunk.length;if(size>150000)throw new BlogError('İstek çok büyük.',413);chunks.push(chunk);}
    let value;
    try{value=JSON.parse(Buffer.concat(chunks).toString('utf8'));}catch{throw new BlogError('JSON okunamadı.');}
    if(!value || typeof value!=='object' || Array.isArray(value)) throw new BlogError('İstek bir JSON nesnesi olmalı.');
    return value;
  }
  const handler=async(req,res)=> {
    res.setHeader('Cache-Control','no-store'); res.setHeader('X-Content-Type-Options','nosniff'); res.setHeader('Referrer-Policy','no-referrer');
    res.setHeader('X-Frame-Options','SAMEORIGIN');
    res.setHeader('Content-Security-Policy',"default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'; form-action 'self'");
    try {
      const hosts=[`127.0.0.1:${port}`,`localhost:${port}`];
      if(!hosts.includes(req.headers.host)) throw new BlogError('Yalnızca yerel panel adresine erişilebilir.',403);
      if(req.headers['sec-fetch-site']==='cross-site') throw new BlogError('Başka bir siteden erişim reddedildi.',403);
      const url=new URL(req.url,`http://${req.headers.host}`),p=url.pathname;
      if(!['GET','POST','PUT'].includes(req.method)) throw new BlogError('Yöntem desteklenmiyor.',405);
      if(req.method!=='GET') {
        if(req.headers.origin!==`http://${req.headers.host}`) throw new BlogError('İstek kaynağı geçersiz.',403);
        const supplied=Buffer.from(req.headers['x-blog-token'] || '');
        if(supplied.length!==token.length || !timingSafeEqual(supplied,Buffer.from(token))) throw new BlogError('Panel oturumu değişti. Sayfayı yenile.',403);
      }
      if(p==='/api/session' && req.method==='GET') return json(res,200,{token,configured:!!ai.key,textModel:ai.textModel,imageModel:ai.imageModel,provider:ai.provider,keyName:ai.keyName,researchMode:ai.researchMode});
      if(p==='/api/drafts' && req.method==='GET') return json(res,200,{drafts:store.list().map(publicDraft),active:queue.active});
      if(p==='/api/drafts' && req.method==='POST') return json(res,201,publicDraft(await store.create(await body(req))));
      const m=p.match(/^\/api\/drafts\/([a-f0-9-]{36})(?:\/(generate|approve|unpublish|preview))?$/);
      if(m) {
        const [,id,action]=m;
        if(!action && req.method==='GET') return json(res,200,publicDraft(store.get(id)));
        if(!action && req.method==='PUT') return json(res,200,publicDraft(await store.save(id,await body(req))));
        if(action==='generate' && req.method==='POST') { const b=await body(req); return json(res,202,publicDraft(await queue.start(id,b.version,b.mode))); }
        if(action==='approve' && req.method==='POST') return json(res,200,publicDraft(await store.approve(id,await body(req))));
        if(action==='unpublish' && req.method==='POST') {const b=await body(req);return json(res,200,publicDraft(await store.unpublish(id,b.version)));}
        if(action==='preview' && req.method==='GET') {
          const d=store.get(id); const article=articleBody({...d,publishedAt:null},d.cover?`${base}/covers/${d.cover}`:'').replace('DİJİROTA tarafından gözden geçirilerek yayımlanmıştır.','henüz yayın onayı verilmemiştir.');
          return json(res,200,{hash:contentHash(d),html:`<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="${base}/assets/style.css"><link rel="stylesheet" href="${base}/assets/blog.css"></head><body><main class="container blog-main">${article}</main></body></html>`});
        }
      }
      const staticFiles={'/':'admin/index.html','/admin.css':'admin/admin.css','/admin.js':'admin/admin.js','/assets/style.css':'assets/style.css','/assets/blog.css':'assets/blog.css'};
      let file=staticFiles[p];
      if(/^\/assets\/fonts\/[a-zA-Z0-9._-]+\.(ttf|woff2)$/.test(p))file=p.slice(1);
      if(/^\/covers\/[a-f0-9-]{36}\.(png|jpg)$/.test(p))file=`.blog-data${p}`;
      if(file && req.method==='GET') {
        const bytes=await readFile(path.join(root,file));
        const types={'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.png':'image/png','.jpg':'image/jpeg','.ttf':'font/ttf','.woff2':'font/woff2'};
        res.writeHead(200,{'Content-Type':types[path.extname(file)]});res.end(bytes);return;
      }
      throw new BlogError('Sayfa bulunamadı.',404);
    } catch(e) { if(!res.headersSent) json(res,e.status || (e.code==='ENOENT'?404:500),{error:e.status?e.message:'İşlem tamamlanamadı. Dosya izinlerini ve panel bağlantısını kontrol et.'});else res.end(); }
  };
  return {handler,store,queue};
}
if(process.argv[1] && path.resolve(process.argv[1])===fileURLToPath(import.meta.url)) {
  console.log('Site ve panel için npm run dev komutunu kullan: http://localhost:4173/admin');
}
