<?php
require_once 'main.config.local.php' ;

echo 'If this page display errors then have a look in the corresponding configs/configXXX.php file<br/>' ;
echo 'Note that this page access the web</br>' ;
require_once '../RDF.php';
require_once '../HTML.php';

define('ICPW2009_RDF','http://data.semanticweb.org/dumps/conferences/icpw-2009-complete.rdf') ;
define('OUTPUT_DIR','data/generated/') ;





function testRDFStore() {
  echo "<h2>Testing RDFStore</h2>";
  
  // Testing Store creation
  $testdbaccount = new DatabaseAccount(RDF_TEST_DATABASE_NAME, RDF_TEST_DATABASE_USER, RDF_TEST_DATABASE_PASSWORD) ;
  $configuration = new RDFStoreConfiguration(array(),$testdbaccount, 'test') ;
  $store = new RDFStore($configuration,'') ;
  
  $count = $store->selectTheValue('SELECT count(?x) AS ?count WHERE { ?x ?y ?z }','count') ;
  echo $count.' are triple(s) in the store.<br/>' ;
  $store->reset() ;
  echo 'The store has been emptied.<br/>' ;
  
  echo '<li>Loading '.ICPW2009_RDF.' ... ' ;
  $n = $store->load(ICPW2009_RDF) ;
  echo $n.' triples loaded.</li>' ;
  
  
  $query = 'CONSTRUCT {?s rdfs:label ?o} WHERE {?s rdfs:label ?o}' ;
  echo '<li>query '.$query.' ... ' ;
  $tripleset = $store->constructQuery($query) ;
  
  
  $corefilename = OUTPUT_DIR.'ICPW2009' ;
  $formats = 'HTML,Turtle,GraphML,Graphviz' ;
  echo "<li> saving TripletSet as $corefilename with formats $formats " ;
   // $tripleset = $store->dumpToTripleSet() ;
  $tripleset->saveFiles('HTML,Turtle,GraphML,Graphviz',$corefilename) ;
  echo " done</li>" ;
  
  return $store ;

}
  


// $triples = $store->getARC2Store()->getTriples();
// var_dump($triples);

function testRDFStoreIntrospector($store) {
  $introspector = new RDFStoreIntrospector($store) ;
  $querynames = array_keys($introspector->QUERIES) ;
  foreach($querynames as $queryname) {
    echo '<h2>'.$queryname.'</h2>' ;
    echo homoArrayMapToHTMLTable($introspector->introspect($queryname)) ;
  }
}


$store = testRDFStore() ;
testRDFStoreIntrospector($store) ;



echo '<h1>END OF TESTS</h1>' ;