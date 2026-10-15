<?php
require __DIR__.'/../app/core.php';
if(installed()){header('Location: admin.php'); exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 try { csrf_check(); $user=safe_text($_POST['username']??'',60); $pass=(string)($_POST['password']??'');
   if(strlen($user)<3 || strlen($pass)<12) throw new RuntimeException('Use a username and a password of at least 12 characters.');
   $record=['username'=>$user,'password_hash'=>password_hash($pass,PASSWORD_DEFAULT),'created_at'=>gmdate('c')];
   $path=KITLAB_STORAGE.'/admin.json'; $handle=@fopen($path,'x');
   if(!$handle) throw new RuntimeException('Already installed.');
   fwrite($handle,json_encode($record,JSON_PRETTY_PRINT)); fclose($handle); chmod($path,0600);
   header('Location: admin.php');exit;
 } catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>KitLab Setup</title><link rel="stylesheet" href="assets/styles.css"><body class="auth-page"><main class="auth-card"><div class="brand"><span class="brand-mark">W</span><div>WINSUN <b>KITLAB</b><small>FIRST-TIME INSTALLATION</small></div></div><h1>Secure your studio</h1><p>Set an administrator login. This page is disabled automatically after installation.</p><?php if($error):?><div class="alert"><?=h($error)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=h($_SESSION['csrf'])?>"><label>Username<input name="username" required minlength="3" autocomplete="username" placeholder="Admin username"></label><label>Strong password<input type="password" name="password" required minlength="12" autocomplete="new-password" placeholder="12+ characters"></label><button class="btn primary full">Install KitLab</button></form></main></body></html>
