(() => {
  'use strict';
  const $=id=>document.getElementById(id);
  const labels={draft:'Taslak',approved:'Onaylandı',queued:'Sırada',researching:'Araştırılıyor',writing:'Yazılıyor',imaging:'Kapak üretiliyor',failed:'İşlem tamamlanamadı'};
  const busy=new Set(['queued','researching','writing','imaging']);
  let token='',configured=false,selected=null,drafts=[],dirty=false,previewHash='',working=false;
  const feedback=message=>{$('feedback').textContent=message;};
  async function api(url,method='GET',data) {
    const response=await fetch(`/admin/blog${url}`,{method,headers:{'Content-Type':'application/json','X-Blog-Token':token},...(data?{body:JSON.stringify(data)}:{})});
    const result=await response.json(); if(response.status===401 && !dirty)location.assign('/admin'); if(!response.ok)throw new Error(result.error || 'İşlem tamamlanamadı.');return result;
  }
  $('logout').onclick=()=>action(async()=>{
    if(dirty&&!confirm('Kaydedilmemiş değişiklikler var. Çıkış yapılsın mı?'))return;
    await api('/logout','POST');dirty=false;location.assign('/admin');
  });
  async function action(fn) {
    if(working)return;working=true;updateControls();
    try{await fn();}catch(e){feedback(e.message);}finally{working=false;updateControls();}
  }
  function updateControls() {
    const generating=selected && busy.has(selected.status);
    $('edit-fields').disabled=working || generating;
    $('create-generate').disabled=working || !configured || drafts.some(d=>busy.has(d.status));
    $('create-manual').disabled=working;
    $('regenerate-text').disabled=!configured || working || generating;
    $('regenerate-cover').disabled=!configured || working || generating;
    $('reviewed').disabled=!selected || dirty || generating || working || previewHash!==selected.hash || !!selected.publicationErrors.length;
    $('approve').disabled=$('reviewed').disabled || !$('reviewed').checked;
    $('unpublish').disabled=working || generating || dirty;
    $('refresh').disabled=working;
  }
  function list() {
    $('draft-list').replaceChildren();
    if(!drafts.length){const p=document.createElement('p');p.className='hint';p.textContent='Henüz taslak yok. İlk konunu girerek başla.';$('draft-list').append(p);}
    for(const d of drafts){const button=document.createElement('button');button.className=`draft-item${selected?.id===d.id?' selected':''}`;const title=document.createElement('strong');title.textContent=d.title;const status=document.createElement('small');status.textContent=`${labels[d.status]}${d.publishedSlug?' · Blogda onaylı sürüm var':''}`;button.append(title,status);button.onclick=()=>action(async()=>{if(dirty&&!confirm('Kaydedilmemiş değişiklikler var. Başka taslağa geçilsin mi?'))return;await open(d.id);});$('draft-list').append(button);}
  }
  function show(d) {
    selected=d;dirty=false;previewHash='';$('reviewed').checked=false;
    $('editor').hidden=false;$('empty-editor').hidden=true;
    for(const key of ['title','slug','description','body','coverAlt','coverPrompt'])$(key).value=d[key];
    $('tags').value=d.tags.join(', ');$('sources').value=d.sources.map(s=>`${s.title} | ${s.url}`).join('\n');
    $('slug').readOnly=!!d.publishedSlug;$('slug-hint').textContent=d.slug;
    $('editor-title').textContent=d.title;$('draft-status').textContent=labels[d.status];
    $('job-progress').hidden=!busy.has(d.status);$('job-progress').textContent=`${labels[d.status]}… Bu işlem birkaç dakika sürebilir. Paneli açık tut; taslak ilerledikçe kaydedilir.`;
    $('draft-error').hidden=!d.error;$('draft-error').textContent=d.error || '';
    $('cover').hidden=!d.coverUrl;$('no-cover').hidden=!!d.coverUrl;
    if(d.coverUrl){$('cover').src=d.coverUrl;$('cover').alt=d.coverAlt;}else $('cover').removeAttribute('src');
    for(const [id,items] of [['review-notes',d.reviewNotes || []],['publication-errors',d.publicationErrors]]){$(id).replaceChildren();for(const item of items){const li=document.createElement('li');li.textContent=item;$(id).append(li);}}
    $('approval-help').hidden=!d.publicationErrors.length;
    $('unpublish').hidden=!d.publishedSlug;
    $('publication-info').textContent=d.publishedSlug?`/blog/${d.publishedSlug}/ için onaylı sürüm var. Taslak değişiklikleri yeniden onaylanana kadar bu sürümü etkilemez. Onaylanan sürüm bu sitede görünür.`:'Onaylanan yazı blogun yayın dosyalarına eklenir. Onaylanan sürüm bu sitede görünür.';
    list();updateControls();
  }
  async function open(id){show(await api(`/api/drafts/${id}`));}
  async function refresh(poll=false) {
    const result=await api('/api/drafts');drafts=result.drafts;list();
    if(selected){const d=drafts.find(x=>x.id===selected.id);if(d && d.version!==selected.version && !dirty && !working)show(d);}
    updateControls();
  }
  function fields() {
    const value={version:selected.version};for(const key of ['title','slug','description','body','coverAlt','coverPrompt'])value[key]=$(key).value;
    value.tags=$('tags').value.split(',').map(t=>t.trim()).filter(Boolean);
    value.sources=$('sources').value.split('\n').filter(s=>s.trim()).map(line=>{const pos=line.indexOf('|');if(pos<1)throw new Error('Her kaynak satırını Başlık | https://adres biçiminde yaz.');return{title:line.slice(0,pos).trim(),url:line.slice(pos+1).trim()};});
    return value;
  }
  async function save() {
    if(!$('edit-form').reportValidity())throw new Error('İşaretli alanları düzelt.');
    const d=await api(`/api/drafts/${selected.id}`,'PUT',fields());
    drafts=drafts.map(x=>x.id===d.id?d:x);show(d);return d;
  }
  async function create(generate) {
    if(!$('create-form').reportValidity())return;
    if(dirty&&!confirm('Kaydedilmemiş değişiklikler var. Yeni taslak açılsın mı?'))return;
    let d=await api('/api/drafts','POST',{topic:$('topic').value,brief:$('brief').value});
    drafts.unshift(d);show(d);
    if(generate){d=await api(`/api/drafts/${d.id}/generate`,'POST',{version:d.version,mode:'all'});drafts=drafts.map(x=>x.id===d.id?d:x);show(d);feedback('Yazı ve kapak üretimi başladı.');}
    else feedback('Boş taslak oluşturuldu.');
  }
  $('create-form').onsubmit=e=>{e.preventDefault();action(()=>create(true));};
  $('create-manual').onclick=()=>action(()=>create(false));
  $('edit-form').onsubmit=e=>{e.preventDefault();action(async()=>{await save();feedback('Taslak kaydedildi. Yayındaki sürüm değişmedi.');});};
  $('edit-form').oninput=()=>{dirty=true;previewHash='';$('reviewed').checked=false;$('slug-hint').textContent=$('slug').value;updateControls();};
  $('refresh').onclick=()=>action(async()=>{if(dirty&&!confirm('Kaydedilmemiş değişiklikler kaybolacak. Son sürüm yüklensin mi?'))return;await refresh();if(selected)await open(selected.id);});
  for(const [id,mode] of [['regenerate-text','text'],['regenerate-cover','image']])$(id).onclick=()=>action(async()=>{
    if(!confirm(mode==='text'?'Taslak yazısı yeniden üretilecek ve mevcut taslak metni değişecek. API kullanımı oluşur. Devam edilsin mi?':'Yeni bir kapak üretimi API kullanımı oluşturur. Devam edilsin mi?'))return;
    await save();const d=await api(`/api/drafts/${selected.id}/generate`,'POST',{version:selected.version,mode});drafts=drafts.map(x=>x.id===d.id?d:x);show(d);feedback('Üretim başladı.');
  });
  $('preview-button').onclick=()=>action(async()=>{await save();const preview=await api(`/api/drafts/${selected.id}/preview`);$('preview-frame').srcdoc=preview.html;previewHash=preview.hash;$('preview-dialog').showModal();});
  $('close-preview').onclick=()=>{$('preview-dialog').close();updateControls();};
  $('preview-dialog').addEventListener('close',updateControls);
  $('reviewed').onchange=updateControls;
  $('approve').onclick=()=>action(async()=>{
    const d=await api(`/api/drafts/${selected.id}/approve`,'POST',{version:selected.version,hash:previewHash,reviewed:$('reviewed').checked});drafts=drafts.map(x=>x.id===d.id?d:x);show(d);feedback('Onayladığın sürüm bloga eklendi ve yayın dosyaları derlendi. Onaylanan sürüm bu sitede görünür.');
  });
  $('unpublish').onclick=()=>action(async()=>{if(!confirm('Yazı blogun yayın dosyalarından kaldırılsın mı? Taslağın saklanacak.'))return;const d=await api(`/api/drafts/${selected.id}/unpublish`,'POST',{version:selected.version});drafts=drafts.map(x=>x.id===d.id?d:x);show(d);feedback('Yazı blogdan kaldırıldı, taslak saklandı. Ziyaretçi sayfasından da kaldırıldı.');});
  window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
  (async()=>{try{const session=await api('/api/session');token=session.token;configured=session.configured;$('connection').textContent=configured?`Üretim bağlantısı tanımlı · ${session.provider || ''} · Yazı: ${session.textModel} · Kapak: ${session.imageModel}`:`Yapay zekâ üretimi için .env dosyasına ${session.keyName || 'NVIDIA_API_KEY'} ekleyip paneli yeniden başlat. Anahtarını bu sayfaya veya sohbete yazma. Boş taslak oluşturma ve düzenleme kullanılabilir.`;$('research-info').textContent=session.researchMode==='manual'?'NVIDIA modu: canlı web araştırması yapılmaz. Taslağın teknik bilgilerini doğrula; kaynak bağlantıları öneridir ve isteğe bağlıdır. Kapak üretimi için NVIDIA hesabında görsel modeline erişim gerekir.':'Bu sağlayıcıda web araştırması kullanılır. Kaynakları ve kod örneklerini yayın öncesinde kontrol et.';await refresh();}catch(e){feedback(e.message);}finally{updateControls();}})();
  setInterval(()=>{if(!working)refresh(true).catch(()=>feedback('Panele ulaşılamıyor. Taslak alanlarını açık tut; paneli yeniden başlatıp bağlantıyı kontrol et.'));},3000);
})();
