<?php
require_once 'main.config.local.php' ;

require_once '../Structures.php' ;
require_once '../HTML.php' ;

$root = array('size' => 10,'elements'=> array('a','b'),'files'=>array("u"=>10)) ;
$child1 = array('size' => 5,'elements'=> array('d1a'),121,"root",'files'=>array("u"=>6,"v"=>4)) ;
$child2 = array('size' => 2,'elements'=> array('d2a','d2b'),4423,"second"=>"yes",'files'=>array("x"=>1,"y"=>3)) ;

echo "<h2>Testing array function operators</h2>" ;
echo "<h3>x1</h3>" ;
var_dump($child1) ;
echo "<h3>x2</h3>" ;
var_dump($child2) ;

foreach(array("array_fusion","array_merge","array_replace") as $fun) {
  echo "<h3>$fun(x1,x2)</h3>" ;
  $z=$fun($child1,$child2) ;
  var_dump($z) ;
}

echo "<h1>END OF TESTS</h1>" ;

