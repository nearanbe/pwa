(function(){var d=document;
d.querySelectorAll('.tabs').forEach(function(w){var bs=[].slice.call(w.querySelectorAll('.tab'));
function sel(b,focus){bs.forEach(function(x){var on=x===b;x.setAttribute('aria-selected',on);x.tabIndex=on?0:-1;var p=d.getElementById('p-'+x.dataset.i);if(p)p.hidden=!on;});if(focus)b.focus();if(w.scrollWidth>w.clientWidth)b.scrollIntoView({block:'nearest',inline:'center',behavior:'smooth'});}
bs.forEach(function(b,i){b.tabIndex=b.getAttribute('aria-selected')==='true'?0:-1;b.addEventListener('click',function(){sel(b)});
b.addEventListener('keydown',function(e){var k=e.key,n=null;if(k==='ArrowDown'||k==='ArrowRight')n=bs[(i+1)%bs.length];else if(k==='ArrowUp'||k==='ArrowLeft')n=bs[(i-1+bs.length)%bs.length];else if(k==='Home')n=bs[0];else if(k==='End')n=bs[bs.length-1];if(n){e.preventDefault();sel(n,true);}});});});
var m=d.getElementById('menu'),n=d.getElementById('nav');if(m&&n){m.addEventListener('click',function(){var o=n.classList.toggle('open');m.setAttribute('aria-expanded',o);m.setAttribute('aria-label',o?'Menüyü kapat':'Menüyü aç');});}
var ph=d.getElementById('phone'),cc=d.getElementById('cc');
function fmt(){var v=ph.value.replace(/\D/g,'');if(cc.value==='90'){v=v.replace(/^0/,'').slice(0,10);var o='';if(v.length){o='('+v.slice(0,3);if(v.length>3)o+=') '+v.slice(3,6);if(v.length>6)o+=' '+v.slice(6,8);if(v.length>8)o+=' '+v.slice(8,10);}ph.value=o;ph.placeholder='(5xx) xxx xx xx';}else{ph.value=v.slice(0,14);ph.placeholder='Telefon numaranız';}}
if(ph&&cc){ph.addEventListener('input',fmt);cc.addEventListener('change',fmt);if(ph.value)fmt();}
})();
(function(){var d=document;
d.querySelectorAll('.vid-in[data-yt]').forEach(function(a){a.addEventListener('click',function(e){e.preventDefault();var f=d.createElement('iframe');f.src='https://www.youtube-nocookie.com/embed/'+a.dataset.yt+'?autoplay=1&rel=0';f.title=a.querySelector('img').alt;f.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';f.allowFullscreen=true;a.replaceWith(Object.assign(d.createElement('div'),{className:'vid-in'}));d.querySelector('.vid .vid-in').appendChild(f);});});
d.querySelectorAll('.share .copy').forEach(function(b){b.addEventListener('click',function(){var u=b.dataset.url;(navigator.clipboard?navigator.clipboard.writeText(u):Promise.reject()).then(function(){b.textContent='Kopyalandı ✓';},function(){prompt('Bağlantı:',u);});});});
})();
