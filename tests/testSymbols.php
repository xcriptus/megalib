<?php
require_once 'main.config.local.php' ;

require_once '../Files.php' ;
require_once '../Symbols.php' ;

define('DIR','../../101repo/contributions') ;
$freq = extensionFrequencies(listAllFileNames(DIR,"file")) ;
asort($freq) ;
echo mapToHTMLList($freq) ;

exit ;
echo "<h1>identifier extraction should be reworked</h1>" ;
$ids=array() ;
foreach(listAllFileNames(DIR) as $filename) {
  if (isFile($filename)) {
    echo"<li><b>$filename</b></li>" ;
    $text = file_get_contents($filename) ;
    $frequencies = extractIds($text) ;
    echo mapToHTMLList($frequencies) ;
    $ids = union($ids,array_keys($frequencies)) ;
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