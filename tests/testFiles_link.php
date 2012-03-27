<?php
error_reporting(E_ALL);
define('DEBUG',12) ;

echo "parameters: ?dir=<directory>&expr=<expr>" ;

require_once '../Files.php' ;
$dir=isset($_GET['dir'])?$_GET['dir']:'.' ;
$expr=isset($_GET['expr'])?$_GET['expr']:'test' ;

echo "<h2>Testing of links $dir</h2>" ;

echo "<li>create a file in $dir/file1 ... " ;
$r=file_put_contents("$dir/file1","toto") ;
echo ($r===false ? "failed" : $r." bytes written").'</li>' ;

echo "<li>create a file in $dir/file2 ... " ;
$r=file_put_contents("$dir/file2","toto") ;
echo ($r===false ? "failed" : $r." bytes written").'</li>' ;

echo "<li>create a link $dir/link1 -> $dir/file1 ... " ;
echo boolStr(symlink("$dir/file1","$dir/link1")).'</li>' ;

echo "<li>removing the file $dir/file1 so that link1 will be broken ... " ;
echo boolStr(unlink("$dir/file1")).'</li>' ;

echo "<li>create a link ","$dir/link2 -> $dir/file2 ... " ;
echo boolStr(symlink("$dir/file2","$dir/link2")).'</li>' ;

echo "<li>Listing all links in $dir ... " ;
$links=listAllLinksWithInfo($dir);
echo count($links).' links found</li>';
echo "<ul>" ;
foreach($links as $link) {
  echo "<li>".formatLinkInformation($link).'</li>' ;
}
echo "</ul>" ;


echo "<li>removing $dir/file2 ... " ;
echo boolStr(unlink("$dir/file2")) ;
echo ", $dir/link1 " ;
echo boolStr(unlink("$dir/link1")) ;
echo ", $dir/link2 " ;
echo boolStr(unlink("$dir/link2")) ;




echo "<h2>Grep in $expr directory $dir</h2>" ;
$items=grepDirectory($dir,$expr) ;
echo implode('<br/>',$items);

echo "<h1>RELINK to be tested</h1>" ;