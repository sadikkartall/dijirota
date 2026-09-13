import { mkdir, open, readFile, unlink } from 'node:fs/promises';
import { unlinkSync } from 'node:fs';
import path from 'node:path';

export async function lockAdmin(root) {
  const file=path.join(root,'.blog-data/admin.lock');await mkdir(path.dirname(file),{recursive:true});
  for(let attempt=0;attempt<2;attempt++) {
    let handle;
    try {handle=await open(file,'wx');}
    catch(e) {
      if(e.code!=='EEXIST')throw e;
      let pid;
      try{pid=JSON.parse(await readFile(file,'utf8')).pid;}catch{throw new Error('Panel kilidi okunamadı. Açık panel olmadığından emin olduktan sonra .blog-data/admin.lock dosyasını kaldır.');}
      if(!Number.isInteger(pid)||pid<1)throw new Error('Panel kilidi geçersiz. .blog-data/admin.lock dosyasını kontrol et.');
      try{process.kill(pid,0);throw new Error('Blog paneli zaten açık. Önce mevcut paneli Ctrl+C ile kapat.');}
      catch(error){if(error.code!=='ESRCH')throw error;}
      await unlink(file).catch(error=>{if(error.code!=='ENOENT')throw error;});continue;
    }
    await handle.writeFile(JSON.stringify({pid:process.pid}));await handle.close();
    return ()=>{try{unlinkSync(file);}catch{}};
  }
  throw new Error('Panel kilidi alınamadı. Tek panel çalıştırıp tekrar dene.');
}
