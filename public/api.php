<?php
require __DIR__.'/../app/core.php';
try {
 $action=$_GET['action']??'';
 if($action==='catalog' && $_SERVER['REQUEST_METHOD']==='GET') {
   $settings=read_data('settings');
   json_out(['ok'=>true,'categories'=>array_values(array_filter(read_data('categories'),fn($r)=>($r['active']??true))), 'products'=>array_values(array_filter(read_data('products'),fn($r)=>($r['active']??true))), 'templates'=>array_values(array_filter(read_data('templates'),fn($r)=>($r['active']??true))), 'settings'=>$settings, 'csrf'=>$_SESSION['csrf']]);
 }
 if($action==='design' && $_SERVER['REQUEST_METHOD']==='GET') {
   $id=safe_text($_GET['id']??'',35); foreach(read_data('designs') as $row) if($row['id']===$id) json_out(['ok'=>true,'design'=>$row]); json_out(['ok'=>false,'error'=>'Saved design not found'],404);
 }
 if($_SERVER['REQUEST_METHOD']!=='POST') throw new RuntimeException('Unsupported request');
 csrf_check();
 if($action==='upload') {
  if(is_rate_limited('upload',25,3600)) throw new RuntimeException('Upload rate limit reached');
  if(empty($_FILES['file']) || $_FILES['file']['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Upload unsuccessful');
  $f=$_FILES['file']; if($f['size']>3000000) throw new RuntimeException('Image must be under 3 MB');
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  $extensions=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
  if(!isset($extensions[$mime])) throw new RuntimeException('Only PNG, JPG or WEBP images are supported');
  $file=uid().'.'.$extensions[$mime]; if(!move_uploaded_file($f['tmp_name'],__DIR__.'/uploads/'.$file)) throw new RuntimeException('Unable to save image');
  json_out(['ok'=>true,'url'=>'uploads/'.$file]);
 }
 if($action==='save') {
  if(is_rate_limited('save',40,3600)) throw new RuntimeException('Save limit reached');
  $post=request_json(); $product=safe_text($post['product_id']??'',60);
  if(!in_array($product,array_column(read_data('products'),'id'),true)) throw new RuntimeException('Invalid product');
  $payload=$post['payload']??null; if(!is_array($payload)) throw new RuntimeException('Invalid design');
  $payload=normalize_design($payload);
  if(strlen(json_encode($payload))>350000) throw new RuntimeException('Design too large');
  $id=uid(); $entry=['id'=>$id,'product_id'=>$product,'payload'=>$payload,'created_at'=>gmdate('c')];
  update_data('designs',function (&$d) use($entry){$d[]=$entry;});
  json_out(['ok'=>true,'id'=>$id,'share_url'=>'?design='.$id]);
 }
 if($action==='order') {
  if(is_rate_limited('order',8,3600)) throw new RuntimeException('Too many submissions. Please try later.');
  $post=request_json();
  $first=safe_text($post['name']??'',90);$email=safe_text($post['email']??'',120);
  if(strlen($first)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Valid name and email required');
  $design=safe_text($post['design_id']??'',35);$found=null;
  foreach(read_data('designs') as $row) if($row['id']===$design) {$found=$row;break;}
  if(!$found) throw new RuntimeException('Save the design before ordering');
  $cleanRoster=[];foreach(array_slice(is_array($post['roster']??null)?$post['roster']:[],0,300) as $person){if(is_array($person))$cleanRoster[]=['name'=>safe_text($person['name']??'',40),'number'=>safe_text($person['number']??'',4),'size'=>safe_text($person['size']??'',30)];}
  $cleanSizes=[];foreach(array_slice(is_array($post['sizes']??null)?$post['sizes']:[],0,50) as $row){if(is_array($row))$cleanSizes[]=['size'=>safe_text($row['size']??'',30),'qty'=>max(0,min(10000,(int)($row['qty']??0)))];}
  $total=count($cleanRoster)?:array_sum(array_column($cleanSizes,'qty'));
  $settings=read_data('settings');$min=(int)($settings['minimum_order']??15);if($total<$min)throw new RuntimeException('Quantity is below the minimum order of '.$min);
  $entry=['id'=>strtoupper(substr(uid(),0,10)),'design_id'=>$design,'product_id'=>$found['product_id'],'name'=>$first,'email'=>$email,'phone'=>safe_text($post['phone']??'',60),'club'=>safe_text($post['club']??'',100),'notes'=>safe_text($post['notes']??'',800),'roster'=>$cleanRoster,'sizes'=>$cleanSizes,'status'=>'New','created_at'=>gmdate('c')];
  if(!is_array($entry['roster']) || count($entry['roster'])>300 || !is_array($entry['sizes']) || count($entry['sizes'])>60) throw new RuntimeException('Roster too large');
  update_data('orders',function (&$d) use($entry){$d[]=$entry;});
  json_out(['ok'=>true,'order_id'=>$entry['id']]);
 }
 throw new RuntimeException('Unknown action');
} catch(Throwable $e) {json_out(['ok'=>false,'error'=>$e->getMessage()],400);}
