import test from 'node:test';
import assert from 'node:assert/strict';
import {Readable} from 'node:stream';
import {bridgeHandler} from '../scripts/bridge-handler.mjs';
const secret='a'.repeat(64);
async function request(handler,headers={},url='/api/session'){
  const req=Readable.from([]);Object.assign(req,{headers,url,method:'GET'});
  const res={writeHead(status){this.status=status;},end(body){this.body=body;}};
  await handler(req,res);return res;
}
test('Private service rejects absent, forged and wrong-length bridge credentials',async()=>{
  let calls=0;const handler=bridgeHandler((req,res)=>{calls++;res.writeHead(200);res.end('private');},secret);
  for(const value of ['', 'fake', 'b'.repeat(64)])assert.equal((await request(handler,{'x-blog-bridge':value})).status,403);
  assert.equal(calls,0);
  assert.equal((await request(handler,{'x-blog-bridge':secret})).body,'private');assert.equal(calls,1);
});
test('Health check is authenticated and missing service configuration fails closed',async()=>{
  assert.throws(()=>bridgeHandler(()=>{},''));
  const handler=bridgeHandler(()=>assert.fail('health must not enter admin handler'),secret);
  assert.equal((await request(handler,{},'/health')).status,403);
  assert.equal((await request(handler,{'x-blog-bridge':secret},'/health')).body,'ok');
});
