import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, writeFile, rm, access } from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';
import { Readable } from 'node:stream';
import { BlogStore, GenerationQueue } from '../lib/blog-store.mjs';
import { BlogAI, responseText, sourcesFrom } from '../lib/blog-ai.mjs';
import { renderMarkdown, contentHash, decodePost, readPublished } from '../lib/blog-content.mjs';
import { build } from '../scripts/build.mjs';
import { createAdmin } from '../scripts/blog-admin.mjs';
import { lockAdmin } from '../lib/blog-lock.mjs';

const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=','base64');
async function setup(t,rebuild) {
  const root=await mkdtemp(path.join(os.tmpdir(),'portfolio-blog-test-')); t.after(()=>rm(root,{recursive:true,force:true}));
  const store=new BlogStore({root,rebuild});await store.init();return {root,store};
}
async function ready(store) {
  let d=await store.create({topic:'JavaScript ve asenkron akış'});
  d=await store.save(d.id,{...d,description:'Asenkron işlemleri küçük örneklerle anlamak.',body:'## Asenkron işlemler\n\n'+ 'Bu yazı yalnızca izole test için oluşturulmuş örnek içeriktir. '.repeat(6),tags:['JavaScript'],sources:[{title:'MDN',url:'https://developer.mozilla.org/'}],coverAlt:'Asenkron akış illüstrasyonu',coverPrompt:'Abstract asynchronous programming editorial illustration.'});
  return store.updateJob(d.id,{cover:await store.storeCover(png),aiGenerated:true});
}
const approval=d=>({version:d.version,hash:contentHash(d),reviewed:true});

test('JPEG covers retain their extension through approval and published reads',async t=>{
  const {store,root}=await setup(t);let d=await ready(store);
  const cover=await store.storeCover(Buffer.from([255,216,255,224,0,0,255,217]));
  assert.match(cover,/\.jpg$/);d=await store.updateJob(d.id,{cover});
  await store.approve(d.id,approval(d));
  assert.equal((await readPublished(path.join(root,'content/blog')))[0].cover,cover);
});

test('Markdown scripts, event handlers, frames and unsafe links are removed; code and tables survive',()=> {
  const html=renderMarkdown('# Başlık\n\n<script>alert(1)</script><img src=x onerror=alert(1)><iframe src=x></iframe>\n\n[bad](javascript:alert(1))\n\n```js\nconst a = "<script>";\n```\n\n| A | B |\n|---|---|\n|1|2|');
  assert.doesNotMatch(html,/<script|onerror|<iframe|javascript:|<img|<h1/);assert.match(html,/<h2>Başlık/);assert.match(html,/&lt;script&gt;/);assert.match(html,/<table>/);
});
test('Drafts stay private, approval requires current version, hash and explicit review',async t=> {
  const {store,root}=await setup(t);const d=await ready(store);
  assert.deepEqual(await readPublished(path.join(root,'content/blog')),[]);
  await assert.rejects(store.approve(d.id,{...approval(d),reviewed:false}));
  await assert.rejects(store.approve(d.id,{...approval(d),hash:'old'}));
  await assert.rejects(store.approve(d.id,{...approval(d),version:undefined}));
  await store.approve(d.id,approval(d));assert.equal((await readPublished(path.join(root,'content/blog'))).length,1);
});
test('Editing approved content leaves old snapshot intact and invalidates old review',async t=> {
  const {store,root}=await setup(t);let d=await ready(store);d=await store.approve(d.id,approval(d));
  const oldHash=contentHash(d);d=await store.save(d.id,{...d,title:'Yeni başlık'});
  assert.notEqual(contentHash(d),oldHash);
  assert.notEqual((await readPublished(path.join(root,'content/blog')))[0].title,d.title);
  await assert.rejects(store.approve(d.id,{...approval(d),hash:oldHash}));
  await store.approve(d.id,approval(d));assert.equal((await readPublished(path.join(root,'content/blog')))[0].title,'Yeni başlık');
});
test('Concurrent stale edits, duplicate publication slugs and published slug changes fail',async t=> {
  const {store}=await setup(t);let d=await ready(store);const stale=structuredClone(d);d=await store.save(d.id,{...d,title:'Değişti'});
  await assert.rejects(store.save(d.id,stale));d=await store.approve(d.id,approval(d));
  await assert.rejects(store.save(d.id,{...d,slug:'different'}));
  const other=await ready(store);await assert.rejects(store.approve(other.id,approval(other)));
});
test('Approval may omit a cover and manually modified published files fail closed',async t=> {
  const {store,root}=await setup(t);let d=await ready(store);d=await store.updateJob(d.id,{cover:''});d=await store.approve(d.id,approval(d));
  d=await store.save(d.id,{...d,title:'Kapaksız güncel taslak'});d=await store.updateJob(d.id,{cover:await store.storeCover(png)});d=await store.approve(d.id,approval(d));
  const file=path.join(root,'content/blog',`${d.slug}.md`);await writeFile(file,(await readFile(file,'utf8'))+'Unapproved changes');
  await assert.rejects(readPublished(path.join(root,'content/blog')));
});
test('Build failure rolls publication back and preserves draft',async t=> {
  let fail=false;const {store,root}=await setup(t,async()=>{if(fail)throw new Error('simulated build failure');});
  let d=await ready(store);d=await store.approve(d.id,approval(d));const before=await readFile(path.join(root,'content/blog',`${d.slug}.md`),'utf8');
  d=await store.save(d.id,{...d,title:'Yeni sürüm'});fail=true;await assert.rejects(store.approve(d.id,approval(d)));
  assert.equal(await readFile(path.join(root,'content/blog',`${d.slug}.md`),'utf8'),before);assert.equal(store.get(d.id).title,'Yeni sürüm');
});
test('Text survives cover API failure; image-only retry does not regenerate text',async t=> {
  const {store}=await setup(t);const d=await ready(store);let imageFails=true,writes=0;
  const ai={key:'test',imageModel:'mock',write:async input=>{writes++;return {...input,body:input.body+' Generated.'};},image:async()=>{if(imageFails)throw new Error('Image failed');return png;}};
  const queue=new GenerationQueue(store,ai);await queue.start(d.id,d.version);await queue.task;
  let current=store.get(d.id);assert.equal(current.status,'failed');assert.match(current.body,/Generated/);assert.equal(current.cover,'');
  imageFails=false;await queue.start(d.id,current.version,'image');await queue.task;current=store.get(d.id);assert.equal(current.status,'draft');assert.ok(current.cover);assert.equal(writes,1);
});
test('Concurrent generation is rejected and interrupted jobs recover without automatic paid retry',async t=> {
  const {store,root}=await setup(t);const d=await ready(store);let release;
  const ai={key:'test',write:()=>new Promise(resolve=>{release=()=>resolve({...d});}),image:async()=>png};
  const queue=new GenerationQueue(store,ai);await queue.start(d.id,d.version);await assert.rejects(queue.start(d.id,d.version));release();await queue.task;
  const current=store.get(d.id);await store.queue(current.id,current.version);
  const next=new BlogStore({root});await next.init();assert.equal(next.get(d.id).status,'failed');assert.match(next.get(d.id).error,/yarım/);
});
test('API output handling rejects incomplete/refusal and records observed sources only',()=> {
  assert.throws(()=>responseText({status:'incomplete'}));assert.throws(()=>responseText({status:'completed',output:[{content:[{type:'refusal'}]}]}));
  assert.deepEqual(sourcesFrom({output:[{content:[{annotations:[{type:'url_citation',url:'https://example.com/',title:'Example'},{type:'url_citation',url:'javascript:alert(1)'}]}]}]}),[{title:'Example',url:'https://example.com/'}]);
});
test('AI requests use server-side key, research tool, structured output and validated PNG',async()=> {
  const calls=[];let step=0;
  const message=text=>({status:'completed',output:[{type:'message',content:[{type:'output_text',text,annotations:[{type:'url_citation',title:'MDN',url:'https://developer.mozilla.org/'}]}]}]});
  const ai=new BlogAI({key:'test-secret',fetcher:async(url,options)=>{calls.push({url,options,body:JSON.parse(options.body)});step++;return {ok:true,json:async()=>step===1?message('Araştırma'):step===2?message(JSON.stringify({title:'Test yazısı',description:'Özet',body:'## Kod\n\nÖrnek',tags:['JS'],coverAlt:'Kapak',coverPrompt:'Editorial image',reviewNotes:['Kodu çalıştır']})):{data:[{b64_json:png.toString('base64')}]}};}});
  const result=await ai.write({topic:'Asenkron akış'});assert.equal(result.sources.length,1);assert.equal(calls[0].body.tools[0].type,'web_search');assert.equal(calls[1].body.text.format.strict,true);assert.equal(calls[0].options.headers.Authorization,'Bearer test-secret');assert.deepEqual(await ai.image('test'),png);
});
test('Missing key and API errors are actionable without leaking credentials',async()=> {
  await assert.rejects(new BlogAI({key:''}).image('test'),/OPENAI_API_KEY/);
  await assert.rejects(new BlogAI({key:'secret',fetcher:async()=>({ok:false,status:429})}).image('test'),/kota/);
});
test('Public manifest exposes only approved snapshots and withdrawal removes access',async t=> {
  const {store,root}=await setup(t);const outDir=path.join(root,'dist');
  store.rebuild=()=>build({root});let d=await ready(store);
  await store.rebuild();
  const read=async()=>JSON.parse(await readFile(path.join(outDir,'published.json'),'utf8'));
  assert.deepEqual(await read(),[]);
  d=await store.approve(d.id,approval(d));
  const [post]=await read();assert.equal(post.slug,d.slug);assert.ok(post.html.includes(d.title));
  assert.ok(post.html.includes('/blog/covers/'));assert.equal(post.body,undefined);
  d=await store.save(d.id,{...d,title:'Private edited title'});
  assert.notEqual((await read())[0].title,d.title);
  d=await store.approve(d.id,approval(d));assert.equal((await read())[0].title,d.title);
  await store.unpublish(d.id,d.version);assert.deepEqual(await read(),[]);
});
test('Coverless approved publication has no broken image or empty cover URL',async t=> {
  const {store,root}=await setup(t);store.rebuild=()=>build({root});let d=await ready(store);
  d=await store.updateJob(d.id,{cover:''});await store.approve(d.id,approval(d));
  const [p]=JSON.parse(await readFile(path.join(root,'dist/published.json'),'utf8'));
  assert.doesNotMatch(p.html,/<img|blog\/covers/);
});
async function request(handler,{url,method='GET',data,headers={}}) {
  const req=Readable.from(data?[Buffer.from(JSON.stringify(data))]:[]);Object.assign(req,{url,method,headers:{host:'127.0.0.1:4180',...headers}});
  const res={headers:{},headersSent:false,setHeader(k,v){this.headers[k]=v;},writeHead(s,h){this.status=s;Object.assign(this.headers,h);this.headersSent=true;},end(body){this.body=body?.toString() || '';}};
  await handler(req,res);return {...res,json:()=>JSON.parse(res.body)};
}
test('Admin handler enforces host, origin, token and private file boundaries without starting a server',async t=> {
  const {root}=await setup(t);const {handler}=await createAdmin({root,rebuild:async()=>{}});
  let r=await request(handler,{url:'/api/session'});assert.equal(r.status,200);const token=r.json().token;
  assert.equal((await request(handler,{url:'/api/session',headers:{host:'evil.example'}})).status,403);
  assert.equal((await request(handler,{url:'/api/drafts',method:'POST',data:{topic:'Test konu'}})).status,403);
  const headers={origin:'http://127.0.0.1:4180','x-blog-token':token,'content-type':'application/json'};
  assert.equal((await request(handler,{url:'/api/drafts',method:'POST',headers,data:[]})).status,400);
  assert.equal((await request(handler,{url:'/api/drafts',method:'POST',headers:{...headers,origin:'https://evil.example'},data:{topic:'Test konu'}})).status,403);
  r=await request(handler,{url:'/api/drafts',method:'POST',headers,data:{topic:'Test konu'}});assert.equal(r.status,201);
  for(const url of ['/.env','/.blog-data/drafts.json','/lib/blog-ai.mjs','/covers/../../.env'])assert.equal((await request(handler,{url})).status,404);
  assert.equal((await request(handler,{url:`/api/drafts/${r.json().id}/approve`,method:'POST',headers,data:{version:1,hash:r.json().hash,reviewed:false}})).status,409);
});

test('Only one admin process can hold the content lock; releasing permits reopening',async t=> {
  const {root}=await setup(t);
  const release=await lockAdmin(root);
  try {await assert.rejects(lockAdmin(root),/zaten açık/);} finally {release();}
  const releaseAgain=await lockAdmin(root);releaseAgain();
  await assert.rejects(access(path.join(root,'.blog-data/admin.lock')));
});
