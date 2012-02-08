<?php
require_once '../Graphml.php' ;

function buildGraph1() {
  $g = new Graphml() ;
  $g->addNode("x") ;
  $g->addNode("y") ;
  $g->addNode("z") ;
  $g->addEdge("x","x") ;
  $g->addEdge("x","y") ;
  return $g ;
}

$graph = buildGraph1() ;
echo '<pre>'.htmlentities($graph->graphToString()).'</pre>' ;