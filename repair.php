<?php
ini_set('display_errors','1');error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
echo '<meta charset="utf-8"><body style="font:16px sans-serif;padding:20px"><h2>Hafswa shop - check & repair</h2>';
try{ require 'config.php'; echo '<p>✅ Database connected: <b>'.DB_NAME.'</b></p>'; }
catch(Throwable $e){ exit('<p>❌ config.php error: '.htmlspecialchars($e->getMessage()).'</p>'); }
foreach(['categories','products','customers','orders','order_items','admins'] as $t){
  try{ echo "<p>$t: <b>".$db->query("SELECT COUNT(*) FROM $t")->fetchColumn()."</b> rows</p>"; }
  catch(Throwable $e){ echo "<p>❌ $t: ".htmlspecialchars($e->getMessage())."</p>"; }
}
if(!$db->query("SELECT COUNT(*) FROM products")->fetchColumn()){
  echo '<h3>No products found - adding samples...</h3>';
  foreach(['Women','Men','Home & Living','Beauty','Accessories'] as $n)$db->prepare("INSERT IGNORE INTO categories(name) VALUES(?)")->execute([$n]);
  $cid=[];foreach($db->query("SELECT id,name FROM categories") as $r)$cid[$r['name']]=$r['id'];
  $rows=[['Linen Summer Dress','Soft sage-green linen dress.',65000,'Women',12,'👗',1],['Floral Blouse','Light blouse in blush pink.',38000,'Women',20,'👚',0],['Linen Shirt','Breathable green linen shirt.',49000,'Men',15,'👔',1],['Minimal Sneakers','Clean white sneakers.',79000,'Men',9,'👟',1],['Scented Candle','Hand-poured vanilla candle.',26000,'Home & Living',30,'🕯️',0],['Ceramic Vase','Matte cream vase.',45000,'Home & Living',7,'🏺',0],['Face Care Set','Cleanser, serum and moisturiser set.',89000,'Beauty',14,'🧴',1],['Lip & Cheek Tint','Natural blush tint.',18000,'Beauty',40,'💄',0],['Straw Hat','Wide-brim straw hat.',35000,'Accessories',11,'👒',1],['Gold Hoops','Gold-tone hoop earrings.',19000,'Accessories',25,'💍',0],['Luxe Handbag','Beige handbag with gold clasp.',129000,'Accessories',5,'👜',1],['Sunglasses','Tortoise-frame sunglasses.',42000,'Accessories',0,'🕶️',0]];
  foreach($rows as $x){
    try{ $x[3]=$cid[$x[3]]??null; $db->prepare("INSERT INTO products(name,description,price,category_id,stock,emoji,featured) VALUES(?,?,?,?,?,?,?)")->execute($x); echo '<p>✅ '.htmlspecialchars($x[0]).'</p>'; }
    catch(Throwable $e){ echo '<p>❌ '.htmlspecialchars($x[0]).': '.htmlspecialchars($e->getMessage()).'</p>'; }
  }
}

echo '<h3>API test (what the shop page receives)</h3>';
$url='http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/api.php?a=products';
echo '<p><small>'.htmlspecialchars($url).'</small></p>';
$out=@file_get_contents($url);
if($out===false){echo '<p>❌ Could not open api.php</p>';}
else{$j=json_decode($out,true);
  if($j===null)echo '<p>❌ api.php did NOT return valid JSON. It returned:</p><pre style="background:#eee;padding:10px;white-space:pre-wrap">'.htmlspecialchars(substr($out,0,600)).'</pre>';
  else echo '<p>✅ API OK: <b>'.count($j['products']).'</b> products, <b>'.count($j['categories']).'</b> categories</p>';}
echo '<hr><p>Products now: <b>'.$db->query("SELECT COUNT(*) FROM products")->fetchColumn().'</b></p><p><a href="index.php">Open shop</a> &nbsp; <small>(delete repair.php when done)</small></p>';
