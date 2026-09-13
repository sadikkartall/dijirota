import http from 'node:http';
import {createAdmin} from './blog-admin.mjs';
import {bridgeHandler} from './bridge-handler.mjs';
import {build} from './build.mjs';
await build();
const {handler}=await createAdmin({port:4180,base:'/admin/blog'});
const server=http.createServer(bridgeHandler(handler));
server.listen(4180,'0.0.0.0');
for(const signal of ['SIGTERM','SIGINT'])process.once(signal,()=>server.close(()=>process.exit(0)));
