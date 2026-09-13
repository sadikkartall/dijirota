import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {readPublished, articleBody} from '../lib/blog-content.mjs';
import {atomicWrite} from '../lib/blog-store.mjs';
const ROOT=fileURLToPath(new URL('../',import.meta.url));
export async function build({root=ROOT,contentDir=path.join(root,'content/blog'),outDir=path.join(root,'dist')}={}) {
  const posts=await readPublished(contentDir);
  // A single atomic manifest switches every public page to the approved snapshot.
  const published=posts.map(p=>({slug:p.slug,title:p.title,description:p.description,tags:p.tags,
    publishedAt:p.publishedAt,approvedAt:p.approvedAt,cover:p.cover,coverAlt:p.coverAlt,
    html:articleBody(p,p.cover?`/blog/covers/${p.cover}`:'')}));
  await atomicWrite(path.join(outDir,'published.json'),JSON.stringify(published));
  return published;
}
if(process.argv[1] && path.resolve(process.argv[1])===fileURLToPath(import.meta.url))await build();
