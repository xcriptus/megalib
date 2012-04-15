<?php
define('DEBUG','30') ;
require_once '../Files.php' ;
require_once '../Symbols.php' ;

define('DIR','data/input') ;
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

echo '<h1>END OF TESTS</h1>' ;