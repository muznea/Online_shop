<?php require 'config.php';
$S=['Pending','Confirmed','Processing','Delivered','Cancelled'];$msg='';$err='';
if(isset($_GET['logout'])){session_destroy();header('Location: admin.php');exit;}
$hasAdmin=(int)$db->query("SELECT COUNT(*) FROM admins")->fetchColumn()>0;
if(!$hasAdmin&&isset($_POST['register'])){
  $u=trim($_POST['username']);$pw=$_POST['password'];
  if(strlen($u)<3)$err='Username must be at least 3 characters.';
  elseif(strlen($pw)<8)$err='Password must be at least 8 characters.';
  elseif($pw!==$_POST['password2'])$err='Passwords do not match.';
  else{$db->prepare("INSERT INTO admins(username,password) VALUES(?,?)")->execute([$u,password_hash($pw,PASSWORD_DEFAULT)]);session_regenerate_id(true);$_SESSION['admin']=$db->lastInsertId();header('Location: admin.php');exit;}
}
if($hasAdmin&&isset($_POST['login'])){
  $u=$db->prepare("SELECT * FROM admins WHERE username=?");$u->execute([trim($_POST['username'])]);$u=$u->fetch();
  if($u&&password_verify($_POST['password'],$u['password'])){session_regenerate_id(true);$_SESSION['admin']=$u['id'];header('Location: admin.php');exit;}
  sleep(1);$err='Wrong username or password.';
}
if(!is_admin()){?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&family=Fraunces:wght@600&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"><script src="i18n.js"></script></head><body>
<form class="login" method="post"><button type="button" class="btn line sm" data-lang onclick="setLang()" style="float:right">EN</button><h1 class="logo">🌿 <?=h(SHOP)?></h1>
<?php if(!$hasAdmin):?><p>Create admin account</p><p><small>First-time setup: register the shop admin. This page closes after the first account is created.</small></p>
<input name="username" placeholder="Username" required autofocus><br><br><input name="password" type="password" placeholder="Password (min 8 characters)" required><br><br><input name="password2" type="password" placeholder="Confirm password" required>
<p class="err"><?=h($err)?></p><button class="btn" name="register" value="1" style="width:100%">Create account</button>
<?php else:?><p>Admin login</p><input name="username" placeholder="Username" required autofocus><br><br><input name="password" type="password" placeholder="Password" required>
<p class="err"><?=h($err)?></p><button class="btn" name="login" value="1" style="width:100%">Log in</button><?php endif;?></form><script>applyLang()</script></body></html><?php exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals(csrf(),$_POST['csrf']??'')){http_response_code(403);exit('Invalid request');}
  $a=$_POST['act']??'';
  if($a==='pw'){$r=$db->prepare("SELECT password FROM admins WHERE id=?");$r->execute([$_SESSION['admin']]);
    if(!password_verify($_POST['old'],$r->fetchColumn()))$msg='Current password is wrong.';elseif(strlen($_POST['new'])<8)$msg='Password must be at least 8 characters.';
    else{$db->prepare("UPDATE admins SET password=? WHERE id=?")->execute([password_hash($_POST['new'],PASSWORD_DEFAULT),$_SESSION['admin']]);$msg='Password changed.';}}
  if($a==='addadmin'){$u=trim($_POST['username']);if(strlen($u)<3)$msg='Username must be at least 3 characters.';elseif(strlen($_POST['password'])<8)$msg='Password must be at least 8 characters.';
    else try{$db->prepare("INSERT INTO admins(username,password) VALUES(?,?)")->execute([$u,password_hash($_POST['password'],PASSWORD_DEFAULT)]);$msg='Admin added.';}catch(Exception $e){$msg='Username already exists.';}}
  if($a==='save'){
    $cn=trim($_POST['category_new']??'');$cid=(int)$_POST['category_id'];
    if($cn){$db->prepare("INSERT IGNORE INTO categories(name) VALUES(?)")->execute([$cn]);$s=$db->prepare("SELECT id FROM categories WHERE name=?");$s->execute([$cn]);$cid=$s->fetchColumn();}
    $img=$_POST['old_image']??'';
    if(!empty($_FILES['image']['tmp_name'])&&$_FILES['image']['error']===0){
      $ext=strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));
      if(in_array($ext,['jpg','jpeg','png','webp','gif'])&&getimagesize($_FILES['image']['tmp_name'])&&$_FILES['image']['size']<5e6){
        $f='uploads/'.bin2hex(random_bytes(8)).'.'.$ext;move_uploaded_file($_FILES['image']['tmp_name'],__DIR__.'/'.$f);$img=$f;
      }else $msg='Image must be JPG, PNG, WEBP or GIF under 5MB.';
    }
    $v=[trim($_POST['name']),trim($_POST['description']),(float)$_POST['price'],$cid,max(0,(int)$_POST['stock']),isset($_POST['available'])?1:0,isset($_POST['featured'])?1:0,$img,trim($_POST['emoji'])?:'🛍️'];
    if(!empty($_POST['id'])){$v[]=(int)$_POST['id'];$db->prepare("UPDATE products SET name=?,description=?,price=?,category_id=?,stock=?,available=?,featured=?,image=?,emoji=? WHERE id=?")->execute($v);$msg=$msg?:'Product updated.';}
    else{$db->prepare("INSERT INTO products(name,description,price,category_id,stock,available,featured,image,emoji) VALUES(?,?,?,?,?,?,?,?,?)")->execute($v);$msg=$msg?:'Product added.';}
  }
  if($a==='del'){$db->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]);$msg='Product deleted.';}
  if($a==='status'&&in_array($_POST['status'],$S)){$db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$_POST['status'],(int)$_POST['id']]);$msg='Order status updated.';}
}
$p=$_GET['p']??'dash';$cats=$db->query("SELECT * FROM categories")->fetchAll();
$one=fn($sql)=>$db->query($sql)->fetchColumn();
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - <?=h(SHOP)?></title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:wght@600&display=swap" rel="stylesheet"><link rel="stylesheet" href="style.css"><script src="i18n.js"></script></head><body>
<div class="adm"><nav class="side"><span class="logo">🌿 Admin</span>
<?php foreach(['dash'=>'Dashboard','products'=>'Products','orders'=>'Orders','settings'=>'Settings'] as $k=>$l)echo '<a class="'.($p==$k?'on':'').'" href="?p='.$k.'">'.$l.'</a>';?>
<a href="./" target="_blank">View shop</a><a href="?logout=1">Log out</a><button class="btn soft sm" data-lang onclick="setLang()">EN</button></nav><div class="main">
<?php if($msg)echo '<div class="panel" style="background:var(--sage)">'.h($msg).'</div>';
if($p==='dash'):
 $st=['tp'=>$one("SELECT COUNT(*) FROM products"),'ap'=>$one("SELECT COUNT(*) FROM products WHERE available=1 AND stock>0"),'to'=>$one("SELECT COUNT(*) FROM orders"),'po'=>$one("SELECT COUNT(*) FROM orders WHERE status='Pending'"),'do'=>$one("SELECT COUNT(*) FROM orders WHERE status='Delivered'"),'sv'=>$one("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='Cancelled'")];?>
<h1>Dashboard</h1><div class="stats"><?php foreach([['Total products',$st['tp']],['Available products',$st['ap']],['Total orders',$st['to']],['Pending orders',$st['po']],['Completed orders',$st['do']],['Total sales',money($st['sv'])]] as [$l,$v])echo "<div class='stat'><b>".h($v)."</b>$l</div>";?></div>
<div class="panel"><h3>Orders by status</h3><br><div class="bars"><?php $mx=1;$cnt=[];foreach($S as $s){$cnt[$s]=(int)$one("SELECT COUNT(*) FROM orders WHERE status='$s'");$mx=max($mx,$cnt[$s]);}
foreach($S as $s)echo "<div><b>{$cnt[$s]}</b><i style='height:".round($cnt[$s]/$mx*110)."px'></i>$s</div>";?></div></div>
<div class="panel"><h3>Sales, last 7 days</h3><br><div class="bars"><?php $days=[];$mx=1;for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i day"));$days[$d]=(float)$one("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='Cancelled' AND date(created_at)='$d'");$mx=max($mx,$days[$d]);}
foreach($days as $d=>$v)echo "<div>".number_format($v)."<i style='height:".round($v/$mx*110)."px'></i>".date('D',strtotime($d))."</div>";?></div></div>
<?php elseif($p==='products'):
 $e=null;if(!empty($_GET['edit'])){$s=$db->prepare("SELECT * FROM products WHERE id=?");$s->execute([(int)$_GET['edit']]);$e=$s->fetch();}
 $q=trim($_GET['q']??'');$c=(int)($_GET['c']??0);
 $s=$db->prepare("SELECT p.*,c.name cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.name LIKE ? AND (?=0 OR p.category_id=?) ORDER BY p.id DESC");$s->execute(["%$q%",$c,$c]);$L=$s->fetchAll();?>
<h1><?=$e?'Edit product':'Add product'?></h1><form class="panel" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="act" value="save"><input type="hidden" name="id" value="<?=h($e['id']??'')?>"><input type="hidden" name="old_image" value="<?=h($e['image']??'')?>">
<div class="fg"><div><label>Name</label><input name="name" required value="<?=h($e['name']??'')?>"></div><div><label>Price (<?=CUR?>)</label><input name="price" type="number" step="any" min="0" required value="<?=h($e['price']??'')?>"></div>
<div><label>Stock quantity</label><input name="stock" type="number" min="0" required value="<?=h($e['stock']??0)?>"></div>
<div><label>Category</label><select name="category_id"><?php foreach($cats as $x)echo "<option value='{$x['id']}'".(($e['category_id']??0)==$x['id']?' selected':'').">".h($x['name'])."</option>";?></select></div>
<div><label>Or new category</label><input name="category_new" placeholder="Type to create"></div><div><label>Emoji (shown if no image)</label><input name="emoji" value="<?=h($e['emoji']??'')?>"></div>
<div><label>Image (upload/change)</label><input type="file" name="image" accept="image/*"></div></div><br><label>Description</label><textarea name="description" rows="3"><?=h($e['description']??'')?></textarea><br><br>
<label><input type="checkbox" name="available" style="width:auto" <?=!$e||$e['available']?'checked':''?>> Available</label> &nbsp; <label><input type="checkbox" name="featured" style="width:auto" <?=!empty($e['featured'])?'checked':''?>> Featured</label><br><br>
<button class="btn"><?=$e?'Save changes':'Add product'?></button> <?php if($e)echo '<a class="btn line" href="?p=products">Cancel</a>';?></form>
<form class="panel" method="get"><input type="hidden" name="p" value="products"><div class="fg"><input name="q" placeholder="Search products" value="<?=h($q)?>"><select name="c"><option value="0">All categories</option><?php foreach($cats as $x)echo "<option value='{$x['id']}'".($c==$x['id']?' selected':'').">".h($x['name'])."</option>";?></select><button class="btn">Filter</button></div></form>
<div class="panel"><table><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
<?php foreach($L as $x)echo '<tr><td>'.($x['image']?'<img src="'.h($x['image']).'" width="44" height="44" style="border-radius:8px;object-fit:cover">':h($x['emoji'])).'</td><td>'.h($x['name']).'</td><td>'.h($x['cat']).'</td><td>'.money($x['price']).'</td><td>'.$x['stock'].'</td><td>'.($x['available']?'Available':'Unavailable').'</td><td style="white-space:nowrap"><a class="btn sm line" href="?p=products&edit='.$x['id'].'">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm(tr(\'Delete this product?\'))"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="'.$x['id'].'"><button class="btn sm red">Delete</button></form></td></tr>';?></table></div>
<?php elseif($p==='settings'):?>
<h1>Settings</h1><form class="panel" method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="act" value="pw"><h3>Change password</h3><br><div class="fg"><input type="password" name="old" placeholder="Current password" required><input type="password" name="new" placeholder="New password (min 8 characters)" required></div><br><button class="btn">Save password</button></form>
<form class="panel" method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="act" value="addadmin"><h3>Add another admin</h3><br><div class="fg"><input name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password (min 8 characters)" required></div><br><button class="btn">Add admin</button></form>
<?php else:
 $O=$db->query("SELECT o.*,c.name cname,c.phone FROM orders o LEFT JOIN customers c ON c.id=o.customer_id ORDER BY o.id DESC")->fetchAll();?>
<h1>Orders</h1><div class="panel"><table><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Delivery details</th><th>Status</th></tr>
<?php foreach($O as $o){$it=$db->prepare("SELECT * FROM order_items WHERE order_id=?");$it->execute([$o['id']]);$li='';foreach($it as $i)$li.=h($i['name']).' × '.$i['qty'].' ('.money($i['price']).')<br>';
echo '<tr><td>'.$o['id'].'<br><small>'.h(substr($o['created_at'],0,16)).'</small></td><td>'.h($o['cname']).'<br><a href="tel:'.h($o['phone']).'">'.h($o['phone']).'</a></td><td>'.$li.'</td><td><b>'.money($o['total']).'</b></td><td>'.nl2br(h($o['address'])).'</td><td><form method="post"><input type="hidden" name="csrf" value="'.csrf().'"><input type="hidden" name="act" value="status"><input type="hidden" name="id" value="'.$o['id'].'"><select name="status" onchange="this.form.submit()">';
foreach($S as $s)echo "<option value='$s'".($o['status']==$s?' selected':'').">$s</option>";echo '</select></form></td></tr>';}
if(!$O)echo '<tr><td colspan="6">No orders yet. Orders appear here after customers check out.</td></tr>';?></table></div>
<?php endif;?></div></div><script>applyLang()</script></body></html>
