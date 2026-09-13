const res=await fetch('http://127.0.0.1:4180/health',{headers:{'X-Blog-Bridge':process.env.BLOG_BRIDGE_SECRET},signal:AbortSignal.timeout(3000)});
if(!res.ok)process.exit(1);
