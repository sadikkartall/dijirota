import { BlogAI } from './blog-ai.mjs';
import { NvidiaBlogAI } from './blog-nvidia.mjs';

export function createBlogAI(env=process.env) {
  const provider=(env.BLOG_AI_PROVIDER || 'nvidia').trim().toLowerCase();
  if(provider==='nvidia')return new NvidiaBlogAI({key:env.NVIDIA_API_KEY,textModel:env.NVIDIA_TEXT_MODEL || 'nvidia/nemotron-3-super-120b-a12b'});
  if(provider==='openai')return Object.assign(new BlogAI({key:env.OPENAI_API_KEY,textModel:env.BLOG_TEXT_MODEL || 'gpt-5-mini',imageModel:env.BLOG_IMAGE_MODEL || 'gpt-image-1.5'}),{provider:'OpenAI',keyName:'OPENAI_API_KEY',researchMode:'web'});
  throw new Error('BLOG_AI_PROVIDER nvidia veya openai olmalı.');
}
