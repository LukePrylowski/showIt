<?php
declare(strict_types=1);
require_once __DIR__.'/game_stats.php';
session_name('showit');
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','cookie_secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function fail(string $message, int $status=400): never { http_response_code($status); echo json_encode(['error'=>$message], JSON_UNESCAPED_UNICODE); exit; }
function catalog(): array {
    $out=[];
    foreach(glob(__DIR__.'/data/categories/*.json') ?: [] as $path) {
        try { $item=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR); } catch(Throwable $e) { error_log('Invalid category: '.basename($path)); continue; }
        if(!is_array($item) || !is_string($item['name']??null) || !is_array($item['words']??null)) continue;
        $words=array_values(array_unique(array_filter($item['words'],fn($w)=>is_string($w) && trim($w)!=='')));
        if($words) $out[pathinfo($path,PATHINFO_FILENAME)]=['name'=>$item['name'],'emoji'=>is_string($item['emoji']??null)?$item['emoji']:'✦','words'=>$words];
    }
    return $out;
}
function draw(array &$room): void {
    if(!$room['deck']) { $room['deck']=$room['pool']; shuffle($room['deck']); if(count($room['deck'])>1 && end($room['deck'])===($room['word']??null)) { $last=count($room['deck'])-1; [$room['deck'][0],$room['deck'][$last]]=[$room['deck'][$last],$room['deck'][0]]; } }
    $room['word']=array_pop($room['deck']);
}
$_SESSION['room']??=['code'=>strtoupper(bin2hex(random_bytes(3))),'teams'=>[],'categories'=>[],'duration'=>60,'phase'=>'setup','turn'=>0,'round'=>1,'roundPoints'=>0];
$room=&$_SESSION['room']; $catalog=catalog(); $action=$_GET['action']??'state';
// Deadline is authoritative, including when a tab was asleep or offline.
if($room['phase']==='running' && microtime(true)>=$room['deadline']) $room['phase']='finished';
if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) fail('Odśwież stronę i spróbuj ponownie.',403);
    try { $input=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR); } catch(Throwable $e) { fail('Nieprawidłowe dane.'); }
    if(!is_array($input)) fail('Nieprawidłowe dane.');
    if($action==='new') {
        $teams=$input['teams']??null; $categories=$input['categories']??null; $duration=$input['duration']??null;
        if(!is_array($teams) || count($teams)<2 || count($teams)>20) fail('Dodaj od 2 do 20 drużyn.');
        foreach($teams as &$name) { if(!is_string($name) || strlen($name)>120 || trim($name)==='') fail('Sprawdź nazwy drużyn.'); $name=trim($name); } unset($name);
        if(count(array_unique(array_map(fn($n)=>function_exists('mb_strtolower')?mb_strtolower($n):strtolower($n),$teams)))!==count($teams)) fail('Nazwy drużyn muszą być różne.');
        if(!is_int($duration) || $duration<15 || $duration>300) fail('Ustaw czas od 15 do 300 sekund.');
        if(!is_array($categories) || !$categories) fail('Wybierz przynajmniej jedną kategorię.');
        $pool=[]; foreach($categories as $id) { if(!is_string($id) || !isset($catalog[$id])) fail('Odśwież listę kategorii.'); $pool=array_merge($pool,$catalog[$id]['words']); }
        $room=['code'=>$room['code'],'teams'=>array_map(fn($n)=>['name'=>$n,'score'=>0],array_values($teams)),'categories'=>array_values(array_unique($categories)),'duration'=>$duration,'phase'=>'ready','turn'=>0,'round'=>1,'roundPoints'=>0,'pool'=>array_values(array_unique($pool)),'deck'=>[]];
    } elseif($action==='start') {
        if($room['phase']!=='ready') fail('Ta tura już się rozpoczęła.');
        if (empty($room['counted'])) {
            try { gameCount(true); } catch (Throwable $error) {
                error_log('Show It counter: '.$error->getMessage());
                fail('Nie udało się zapisać gry. Spróbuj ponownie.', 503);
            }
            $room['counted'] = true;
        }
        draw($room); $room['roundPoints']=0; $room['phase']='running'; $room['deadline']=microtime(true)+$room['duration']; $room['revision']=bin2hex(random_bytes(8));
    } elseif($action==='next') {
        if($room['phase']==='running') {
            if(!is_string($input['revision']??null) || !hash_equals($room['revision'],$input['revision'])) fail('Hasło już się zmieniło. Odśwież stronę.',409);
            $room['teams'][$room['turn']]['score']++; $room['roundPoints']++; draw($room); $room['revision']=bin2hex(random_bytes(8));
        } elseif($room['phase']!=='finished') fail('Najpierw rozpocznij turę.');
    } elseif($action==='finish') {
        if($room['phase']==='running') fail('Czas jeszcze nie minął.');
        if($room['phase']!=='finished') fail('Brak zakończonej tury.');
    } elseif($action==='advance') {
        if($room['phase']!=='finished') fail('Najpierw dokończ turę.');
        $room['turn']=($room['turn']+1)%count($room['teams']); if($room['turn']===0) $room['round']++;
        $room['phase']='ready'; $room['roundPoints']=0;
    } elseif($action==='setup') {
        if($room['phase']==='running') fail('Dokończ aktualną turę.');
        $room['phase']='setup'; unset($room['word'],$room['revision']);
    } else fail('Nieznana operacja.');
} elseif($_SERVER['REQUEST_METHOD']!=='GET' || $action!=='state') fail('Niedozwolona metoda.',405);
$public=array_intersect_key($room,array_flip(['code','teams','categories','duration','phase','turn','round','roundPoints']));
$public['remaining']=$room['phase']==='running'?max(0,$room['deadline']-microtime(true)):0;
if($room['phase']==='running') { $public['word']=$room['word']; $public['revision']=$room['revision']; }
$public['catalog']=[]; foreach($catalog as $id=>$item) $public['catalog'][]=['id'=>$id,'name'=>$item['name'],'emoji'=>$item['emoji'],'count'=>count($item['words'])];
echo json_encode($public,JSON_UNESCAPED_UNICODE);
