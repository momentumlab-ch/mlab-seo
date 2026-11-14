<?php
// Extracts translatable strings of PHP (tokenizer) and JS (regex) files. Output: JSON list.
$root = $argv[1];
$fn = ['__'=>[0,null],'_e'=>[0,null],'esc_html__'=>[0,null],'esc_html_e'=>[0,null],'esc_attr__'=>[0,null],'esc_attr_e'=>[0,null],'_x'=>[0,1],'esc_html_x'=>[0,1],'_n'=>[0,'n']];
$out = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
  $p = $f->getPathname(); $rel = substr($p, strlen($root)+1);
  if (preg_match('#^(lib|languages|bin|docs|assets/img)/#', $rel)) continue;
  if (substr($p,-4)==='.php') {
    $t = token_get_all(file_get_contents($p)); $n=count($t);
    for ($i=0;$i<$n;$i++) {
      if (!is_array($t[$i]) || $t[$i][0]!==T_STRING || !isset($fn[$t[$i][1]])) continue;
      $name=$t[$i][1]; $args=[]; $j=$i+1;
      while ($j<$n && is_array($t[$j]) && $t[$j][0]===T_WHITESPACE) $j++;
      if ($t[$j]!=='(') continue; $j++; $depth=1; $cur=null; $curOk=true; $vals=[];
      for (;$j<$n && $depth>0;$j++) {
        $x=$t[$j];
        if ($x==='(' ) $depth++; elseif ($x===')') {$depth--; if(!$depth){$vals[]=$curOk?$cur:null;break;}}
        elseif ($x===',' && $depth===1) {$vals[]=$curOk?$cur:null;$cur=null;$curOk=true;}
        elseif (is_array($x)) {
          if ($x[0]===T_CONSTANT_ENCAPSED_STRING) { $s=$x[1]; $q=$s[0]; $s=substr($s,1,-1); $s = $q==="'" ? str_replace(["\\'",'\\\\'],["'",'\\'],$s) : stripcslashes($s); $cur=($cur??'').$s; }
          elseif ($x[0]!==T_WHITESPACE && $x[0]!==T_COMMENT) $curOk=false;
        } elseif ($x==='.') {} else { if(trim($x)!=='') $curOk=false; }
      }
      $ctx=null;
      if ($fn[$name][1]===1) { $ctx=$vals[1]??null; }
      $id=$vals[0]??null; if ($id===null) continue;
      $e=['id'=>$id,'ctx'=>$ctx,'file'=>$rel];
      if ($fn[$name][1]==='n') { $e['plural']=$vals[1]??''; }
      $out[]=$e;
    }
  } elseif (substr($p,-3)==='.js') {
    $s=file_get_contents($p);
    preg_match_all("/\b__\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*,\s*'mlab-seo'/s",$s,$m);
    foreach ($m[1] as $id) $out[]=['id'=>stripcslashes(str_replace("\\'","'",$id)),'ctx'=>null,'file'=>$rel];
    preg_match_all("/\b__\(\s*\"((?:[^\"\\\\]|\\\\.)*)\"\s*,\s*'mlab-seo'/s",$s,$m);
    foreach ($m[1] as $id) $out[]=['id'=>stripcslashes($id),'ctx'=>null,'file'=>$rel];
  }
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
