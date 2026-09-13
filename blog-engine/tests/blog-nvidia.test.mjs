import test from 'node:test';
import assert from 'node:assert/strict';
import {NvidiaBlogAI} from '../lib/blog-nvidia.mjs';
import {createBlogAI} from '../lib/blog-provider.mjs';
import {publicationErrors} from '../lib/blog-content.mjs';

const png=Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=','base64');
const post={title:'JavaScript ile akış',description:'Kısa açıklama',body:'## Örnek\n\n'+'Bu bir test taslağıdır. '.repeat(10),tags:['JavaScript'],coverAlt:'Akış illüstrasyonu',coverPrompt:'Abstract code flow in charcoal and lime green',reviewNotes:['Kod örneğini çalıştır.']};
const chat=(content=JSON.stringify(post),finish_reason='stop')=>({ok:true,status:200,json:async()=>({choices:[{finish_reason,message:{content}}]})});

test('NVIDIA selection isolates credentials and model names from legacy OpenAI settings',()=>{
  const ai=createBlogAI({BLOG_AI_PROVIDER:'nvidia',NVIDIA_API_KEY:'nv-test',OPENAI_API_KEY:'openai-test',BLOG_TEXT_MODEL:'old-openai-model'});
  assert.equal(ai.key,'nv-test');assert.equal(ai.textModel,'nvidia/nemotron-3-super-120b-a12b');assert.equal(ai.researchMode,'manual');
  assert.equal(createBlogAI({BLOG_AI_PROVIDER:'openai',OPENAI_API_KEY:'openai-test',NVIDIA_API_KEY:'nv-test'}).key,'openai-test');
  assert.throws(()=>createBlogAI({BLOG_AI_PROVIDER:'typo'}));
});
test('NVIDIA uses chat completions, preserves supplied sources and discloses no web search',async()=>{
  const calls=[],stages=[],sources=[{title:'MDN',url:'https://developer.mozilla.org/'}];
  const ai=new NvidiaBlogAI({key:'nv-test',fetcher:async(url,options)=>{calls.push({url,...options});return chat();}});
  const result=await ai.write({topic:'JavaScript',brief:'Başlangıç düzeyi',sources},s=>stages.push(s));
  assert.deepEqual(stages,['writing']);assert.equal(calls[0].url,'https://integrate.api.nvidia.com/v1/chat/completions');
  assert.equal(calls[0].headers.Authorization,'Bearer nv-test');const body=JSON.parse(calls[0].body);assert.equal(body.max_tokens,8192);assert.equal(body.reasoning_effort,'none');assert.equal(body.stream,false);assert.equal(body.tools,undefined);
  assert.deepEqual(result.sources,sources);assert.match(result.reviewNotes[0],/canlı web araştırması yapılmadı/);assert.equal(result.generation.researchMode,'manual');
});
test('NVIDIA source-less drafts remain publishable without invented bibliography',async()=>{
  const ai=new NvidiaBlogAI({key:'nv-test',fetcher:async()=>chat()});
  const result=await ai.write({topic:'JavaScript'});assert.deepEqual(result.sources,[]);assert.equal(publicationErrors({...result,cover:'test.png'}).length,0);
});
test('Incomplete or malformed NVIDIA text fails without a usable draft',async()=>{
  for(const response of [chat('{}','length'),chat('not JSON'),chat('[]')]){
    const ai=new NvidiaBlogAI({key:'nv-test',fetcher:async()=>response});await assert.rejects(ai.write({topic:'Test'}));
  }
});
test('NVIDIA image request uses FLUX.2 Klein schema, checks PNG and rejects filtered output',async()=>{
  let request;
  const ai=new NvidiaBlogAI({key:'nv-test',fetcher:async(url,options)=>{request={url,body:JSON.parse(options.body)};return{ok:true,status:200,json:async()=>({artifacts:[{base64:png.toString('base64'),finishReason:'SUCCESS'}]})};}});
  assert.deepEqual(await ai.image('Abstract technical cover'),png);assert.match(request.url,/black-forest-labs\/flux\.2-klein-4b$/);assert.equal(request.body.width,1024);assert.equal(request.body.height,1024);assert.equal(request.body.prompt,'Abstract technical cover');assert.equal(request.body.steps,4);assert.equal(request.body.cfg_scale,1);
  for(const artifact of [{base64:'invalid',finishReason:'SUCCESS'},{base64:png.toString('base64'),finishReason:'CONTENT_FILTERED'}]){
    const bad=new NvidiaBlogAI({key:'nv-test',fetcher:async()=>({ok:true,status:200,json:async()=>({artifacts:[artifact]})})});await assert.rejects(bad.image('cover'));
  }
});
test('NVIDIA pending calls poll a fixed trusted endpoint without repeating POST',async()=>{
  const calls=[];let step=0;
  const ai=new NvidiaBlogAI({key:'nv-test',wait:async()=>{},fetcher:async(url,options)=>{calls.push({url,method:options.method});return step++===0?{status:202,headers:new Headers({'nvcf-reqid':'11111111-1111-4111-8111-111111111111'})}:chat();}});
  await ai.write({topic:'Test'});assert.deepEqual(calls.map(c=>c.method),['POST','GET']);assert.match(calls[1].url,/^https:\/\/api.nvcf.nvidia.com\/v2\/nvcf\/pexec\/status\//);
});

test('NVIDIA accepts JPEG artifacts and rejects truncated JPEG data',async()=>{
  const jpeg=Buffer.from([255,216,255,224,0,0,255,217]);
  const ai=new NvidiaBlogAI({key:'nv-test',fetcher:async()=>({ok:true,status:200,json:async()=>({artifacts:[{base64:jpeg.toString('base64')}]})})});
  assert.deepEqual(await ai.image('cover'),jpeg);
  ai.fetcher=async()=>({ok:true,status:200,json:async()=>({artifacts:[{base64:jpeg.subarray(0,-2).toString('base64')}]})});
  await assert.rejects(ai.image('cover'));
});
test('Missing NVIDIA key and account access errors are explicit without exposing the secret',async()=>{
  await assert.rejects(new NvidiaBlogAI({key:''}).image('cover'),/NVIDIA_API_KEY/);
  for(const status of [401,403,404,429]){
    const ai=new NvidiaBlogAI({key:'secret-nv',fetcher:async()=>({ok:false,status})});await assert.rejects(ai.image('cover'),e=>!e.message.includes('secret-nv')&&e.message.includes('NVIDIA'));
  }
});

test('NVIDIA 422 identifies rejected fields without exposing inputs or arbitrary API text',async()=>{
  const ai=new NvidiaBlogAI({key:'secret-nv',fetcher:async()=>({ok:false,status:422,json:async()=>({detail:[{loc:['body','cfg_scale'],type:'greater_than_equal',msg:'secret-nv',input:'private prompt'},{loc:['body','secret-nv']}]})})});
  await assert.rejects(ai.image('cover'),e=>e.message.includes('422')&&e.message.includes('cfg_scale')&&!e.message.includes('secret-nv')&&!e.message.includes('private prompt'));
  ai.fetcher=async()=>({ok:false,status:422,json:async()=>{throw new Error('invalid JSON');}});
  await assert.rejects(ai.image('cover'),/HTTP 422/);
});
