<?php
define('DEBUG',10) ;
require_once '../Strings.php' ;
require_once '../Files.php' ;
define('DIR','c:/RECHERCHE/ARTICLES/MODELS2012/src') ;

testRangesExpression() ;

//var_dump(explodeId('todldELEMETSToto_titi_TIT239ITTotoTOT__','strtolower')) ;

function testRangesExpression() {
  echo '<h2>Testing rangesExpression</h2>' ;
  $expressions = array(
      "",
      "1",
      "1-4",
      "1,2,5",
      "9-6",
      "1-3,2-5,12-20,9-8") ;      
  foreach ($expressions as $expression) {
    echo '<li>rangeExpression("<b>'.$expression.'</b>") = array(' ;
    echo implode(',',rangesExpression($expression)).')' ;
    echo "</li>" ;
  }
}

echo "<h1>identifier extraction should be reworked</h1>" ;
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

echo '<h1>End of tests</h1>' ;