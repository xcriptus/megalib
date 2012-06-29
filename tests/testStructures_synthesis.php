<?php
require_once 'main.config.local.php' ;

require_once '../Structures.php' ;
require_once '../HTML.php' ;
require_once '../TExpression.php' ;

$root = array('size' => 10,'elements'=> array('a','b'),'files'=>array("u"=>10)) ;
$child1 = array('size' => 5,'elements'=> array('d1a'),121,"root",'files'=>array("u"=>6,"v"=>4)) ;
$child2 = array('size' => 2,'elements'=> array('d2a','d2b'),4423,"second"=>"yes",'files'=>array("x"=>1,"y"=>3)) ;

function myAttributeSyn($rootKey,$rootValue,$childValues) {
  switch($rootKey) {
    case "size":
      return array("sumSize"=>Synthesizer::sum($rootKey,$rootValue,$childValues),
                   "maxSize"=>Synthesizer::max($rootKey,$rootValue,$childValues)) ;
    case "elements":
      return array("allElements"=>Synthesizer::fusionAll($rootKey,$rootValue,$childValues),
                   "countAllElements"=>Synthesizer::countAll($rootKey,$rootValue,$childValues)) ;
    case "files":
      return array("allFiles"=>Synthesizer::prefixAll($rootKey,$rootValue,$childValues)) ;
    default:
      return array() ;
  }
}


echo "<h2>testing synthesizeMap</h2>" ;
var_dump($root) ;
$childs=array('d1'=>$child1,'d2'=>$child2) ;
var_dump($childs) ;
$r=synthesizeMap($root,$childs,"myAttributeSyn") ;
var_dump($r) ;


echo "<h2>testing synthesizeMap</h2>" ;
$root='data/input/gwt/war' ;
$mapr = jsonLoadFileAsMap($root.'/index.summary.json') ;
$map1 = jsonLoadFileAsMap($root.'/gwt/index.summary.json') ;
$map2 = jsonLoadFileAsMap($root.'/img/index.summary.json');
$map3 = jsonLoadFileAsMap($root.'/WEB-INF/index.summary.json');
$mapchildren=array(
    "gwt"=>$map1,
    "img"=>$map2,
    "WEB-INF"=>$map3) ;
echo htmlAsIs(jsonEncode($mapchildren,true)) ;




echo "<h1>END OF TESTS</h1>" ;
