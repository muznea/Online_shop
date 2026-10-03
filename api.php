<?php
ini_set('display_errors','0');error_reporting(E_ALL);ob_start();
require 'config.php'; ob_clean();header('Content-Type: application/json');header('Cache-Control: no-store');
$a=$_GET['a']??'';
if($a==='products'){
  $pr=$db->query("SELECT p.*,c.name category FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC")->fetchAll();
  foreach($pr as &$x){$x['price']=(float)$x['price'];foreach(['id','stock','available','featured','category_id'] as $k)$x[$k]=(int)$x[$k];}unset($x);
  echo json_encode(['products'=>$pr,
  'categories'=>$db->query("SELECT c.id,c.name,(SELECT COUNT(*) FROM products WHERE category_id=c.id) n FROM categories c")->fetchAll()]);exit;
}
if($a==='order' && $_SERVER['REQUEST_METHOD']==='POST'){
  $d=json_decode(file_get_contents('php://input'),true);
  $name=trim($d['name']??'');$phone=trim($d['phone']??'');$addr=trim($d['address']??'');
  if(!$name||!preg_match('/^[0-9+ ()-]{7,20}$/',$phone)||!$addr||empty($d['items'])){http_response_code(422);echo json_encode(['error'=>'Please fill in name, a valid phone number and delivery details.']);exit;}
  try{
    $db->beginTransaction();$total=0;$lines=[];
    foreach($d['items'] as $it){
      $q=max(1,(int)$it['qty']);$p=$db->prepare("SELECT * FROM products WHERE id=?");$p->execute([(int)$it['id']]);$p=$p->fetch();
      if(!$p||!$p['available']||$p['stock']<$q)throw new Exception(($p['name']??'A product').' is not available in that quantity.');
      $total+=$p['price']*$q;$lines[]=[$p,$q];
    }
    $c=$db->prepare("SELECT id FROM customers WHERE phone=? AND name=?");$c->execute([$phone,$name]);$cid=$c->fetchColumn();
    if(!$cid){$db->prepare("INSERT INTO customers(name,phone) VALUES(?,?)")->execute([$name,$phone]);$cid=$db->lastInsertId();}
    $db->prepare("INSERT INTO orders(customer_id,address,total) VALUES(?,?,?)")->execute([$cid,$addr,$total]);$oid=$db->lastInsertId();
    $msg="*New order #$oid - ".SHOP."*\nName: $name\nPhone: $phone\n\n*Products:*\n";
    foreach($lines as [$p,$q]){
      $db->prepare("INSERT INTO order_items(order_id,product_id,name,price,qty) VALUES(?,?,?,?,?)")->execute([$oid,$p['id'],$p['name'],$p['price'],$q]);
      $db->prepare("UPDATE products SET stock=stock-? WHERE id=?")->execute([$q,$p['id']]);
      $msg.="- {$p['name']} x$q @ ".money($p['price'])." = ".money($p['price']*$q)."\n";
    }
    $msg.="\n*Total: ".money($total)."*\nDelivery/location: $addr";
    $db->commit();
    echo json_encode(['id'=>$oid,'total'=>$total,'wa'=>'https://wa.me/'.WA.'?text='.rawurlencode($msg)]);
  }catch(Exception $e){if($db->inTransaction())$db->rollBack();http_response_code(422);echo json_encode(['error'=>$e->getMessage()]);}
  exit;
}
http_response_code(404);echo '{}';
