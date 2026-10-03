<?php
session_start();
define('SHOP','Hafswa Online Shop'); define('WA','255683285852'); define('CUR','TSh');
/* ---- MySQL settings (XAMPP defaults: user root, empty password) ---- */
const DB_HOST='localhost', DB_USER='root', DB_PASS='', DB_NAME='hafswa_shop';
@mkdir(__DIR__.'/uploads',0755,true);
try{
  $opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
  (new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,$opt))->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $db=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,$opt);
}catch(PDOException $e){http_response_code(500);exit('Database connection failed. Start MySQL in XAMPP and check the settings at the top of config.php.');}
foreach([
"CREATE TABLE IF NOT EXISTS admins(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(80) UNIQUE,password VARCHAR(255)) ENGINE=InnoDB",
"CREATE TABLE IF NOT EXISTS categories(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) UNIQUE) ENGINE=InnoDB",
"CREATE TABLE IF NOT EXISTS products(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(255),description TEXT,price DECIMAL(12,2),category_id INT NULL,stock INT DEFAULT 0,available TINYINT DEFAULT 1,image VARCHAR(255),emoji VARCHAR(20),featured TINYINT DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL) ENGINE=InnoDB",
"CREATE TABLE IF NOT EXISTS customers(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(150),phone VARCHAR(30),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB",
"CREATE TABLE IF NOT EXISTS orders(id INT AUTO_INCREMENT PRIMARY KEY,customer_id INT,address TEXT,total DECIMAL(12,2),status VARCHAR(20) DEFAULT 'Pending',created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(customer_id) REFERENCES customers(id)) ENGINE=InnoDB",
"CREATE TABLE IF NOT EXISTS order_items(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT,product_id INT NULL,name VARCHAR(255),price DECIMAL(12,2),qty INT,FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB"] as $q)$db->exec($q);
$db->exec("CREATE TABLE IF NOT EXISTS settings(k VARCHAR(50) PRIMARY KEY,v VARCHAR(255)) ENGINE=InnoDB");
/* Sample data: added once (also repairs a database that has categories but no products) */
if(!$db->query("SELECT COUNT(*) FROM settings WHERE k='seeded'")->fetchColumn()){
  foreach(['Women','Men','Home & Living','Beauty','Accessories'] as $n)$db->prepare("INSERT IGNORE INTO categories(name) VALUES(?)")->execute([$n]);
  $cid=[];foreach($db->query("SELECT id,name FROM categories") as $r)$cid[$r['name']]=$r['id'];
  if(!$db->query("SELECT COUNT(*) FROM products")->fetchColumn()){
    $rows=[['Linen Summer Dress','Soft sage-green linen dress with a flattering ruched front.',65000,'Women',12,'👗',1],
    ['Floral Blouse','Light blouse in blush pink, perfect for everyday wear.',38000,'Women',20,'👚',0],
    ['Linen Shirt','Breathable green linen shirt, relaxed fit.',49000,'Men',15,'👔',1],
    ['Minimal Sneakers','Clean white sneakers, comfortable all day.',79000,'Men',9,'👟',1],
    ['Scented Candle','Hand-poured vanilla candle, 40 hours burn time.',26000,'Home & Living',30,'🕯️',0],
    ['Ceramic Vase','Matte cream vase for fresh or dried flowers.',45000,'Home & Living',7,'🏺',0],
    ['Face Care Set','Cleanser, serum and moisturiser gift set.',89000,'Beauty',14,'🧴',1],
    ['Lip & Cheek Tint','Natural blush tint, long lasting.',18000,'Beauty',40,'💄',0],
    ['Straw Hat','Wide-brim straw hat with black ribbon.',35000,'Accessories',11,'👒',1],
    ['Gold Hoops','Lightweight gold-tone hoop earrings.',19000,'Accessories',25,'💍',0],
    ['Luxe Handbag','Beige leather-look handbag with gold clasp.',129000,'Accessories',5,'👜',1],
    ['Sunglasses','Classic tortoise-frame sunglasses.',42000,'Accessories',0,'🕶️',0]];
    foreach($rows as $x){$x[3]=$cid[$x[3]];$db->prepare("INSERT INTO products(name,description,price,category_id,stock,emoji,featured) VALUES(?,?,?,?,?,?,?)")->execute($x);}
  }
  $db->exec("INSERT IGNORE INTO settings(k,v) VALUES('seeded','1')");
}
function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function money($n){return CUR.' '.number_format((float)$n);}
function is_admin(){return !empty($_SESSION['admin']);}
function csrf(){return $_SESSION['csrf']??($_SESSION['csrf']=bin2hex(random_bytes(16)));}
