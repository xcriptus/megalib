<?php
define('DEBUG',0) ;
require_once '../HTML.php' ;
require_once '../NAGraph.php' ;
require_once '../GraphML.php' ;
require_once '../Graphviz.php' ;

function buildGraph1() {
  $g = new NAGraph() ;
  $g->addAttributeType("edge","k1","string","distance","10") ; 
  $g->addNode("mali",array("name"=>"Mali","climate"=>"Hot"),"africa") ;
  $g->addNode("lyon",array("name"=>"Lyon"),'france') ;
  $g->addNode("koblenz",array(),'germany') ;
  $g->addEdge('africa','europe',array(),"edge-ae") ;
  $g->addEdge("mali","mali") ;
  $g->addNode("lyon",array("climate"=>"Continental")) ;
  $g->addEdge("mali","france",array("k1"=>"890")) ;
  $g->addNode("france",array(),"europe") ;
  $g->addNode('germany',array(),'europe') ;
  $g->addEdge('bamako','lyon') ;
  $g->addNode('bamako',array(),'mali') ;
  $g->addNode("oceania",array("name"=>"Oceanie")) ;
  
  return $g ;
}

echo '<h2>Building the graph</h2>' ;
$graph = buildGraph1() ;
echo "done" ;

echo "<h2>Writing the graph in graphiz (dot) syntax.</h2>" ;
echo '<h1> TODO nesting not supported yet</h1>' ;
$graphvizWriter = new GraphvizWriter($graph) ;
$graphvizString = $graphvizWriter->graphToGraphString() ;
echo '<pre>'.htmlAsIs($graphvizString).'</pre>' ;

echo "<h2>Writing the graph in graphml syntax</h2>" ;
$graphmlWriter = new GraphMLWriter($graph) ;
$graphmlString = $graphmlWriter->graphToGraphString() ;
echo '<pre>'.htmlAsIs($graphmlString).'</pre>' ;
echo "<h1>End of tests</h1>" ;

echo "<h2>Reading the graph from the graphml generated </h2>" ;
$graphmlReader = new GraphMLReader($graphmlString) ;
$graph2 = $graphmlReader->getGraph() ;
echo "done" ;

echo "<h2>Writing the graph again (should be the same as above)</h2>" ;
$graphmlWriter2 = new GraphMLWriter($graph2) ;
$graphmlString2 = $graphmlWriter2->graphToGraphString() ;
echo '<pre>'.htmlAsIs($graphmlString2).'</pre>' ;
