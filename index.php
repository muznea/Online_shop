<?php
require 'config.php';
$cats=$db->query("SELECT c.id,c.name,(SELECT COUNT(*) FROM products WHERE category_id=c.id) n FROM categories c ORDER BY c.id")->fetchAll();
$prods=$db->query("SELECT p.*,c.name category FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC")->fetchAll();
foreach($prods as $k=>$x){
  $prods[$k]['id']=(int)$x['id'];$prods[$k]['price']=(float)$x['price'];$prods[$k]['stock']=(int)$x['stock'];
  $prods[$k]['available']=(int)$x['available'];$prods[$k]['featured']=(int)$x['featured'];$prods[$k]['category_id']=(int)$x['category_id'];
}
$em=['Women'=>'👗','Men'=>'👔','Home & Living'=>'🏺','Beauty'=>'🧴','Accessories'=>'💍'];
function pimg($p){return $p['image']?'<img src="'.h($p['image']).'" alt="'.h($p['name']).'">':h($p['emoji']?$p['emoji']:'🛍️');}
function card($p){
  $out=(!$p['available']||$p['stock']<1);
  $tag=$out?'<span class="tag out">Unavailable</span>':($p['stock']<=5?'<span class="tag">Only <b>'.$p['stock'].'</b> left</span>':'');
  return '<div class="card" data-id="'.$p['id'].'" data-cat="'.$p['category_id'].'" data-name="'.h($p['name']).'" data-price="'.$p['price'].'">'
  .'<div class="thumb" onclick="show('.$p['id'].')">'.pimg($p).$tag.'</div><div class="cb"><small>'.h($p['category']).'</small><h3>'.h($p['name']).'</h3>'
  .'<span class="price">'.h(money($p['price'])).'</span><small>'.($out?'Out of stock':'<b>'.$p['stock'].'</b> in stock').'</small>'
  .'<button class="btn sm"'.($out?' disabled style="opacity:.5"':'').' onclick="add('.$p['id'].')">Add to cart</button></div></div>';
}
$slides=array();
foreach($prods as $p){if($p['available']&&$p['stock']>0&&($p['featured']||$p['image']))$slides[]=$p;}
if(!$slides)$slides=$prods;
$slides=array_slice($slides,0,5);
$json=json_encode($prods,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);
if(!$json)$json='[]';
?><!doctype html><html lang="sw"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h(SHOP)?></title><link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=2"><script src="i18n.js"></script></head><body>
<div class="top"><span><i>🚚</i> Delivered to your door</span><span><i>💬</i> Easy ordering via WhatsApp</span><span><i>🔒</i> Safe &amp; simple ordering</span></div>
<header class="nav"><div class="wrap"><a class="logo" href="./"><i>🌿</i><span>HAFSWA<small>ONLINE SHOP</small></span></a>
<nav class="links"><a href="#shop">Shop</a><a href="#catsec">Categories</a><a href="#new">New Arrivals</a><a href="#sale">Top Picks</a><a href="https://wa.me/<?=WA?>" target="_blank" rel="noopener">Contact</a></nav>
<input class="search" id="q" type="search" placeholder="Search products by name…" aria-label="Search products">
<button class="btn line sm" data-lang onclick="setLang()" aria-label="Language">EN</button><button class="cartbtn" id="cartOpen" onclick="openCart()" aria-label="Open cart">🛍️<b id="cc">0</b></button></div></header>
<main class="wrap">
<div class="hero"><div><span class="eyebrow">NEW SEASON COLLECTION</span><h1>Live beautifully.<br><em>Shop effortlessly.</em></h1><p>Curated styles and everyday favourites, delivered to your door.</p>
<a class="btn" href="#shop">Shop now <span>→</span></a>
<div class="perks"><div><i>🚚</i><b>Delivery</b>To your door</div><div><i>🔒</i><b>Safe ordering</b>Via WhatsApp</div><div><i>🛍️</i><b>Easy ordering</b>No account needed</div><div><i>🎧</i><b>Support</b>We're here to help</div></div></div>
<div class="art" id="hero"><?php foreach($slides as $i=>$p){ ?><div class="sl<?=$i==0?' on':''?>" onclick="show(<?=$p['id']?>)"><?=$p['image']?pimg($p):'<span>'.h($p['emoji']?$p['emoji']:'🛍️').'</span>'?><div class="cap" onclick="event.stopPropagation()"><b><?=h($p['name'])?></b><span class="price"><?=h(money($p['price']))?></span><button class="btn sm" onclick="add(<?=$p['id']?>)">Add to cart</button></div></div><?php } ?>
<?php if(count($slides)>1){ ?><div class="dots"><?php foreach($slides as $i=>$p){ ?><button class="<?=$i==0?'on':''?>" onclick="goHero(<?=$i?>)" aria-label="Slide <?=$i+1?>"></button><?php } ?></div><?php } ?></div></div>
<section id="catsec"><div class="panelc"><div class="sh"><h2>Shop by category</h2><a onclick="setCat(0)">Browse all <span>→</span></a></div><div class="cats">
<?php foreach($cats as $c){ ?><button class="cat" data-id="<?=$c['id']?>" data-name="<?=h($c['name'])?>" onclick="setCat(<?=$c['id']?>)"><i><?=isset($em[$c['name']])?$em[$c['name']]:'🛍️'?></i><?=h($c['name'])?><small><b><?=$c['n']?></b> items</small></button><?php } ?></div></div></section>
<section id="sale"><div class="sale"><div><small>Limited time offer</small><h2>Spring picks are here</h2><p>Browse our featured favourites and order straight on WhatsApp.</p><a class="btn soft" href="#new">See featured</a></div><div class="r">🌸<b>NEW<br>SEASON</b></div></div></section>
<section id="new"><div class="sh"><h2>New Arrivals</h2><a href="#shop">View all <span>→</span></a></div><div class="grid five"><?php foreach(array_slice($prods,0,5) as $p)echo card($p); ?></div></section>
<section id="shop"><div class="sh"><h2 id="shopTitle" data-dyn>All products</h2><select id="sort" style="width:auto;padding:7px 12px"><option value="new">Latest first</option><option value="lo">Price: low to high</option><option value="hi">Price: high to low</option></select></div>
<div class="grid" id="list"><?php foreach($prods as $p)echo card($p); ?></div>
<p id="none" class="hide"><?=$prods?'No products found. Try another search or category.':'No products yet.'?></p></section>
<section><div class="join"><i>💬</i><div><h2>Join the Hafswa Circle</h2><p>Be the first to know about new arrivals and style inspiration.</p></div><a class="btn" href="https://wa.me/<?=WA?>" target="_blank" rel="noopener">Chat on WhatsApp</a></div></section>
<div class="trust"><div><i>✨</i><span><b>Quality Products</b><small>Carefully chosen for you.</small></span></div><div><i>🎧</i><span><b>Customer Support</b><small>We reply on WhatsApp.</small></span></div><div><i>🛍️</i><span><b>Simple Ordering</b><small>No account, no hassle.</small></span></div><div><i>🚚</i><span><b>Fast Delivery</b><small>Right to your door.</small></span></div></div>
</main>
<footer><div class="wrap"><div class="fg5"><div><div class="logo"><i>🌿</i><span>HAFSWA<small>ONLINE SHOP</small></span></div><p>Timeless style. Thoughtful shopping.</p><div class="soc"><a href="https://wa.me/<?=WA?>" target="_blank" rel="noopener" aria-label="WhatsApp">💬</a><a href="tel:+<?=WA?>" aria-label="Call">📞</a></div></div>
<div><h4>Shop</h4><a href="#shop" onclick="setCat(0)">All Products</a><?php foreach($cats as $c){ ?><a href="#shop" onclick="setCat(<?=$c['id']?>)"><?=h($c['name'])?></a><?php } ?></div>
<div><h4>Customer Service</h4><a href="https://wa.me/<?=WA?>" target="_blank" rel="noopener">Contact us</a><a href="tel:+<?=WA?>">+<?=WA?></a><a href="#shop">How to order</a></div>
<div><h4>Information</h4><p>Orders are confirmed on WhatsApp</p><p>Delivery to the address you provide</p><p>No account needed</p></div>
<div><h4>Payment</h4><div class="chips"><span>Cash on delivery</span><span>Mobile money</span></div><p style="margin-top:8px;font-size:12px">Confirm payment method on WhatsApp</p></div></div>
<div class="copy">© <span id="yr"></span> Hafswa Online Shop. All rights reserved.</div></div></footer>
<div class="mask" id="mask" onclick="closeAll()"></div><div class="modal" id="pm" data-dyn></div><aside class="drawer" id="dr" data-dyn aria-label="Shopping cart"></aside><div class="toast" id="toast"></div>
<script>
var CUR='<?=CUR?>',P=<?=$json?>,cat=0,cart=[],hi=0,ht,HERO_SECONDS=60; /* picha zinabadilika kila sekunde hizi (60 = kila dakika) */
try{cart=JSON.parse(localStorage.getItem('cart')||'[]')}catch(e){cart=[]}
function $(id){return document.getElementById(id)}
function m(n){return CUR+' '+Number(n).toLocaleString()}
function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function pf(id){return P.find(function(p){return p.id==id})}
function toast(t){var e=$('toast');e.textContent=t;e.style.display='block';setTimeout(function(){e.style.display='none'},1800)}
function pimgJS(p,sz){return p.image?'<img src="'+esc(p.image)+'" alt="" '+(sz?'width="'+sz+'" height="'+sz+'" style="border-radius:10px;object-fit:cover"':'')+'>':esc(p.emoji||'🛍️')}
function save(){try{localStorage.setItem('cart',JSON.stringify(cart))}catch(e){}$('cc').textContent=cart.reduce(function(a,b){return a+b.qty},0)}
function add(id){var p=pf(id);if(!p||!p.available||p.stock<1)return;var i=cart.find(function(x){return x.id==id});if(i){if(i.qty>=p.stock)return toast(tr('Maximum stock reached'));i.qty++}else cart.push({id:id,qty:1});save();toast(tr('Added to cart'))}
function chg(id,d){var p=pf(id),i=cart.find(function(x){return x.id==id});i.qty=Math.min(p.stock,i.qty+d);if(i.qty<1)cart=cart.filter(function(x){return x.id!=id});save();openCart()}
function rm(id){cart=cart.filter(function(x){return x.id!=id});save();openCart()}
function closeAll(){['pm','dr','mask'].forEach(function(i){$(i).classList.remove('on')})}
function show(id){var p=pf(id),out=!p.available||p.stock<1;$('pm').innerHTML='<div class="thumb">'+pimgJS(p)+'</div><div class="in"><small>'+esc(p.category)+'</small><h2>'+esc(p.name)+'</h2><p class="price" style="font-size:22px">'+m(p.price)+'</p><p>'+esc(p.description)+'</p><p><b>'+(out?tr('Out of stock'):tr('Stock')+': '+p.stock)+'</b></p><button class="btn" '+(out?'disabled':'')+' onclick="add('+p.id+');closeAll()">'+tr('Add to cart')+'</button> <button class="btn line" onclick="closeAll()">'+tr('Close')+'</button></div>';$('pm').classList.add('on');$('mask').classList.add('on')}
function openCart(){cart=cart.filter(function(i){return pf(i.id)});save();var tot=0;
 var rows=cart.map(function(i){var p=pf(i.id);tot+=p.price*i.qty;return '<div class="row"><div style="font-size:30px">'+pimgJS(p,48)+'</div><div class="grow"><b>'+esc(p.name)+'</b><br><small>'+m(p.price)+'</small></div><div class="q"><button onclick="chg('+p.id+',-1)" aria-label="Decrease">−</button><span>'+i.qty+'</span><button onclick="chg('+p.id+',1)" aria-label="Increase">+</button></div><button class="btn sm red" onclick="rm('+p.id+')" aria-label="Remove">✕</button></div>'}).join('');
 $('dr').innerHTML='<div class="sh"><h2>'+tr('Your cart')+'</h2><button class="btn line sm" onclick="closeAll()">'+tr('Close')+'</button></div>'+(cart.length?rows+'<h3 style="margin:16px 0">'+tr('Total')+': '+m(tot)+'</h3><input id="cn" placeholder="'+tr('Your name')+'" autocomplete="name"><br><br><input id="cp" placeholder="'+tr('Phone number')+'" type="tel" autocomplete="tel"><br><br><textarea id="ca" rows="3" placeholder="'+tr('Delivery address / location details')+'"></textarea><p class="err" id="ce"></p><button class="btn" style="width:100%" onclick="order()">'+tr('Place order')+'</button>':'<p>'+tr('Your cart is empty. Add something you love.')+'</p>');
 $('dr').classList.add('on');$('mask').classList.add('on')}
function errTr(e){var k='is not available in that quantity.';return e.slice(-k.length)==k?e.slice(0,-k.length)+tr(k):tr(e)}
function order(){var phone=$('cp').value.trim();if(!/^[0-9+ ()-]{7,20}$/.test(phone)){$('ce').textContent=L==='sw'?'Namba ya simu si sahihi. Mfano: +255712345678.':'Enter a valid phone number, e.g. +255712345678.';$('cp').focus();return}var b={name:$('cn').value,phone:phone,address:$('ca').value,items:cart};
 fetch('api.php?a=order',{method:'POST',body:JSON.stringify(b)}).then(function(r){return r.text().then(function(t){var d;try{d=JSON.parse(t)}catch(e){d={error:t.slice(0,200)||'Server error'}}return {ok:r.ok,d:d}})}).then(function(x){
  if(!x.ok){$('ce').textContent=errTr(x.d.error||'Error');return}
  var d=x.d;cart=[];save();
  $('dr').innerHTML='<h2>'+tr('Order')+' #'+d.id+' '+tr('placed')+' ✅</h2><p>'+tr('Your order is saved.')+' '+tr('Total')+': <b>'+m(d.total)+'</b>.</p><p>'+tr('Tap the button to send the order details to our WhatsApp so we can confirm and deliver.')+'</p><a class="btn" style="background:#25d366;width:100%;text-align:center" href="'+d.wa+'" target="_blank" rel="noopener">'+tr('Send order on WhatsApp')+'</a><br><br><button class="btn line" onclick="location.reload()">'+tr('Continue shopping')+'</button>'}).catch(function(e){$('ce').textContent=String(e)})}
function filter(){var q=$('q').value.trim().toLowerCase(),n=0;
 document.querySelectorAll('#list .card').forEach(function(c){var ok=(!cat||c.dataset.cat==cat)&&c.dataset.name.toLowerCase().indexOf(q)>-1;c.classList.toggle('hide',!ok);if(ok)n++});
 $('none').classList.toggle('hide',n>0||!P.length);
 document.querySelectorAll('.cat').forEach(function(b){b.classList.toggle('on',b.dataset.id==cat)});
 var cb=document.querySelector('.cat[data-id="'+cat+'"]');$('shopTitle').textContent=(cat&&cb)?cb.dataset.name:tr('All products')}
function setCat(i){cat=i;filter();$('shop').scrollIntoView({behavior:'smooth'})}
function sortList(){var l=$('list'),s=$('sort').value,a=Array.prototype.slice.call(l.querySelectorAll('.card'));
 a.sort(function(x,y){return s=='lo'?x.dataset.price-y.dataset.price:s=='hi'?y.dataset.price-x.dataset.price:y.dataset.id-x.dataset.id});a.forEach(function(c){l.appendChild(c)})}
function goHero(i){hi=i;document.querySelectorAll('#hero .sl').forEach(function(e,k){e.classList.toggle('on',k==i)});document.querySelectorAll('#hero .dots button').forEach(function(e,k){e.classList.toggle('on',k==i)})}
window.onLang=function(){filter();if($('dr').classList.contains('on'))openCart()};
$('q').oninput=filter;$('sort').onchange=sortList;$('yr').textContent=new Date().getFullYear();
var nS=document.querySelectorAll('#hero .sl').length;if(nS>1)ht=setInterval(function(){goHero((hi+1)%nS)},HERO_SECONDS*1000);
save();filter();applyLang();
</script></body></html>
