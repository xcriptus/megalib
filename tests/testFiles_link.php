<?php
error_reporting(E_ALL);
define('DEBUG',12) ;
require_once '../Files.php' ;
$dir=isset($_GET['dir'])?$_GET['dir']:'.' ;
$expr=isset($_GET['expr'])?$_GET['expr']:'test' ;


echo "<h2>Listing all broken links in $dir</h2>" ;
$links=listAllLinksWithInfo($dir,true);
foreach($links as $link) {
  echo "<li>".formatLinkInformation($link).'</li>' ;
}

echo "<h2>Grep in $expr directory $dir</h2>" ;
$items=grepDirectory($dir,$expr) ;
echo implode('<br/>',$items);

echo "<h1>RELINK to be tested</h1>" ;