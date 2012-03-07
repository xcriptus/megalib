<?php
require_once '../Strings.php' ;
require_once '../Files.php' ;
define('DIR','c:/RECHERCHE/ARTICLES/MODELS2012/ATL') ;

//var_dump(explodeId('todldELEMETSToto_titi_TIT239ITTotoTOT__','strtolower')) ;



$ids=array() ;
foreach(listAllFileNames(DIR) as $filename) {
  if (isFile($filename)) {
    //echo"<li>$filename</li>" ;
    $text = file_get_contents($filename) ;
    $ids = union($ids,array_keys(extractIds($text))) ;
  }  
}
sort($ids) ;
$segments = array();
foreach($ids as $id) {
  $segments=union($segments,explodeId($id)) ;
}
sort($segments) ;
echo implode(' ',$segments) ;
