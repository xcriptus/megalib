<?php
error_reporting(E_ALL);
define('DEBUG',12) ;

echo "<h1>Usage: parameters: ?dir=...directory...&expr=...expr...</h1>" ;
echo "<h2>This program will not work on windows because of the absence of symbolic links</h2>" ;
 
require_once '../Files.php' ;
$dir=isset($_GET['dir'])?$_GET['dir']:'.' ;
$expr=isset($_GET['expr'])?$_GET['expr']:'test' ;

echo "<h2>Testing of links $dir</h2>" ;

echo "<li>create the file $dir/file1 ... " ;
$r=file_put_contents("$dir/file1","toto") ;
echo ($r===false ? "failed" : $r." bytes written").'</li>' ;

echo "<li>create the file $dir/file2 ... " ;
$r=file_put_contents("$dir/file2","toto") ;
echo ($r===false ? "failed" : $r." bytes written").'</li>' ;

echo "<li>create the symbolic link $dir/link1 to file1 ... " ;
echo boolStr(symlink("file1","$dir/link1")).'</li>' ;

echo "<li>removing the file $dir/file1 so that link1 create above will be broken ... " ;
echo boolStr(unlink("$dir/file1")).'</li>' ;


echo "<li>create the symbolic link $dir/link2 to the file file2 ... " ;
echo boolStr(symlink("file2","$dir/link2")).'</li>' ;

echo "<li>Listing all links in $dir ... " ;
$links=listAllLinksWithInfo($dir);
echo count($links).' links found</li>';
echo "<ul>" ;
foreach($links as $link) {
  echo "<li>".formatLinkInformation($link).'</li>' ;
}
echo "</ul>" ;

echo ", $dir/link1 " ;
echo boolStr(unlink("$dir/link1")) ;
echo ", $dir/link2 " ;
echo boolStr(unlink("$dir/link2")) ;
echo "<li>removing $dir/file2 ... " ;
echo boolStr(unlink("$dir/file2")) ;




echo "<h2>Grep in $expr directory $dir</h2>" ;
$items=grepDirectory(realpath($dir),$expr) ;
if (isset($items)) {
  echo implode('<br/>',$items);
} else {
  echo "FAILURE" ;
}

echo "<h1>RELINK to be tested</h1>" ;