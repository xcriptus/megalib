<?php
define('DEBUG',0) ;
echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
echo 'Note that this page access the web</br>' ;
require_once '../RDF.php';
require_once '../HTML.php';

define('ICPW2009_RDF','http://data.semanticweb.org/dumps/conferences/icpw-2009-complete.rdf') ;




function saveTripleSet($tripleset,$format,$filename) {
  echo "<p> Saving the tripleset into $filename (format:$format) ..." ;
  $r = $tripleset->save($format,$filename) ;
  assert('$r!==false') ;
  echo " $r bytes saved</p>" ; 
}


function testRDFStore() {
  echo "<h1>Testing RDFStore</h1>";
  
  // Testing Store creation
  $testdbaccount = new DatabaseAccount(RDF_TEST_DATABASE_NAME, RDF_TEST_DATABASE_USER, RDF_TEST_DATABASE_PASSWORD) ;
  $configuration = new RDFStoreConfiguration(array(),$testdbaccount, 'test') ;
  $store = new RDFStore($configuration,'') ;
  
  $count = $store->selectTheValue('SELECT count(?x) AS ?count WHERE { ?x ?y ?z }','count') ;
  echo $count.' are triple(s) in the store.<br/>' ;
  $store->reset() ;
  echo 'The store has been emptied.<br/>' ;
  
  echo 'Loading '.ICPW2009_RDF.' ... ' ;
  $n = $store->load(ICPW2009_RDF) ;
  echo $n.' triples loaded.' ;
  
  $tripleset = $store->dumpToTripleSet() ;
  saveTripleSet($tripleset,'HTML','output/ICPW2009.html') ;
  saveTripleSet($tripleset,'Turtle','output/ICPW2009.ttl') ;
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



