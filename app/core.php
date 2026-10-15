<?php
declare(strict_types=1);
const KITLAB_ROOT = __DIR__ . '/..';
const KITLAB_STORAGE = KITLAB_ROOT . '/storage';
function boot(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>'/']);
        session_start();
    }
    if (!is_dir(KITLAB_STORAGE)) mkdir(KITLAB_STORAGE, 0750, true);
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    seed_catalog();
}
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function read_data(string $name): array {
    if (!preg_match('/^[a-z_]+$/', $name)) throw new Exception('Invalid collection');
    $path=KITLAB_STORAGE.'/'.$name.'.json';
    if (!is_file($path)) return [];
    $fp=fopen($path,'rb'); if(!$fp) return [];
    flock($fp,LOCK_SH); $data=stream_get_contents($fp); flock($fp,LOCK_UN); fclose($fp);
    $decoded=json_decode($data?:'[]',true);
    return is_array($decoded)?$decoded:[];
}
function update_data(string $name, callable $callback) {
    if (!preg_match('/^[a-z_]+$/', $name)) throw new Exception('Invalid collection');
    $fp=fopen(KITLAB_STORAGE.'/'.$name.'.json','c+'); if(!$fp) throw new Exception('Storage unavailable');
    flock($fp, LOCK_EX); rewind($fp); $decoded=json_decode(stream_get_contents($fp)?:'[]',true);
    $data=is_array($decoded)?$decoded:[];
    try {
        $result=$callback($data);
        $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        rewind($fp); ftruncate($fp,0); fwrite($fp,$json); fflush($fp);
    } finally { flock($fp,LOCK_UN); fclose($fp); }
    return $result;
}
function seed_catalog(): void {
    $path=KITLAB_STORAGE.'/categories.json';
    if (is_file($path)) return;
    update_data('categories', function (&$d) { if(!$d) $d=[
        ['id'=>'soccer','name'=>'Football / Soccer','parent_id'=>null,'sort'=>1,'active'=>true],
        ['id'=>'rugby','name'=>'Rugby','parent_id'=>null,'sort'=>2,'active'=>true],
        ['id'=>'basketball','name'=>'Basketball','parent_id'=>null,'sort'=>3,'active'=>true],
        ['id'=>'training','name'=>'Training & Leisure','parent_id'=>null,'sort'=>4,'active'=>true],
        ['id'=>'football-tops','name'=>'Match Jerseys','parent_id'=>'soccer','sort'=>1,'active'=>true],
        ['id'=>'rugby-tops','name'=>'Playing Jerseys','parent_id'=>'rugby','sort'=>1,'active'=>true],
    ]; });
    update_data('products', function (&$d) { if(!$d) $d=[
        ['id'=>'soccer-jersey','category_id'=>'football-tops','name'=>'Pro Soccer Jersey','shape'=>'jersey','description'=>'Performance fit · short sleeve','active'=>true],
        ['id'=>'rugby-jersey','category_id'=>'rugby-tops','name'=>'Rugby Match Jersey','shape'=>'rugby','description'=>'Structured collar · athletic fit','active'=>true],
        ['id'=>'basketball-singlet','category_id'=>'basketball','name'=>'Basketball Singlet','shape'=>'singlet','description'=>'Sleeveless · breathable','active'=>true],
        ['id'=>'training-hoodie','category_id'=>'training','name'=>'Premium Team Hoodie','shape'=>'hoodie','description'=>'Pullover · fleece','active'=>true],
        ['id'=>'training-polo','category_id'=>'training','name'=>'Teamwear Polo Shirt','shape'=>'polo','description'=>'Folded collar · classic fit','active'=>true]
    ]; });
    update_data('templates', function (&$d) { if(!$d) {
       $base=[['name'=>'Velocity','pattern'=>'chevron','colors'=>['#0c58db','#0d172b','#eaff4f','#ffffff']],['name'=>'Classic Hoops','pattern'=>'hoops','colors'=>['#122b52','#fffefe','#eb532e','#fffefe']],['name'=>'Split Motion','pattern'=>'diagonal','colors'=>['#151e30','#46d7ff','#d9ff44','#ffffff']],['name'=>'Clean Base','pattern'=>'solid','colors'=>['#ffffff','#101c36','#e83358','#101c36']],['name'=>'Elite Stripe','pattern'=>'stripes','colors'=>['#2546ca','#fefefe','#e7e94b','#ffffff']],['name'=>'Dynamic Flash','pattern'=>'flash','colors'=>['#141b2b','#e02440','#ffffff','#ffffff']]];
       foreach(['soccer-jersey','rugby-jersey','basketball-singlet','training-hoodie','training-polo'] as $p){ foreach($base as $i=>$v){$d[]=['id'=>$p.'-'.($i+1),'product_id'=>$p,'name'=>$v['name'],'pattern'=>$v['pattern'],'colors'=>$v['colors'],'active'=>true];}}
    }});
}
function installed(): bool { return is_file(KITLAB_STORAGE.'/admin.json'); }
function csrf_check(): void {
    $token=$_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!is_string($token) || !hash_equals($_SESSION['csrf']??'', $token)) throw new RuntimeException('Security token mismatch');
}
function json_out($value, int $status=200): void {
    http_response_code($status); header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');
    echo json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit;
}
function request_json(): array {
    if ((int)($_SERVER['CONTENT_LENGTH']??0) > 7000000) throw new RuntimeException('Request exceeds 7 MB');
    $body=file_get_contents('php://input');
    $data=json_decode($body?:'null', true); if(!is_array($data)) throw new RuntimeException('Invalid JSON'); return $data;
}
function normalize_design(array $payload): array {
    $colors=$payload['colors']??[];
    if(!is_array($colors)||count($colors)!==4) throw new RuntimeException('Four valid layer colours are required');
    foreach($colors as $c) if(!valid_color($c)) throw new RuntimeException('Invalid design colour');
    $objects=$payload['objects']??[];
    if(!is_array($objects)||count($objects)>40) throw new RuntimeException('Too many design elements');
    $clean=[];
    foreach($objects as $o) {
        if(!is_array($o)) throw new RuntimeException('Invalid design element');
        $type=$o['type']??''; $face=$o['face']??'';
        if(!in_array($type,['text','number','image'],true) || !in_array($face,['front','back'],true)) throw new RuntimeException('Invalid element type or face');
        $id=safe_text($o['id']??'',30);
        if(!preg_match('/^[a-zA-Z0-9_-]{2,30}$/',$id)) throw new RuntimeException('Invalid element ID');
        $size=max(12,min(140,(float)($o['size']??38)));
        $angle=max(-180,min(180,(float)($o['rotation']??0)));
        $x=max(0,min(320,(float)($o['x']??160)));
        $y=max(0,min(420,(float)($o['y']??170)));
        $font=$o['font']??'Impact';
        if(!in_array($font,['Impact','Arial','Georgia','Courier New'],true))$font='Impact';
        $color=$o['color']??'#ffffff'; if(!valid_color($color))$color='#ffffff';
        $src=safe_text($o['src']??'',130);
        if($type==='image' && !preg_match('/^uploads\/[0-9a-f]{24}\.(png|jpg|webp)$/',$src)) throw new RuntimeException('Invalid uploaded logo source');
        $clean[]=['id'=>$id,'type'=>$type,'face'=>$face,'x'=>$x,'y'=>$y,'size'=>$size,'rotation'=>$angle,'font'=>$font,'color'=>$color,'text'=>safe_text($o['text']??'',24),'src'=>$type==='image'?$src:''];
    }
    $mode=($payload['mode']??'sizes')==='roster'?'roster':'sizes';
    $sizes=[];
    foreach(array_slice(is_array($payload['sizes']??null)?$payload['sizes']:[],0,50) as $r){if(!is_array($r))continue;$sizes[]=['size'=>safe_text($r['size']??'M',30),'qty'=>max(0,min(10000,(int)($r['qty']??0)))];}
    $roster=[];
    foreach(array_slice(is_array($payload['roster']??null)?$payload['roster']:[],0,300) as $r){if(!is_array($r))continue;$roster[]=['name'=>safe_text($r['name']??'',40),'number'=>safe_text($r['number']??'',4),'size'=>safe_text($r['size']??'M',30)];}
    return ['template_id'=>safe_text($payload['template_id']??'',65),'colors'=>array_values($colors),'objects'=>$clean,'sizes'=>$sizes,'roster'=>$roster,'mode'=>$mode];
}
function uid(): string { return bin2hex(random_bytes(12)); }
function is_admin(): bool { return installed() && ($_SESSION['admin']??false) === true; }
function require_admin(): void { if(!is_admin()) throw new RuntimeException('Admin login required'); }
function safe_text($s,int $max=120): string { return substr(trim((string)$s),0,$max); }
function valid_color($s): bool {return is_string($s) && preg_match('/^#[0-9a-fA-F]{6}$/',$s)===1;}
function is_rate_limited(string $key,int $limit,int $seconds): bool {
    $now=time(); $list=array_filter($_SESSION['rate_'.$key]??[],fn($t)=> $t>$now-$seconds);
    if(count($list)>=$limit) return true;
    $list[]=$now; $_SESSION['rate_'.$key]=array_values($list); return false;
}
boot();
