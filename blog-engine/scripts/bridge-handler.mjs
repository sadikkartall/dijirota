import {timingSafeEqual} from 'node:crypto';
export function bridgeHandler(handler,secret=process.env.BLOG_BRIDGE_SECRET) {
  if(!secret || secret.length<32)throw new Error('BLOG_BRIDGE_SECRET yapılandırılmalı.');
  return async(req,res)=>{
    const supplied=Buffer.from(req.headers['x-blog-bridge']||'');
    const expected=Buffer.from(secret);
    if(supplied.length!==expected.length||!timingSafeEqual(supplied,expected)) {
      res.writeHead(403,{'Content-Type':'application/json'});res.end('{"error":"Erişim reddedildi."}');return;
    }
    if(req.url==='/health' && req.method==='GET'){res.writeHead(200);res.end('ok');return;}
    return handler(req,res);
  };
}
