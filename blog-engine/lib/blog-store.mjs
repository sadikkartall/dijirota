import { mkdir, readFile, writeFile, rename, readdir, rm, copyFile } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';
import path from 'node:path';
import { editable, slugify, contentHash, encodePost, publicationErrors } from './blog-content.mjs';

export const busyStates = new Set(['queued','researching','writing','imaging']);
const requireVersion = value => {if(!Number.isInteger(value) || value<1) throw new BlogError('Taslak sürümü gerekli.',409);};
export class BlogError extends Error { constructor(message, status=400) { super(message); this.status=status; } }
export async function atomicWrite(file, data) {
  await mkdir(path.dirname(file), {recursive:true});
  const temp=`${file}.${randomUUID()}.tmp`;
  try { await writeFile(temp,data); await rename(temp,file); } finally { await rm(temp,{force:true}); }
}
export class BlogStore {
  constructor({root, rebuild=async()=>{}}) {
    this.root=root; this.privateDir=path.join(root,'.blog-data'); this.contentDir=path.join(root,'content/blog');
    this.file=path.join(this.privateDir,'drafts.json'); this.rebuild=rebuild; this.tail=Promise.resolve(); this.state={drafts:[]};
  }
  async init() {
    await mkdir(path.join(this.privateDir,'covers'),{recursive:true});
    await mkdir(path.join(this.contentDir,'covers'),{recursive:true});
    try { this.state=JSON.parse(await readFile(this.file,'utf8')); } catch(e) { if(e.code!=='ENOENT') throw e; }
    let interrupted=false;
    for(const d of this.state.drafts) if(busyStates.has(d.status)) { d.status='failed'; d.error='Önceki üretim panel kapandığı için yarım kaldı. Kaydedilmiş içerik korundu; yeniden deneyebilirsin.'; d.version++; interrupted=true; }
    if(interrupted) await atomicWrite(this.file,JSON.stringify(this.state,null,2));
  }
  exclusive(fn) { const task=this.tail.then(fn); this.tail=task.catch(()=>{}); return task; }
  list() { return structuredClone(this.state.drafts).sort((a,b)=>b.updatedAt.localeCompare(a.updatedAt)); }
  get(id) { const d=this.state.drafts.find(d=>d.id===id); if(!d) throw new BlogError('Taslak bulunamadı.',404); return structuredClone(d); }
  async change(fn) {
    return this.exclusive(async()=> { const next=structuredClone(this.state); const result=await fn(next); await atomicWrite(this.file,JSON.stringify(next,null,2)); this.state=next; return structuredClone(result); });
  }
  find(state,id,version) {
    const d=state.drafts.find(d=>d.id===id);
    if(!d) throw new BlogError('Taslak bulunamadı.',404);
    if(version!==undefined && d.version!==version) throw new BlogError('Taslak başka bir işlemle değişti. Listeyi yenileyip son sürümü aç.',409);
    return d;
  }
  touch(d) { d.version++; d.updatedAt=new Date().toISOString(); }
  assertIdle(d) { if(busyStates.has(d.status)) throw new BlogError('Üretim devam ediyor. Tamamlandıktan sonra tekrar dene.',409); }
  async create({topic,brief=''}) {
    if(typeof topic!=='string' || topic.trim().length<3 || topic.length>300 || typeof brief!=='string' || brief.length>3000) throw new BlogError('3–300 karakterlik bir konu ve en fazla 3000 karakterlik not gir.');
    return this.change(state=> {
      const now=new Date().toISOString();
      const d={id:randomUUID(),topic:topic.trim(),brief:brief.trim(),title:topic.trim().slice(0,160),slug:slugify(topic),description:'',body:'',tags:[],sources:[],cover:'',coverAlt:'',coverPrompt:'',aiGenerated:false,reviewNotes:[],status:'draft',version:1,createdAt:now,updatedAt:now};
      state.drafts.push(d); return d;
    });
  }
  async save(id,input) {
    requireVersion(input.version);
    let fields;
    try{fields=editable(input);}catch(error){throw new BlogError(error.message);}
    return this.change(state=> { const d=this.find(state,id,input.version); this.assertIdle(d);
      if(d.publishedSlug && fields.slug!==d.publishedSlug) throw new BlogError('Yayımlanmış yazının adresi değiştirilemez.');
      Object.assign(d,fields,{status:'draft',error:''}); this.touch(d); return d;
    });
  }
  async updateJob(id,fields) { return this.change(state=> { const d=this.find(state,id); Object.assign(d,fields); this.touch(d); return d; }); }
  async queue(id,version) { requireVersion(version); return this.change(state=> { const d=this.find(state,id,version); this.assertIdle(d); d.status='queued'; d.error=''; this.touch(d); return d; }); }
  async storeCover(bytes) {
    const extension=bytes.subarray(0,3).equals(Buffer.from([255,216,255]))?'jpg':'png';
    const file=`${randomUUID()}.${extension}`; await atomicWrite(path.join(this.privateDir,'covers',file),bytes); return file;
  }
  async approve(id,{version,hash,reviewed}) {
    requireVersion(version);
    return this.exclusive(async()=> {
      const next=structuredClone(this.state),d=this.find(next,id,version); this.assertIdle(d);
      if(reviewed!==true || hash!==contentHash(d)) throw new BlogError('Son taslağı önizleyip içerik ve kapağı onayla.',409);
      const errors=publicationErrors(d); if(errors.length) throw new BlogError(errors.join(' '));
      if(next.drafts.some(other=>other.id!==id && other.publishedSlug===d.slug)) throw new BlogError('Bu adres başka bir yazıda kullanılıyor.',409);
      const file=path.join(this.contentDir,`${d.slug}.md`);
      const previous=await readFile(file).catch(e=> {if(e.code==='ENOENT')return null;throw e;});
      if(previous && d.publishedSlug!==d.slug) throw new BlogError('Bu adreste bir yayın zaten var.',409);
      const now=new Date().toISOString();
      const post={...editable(d),id:d.id,cover:d.cover,aiGenerated:d.aiGenerated,publishedAt:d.publishedAt || now,approvedAt:now,approvedHash:contentHash(d)};
    if(d.cover) await copyFile(path.join(this.privateDir,'covers',d.cover),path.join(this.contentDir,'covers',d.cover));
      try {
        await atomicWrite(file,encodePost(post));
        await this.rebuild();
        Object.assign(d,{publishedSlug:d.slug,publishedAt:post.publishedAt,approvedAt:now,approvedHash:post.approvedHash,status:'approved',error:''}); this.touch(d);
        await atomicWrite(this.file,JSON.stringify(next,null,2)); this.state=next;
      } catch(e) {
        if(previous) await atomicWrite(file,previous); else await rm(file,{force:true});
        await this.rebuild().catch(()=>{});
        throw new BlogError('Yayın dosyaları hazırlanamadı; önceki onay korundu. Dosya izinlerini ve derlemeyi kontrol et.',500);
      }
      return structuredClone(d);
    });
  }
  async unpublish(id,version) {
    requireVersion(version);
    return this.exclusive(async()=> {
      const next=structuredClone(this.state),d=this.find(next,id,version); this.assertIdle(d);
      if(!d.publishedSlug) throw new BlogError('Bu yazı yayında değil.');
      const file=path.join(this.contentDir,`${d.publishedSlug}.md`),previous=await readFile(file);
      try {
        await rm(file); await this.rebuild();
        delete d.publishedSlug; delete d.publishedAt; delete d.approvedHash; delete d.approvedAt; d.status='draft'; this.touch(d);
        await atomicWrite(this.file,JSON.stringify(next,null,2)); this.state=next;
      } catch(e) { await atomicWrite(file,previous); await this.rebuild().catch(()=>{}); throw new BlogError('Yayından kaldırma tamamlanamadı; önceki yayın korundu.',500); }
      return structuredClone(d);
    });
  }
}

export class GenerationQueue {
  constructor(store,ai) { this.store=store; this.ai=ai; this.active=null; }
  async start(id,version,mode='all') {
    if(this.active) throw new BlogError('Bir üretim zaten devam ediyor. Tamamlandıktan sonra tekrar dene.',409);
    if(!['all','text','image'].includes(mode)) throw new BlogError('Üretim türü geçersiz.');
    if(!this.ai.key) throw new BlogError(`${this.ai.keyName || 'API anahtarı'} tanımlı değil. .env dosyasını doldurup paneli yeniden başlat.`);
    this.active=id;
    let draft;
    try { draft=await this.store.queue(id,version); } catch(e) {this.active=null;throw e;}
    this.task=this.run(draft,mode).finally(()=> {this.active=null;});
    return draft;
  }
  async run(draft,mode) {
    try {
      if(mode!=='image') {
        const fields=await this.ai.write(draft,status=>this.store.updateJob(draft.id,{status}));
        draft=await this.store.updateJob(draft.id,{...fields,cover:'',status:mode==='text'?'draft':'imaging'});
      }
      if(mode!=='text') {
        await this.store.updateJob(draft.id,{status:'imaging'});
        const bytes=await this.ai.image(draft.coverPrompt);
        const cover=await this.store.storeCover(bytes);
        await this.store.updateJob(draft.id,{cover,aiGenerated:true,generation:{...draft.generation,imageModel:this.ai.imageModel},status:'draft',error:''});
      }
    } catch(e) { await this.store.updateJob(draft.id,{status:'failed',error:e.message}).catch(()=>{}); }
  }
}
