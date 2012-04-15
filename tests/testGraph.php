<?php
define('DEBUG',0) ;
require_once '../GraphML.php' ;

function buildGraph1() {
  $g = new Graphml() ;
  $g->addAttributeType("edge","distance","string","10") ; 
  $g->addNode("mali",array("name"=>"Mali","climate"=>"Hot"),"africa") ;
  $g->addNode("lyon",array("name"=>"Lyon"),'france') ;
  $g->addNode("koblenz",array(),'germany') ;
  $g->addEdge('africa','europe') ;
  $g->addEdge("mali","mali") ;
  $g->addNode("lyon",array("climate"=>"Continental")) ;
  $g->addEdge("mali","france",array("distance"=>"890")) ;
  $g->addNode("france",array(),"europe") ;
  $g->addNode('germany',array(),'europe') ;
  $g->addEdge('bamako','lyon') ;
  $g->addNode('bamako',array(),'mali') ;
  
  return $g ;
}

$graph = buildGraph1() ;

echo '<pre>'.htmlentities($graph->graphToString()).'</pre>' ;
echo "<h1>End of tests</h1>" ;