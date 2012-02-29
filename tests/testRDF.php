<?php
define('DEBUG',0) ;
echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
echo 'Note that this page access the web</br>' ;
require_once '../RDF.php';
require_once '../HTML.php';

define('ICPW2009_RDF','http://data.semanticweb.org/dumps/conferences/icpw-2009-complete.rdf') ;

function testRDFConfiguration() {
  echo "<h1>Testing RDFDefinitions</h1>" ;
  $c = new RDFConfiguration() ;
  foreach (array(
      ICPW2009_RDF,
      'http://data.semanticweb.org/dumps/conferences/otherconf.rdf',
      'http://data.semanticweb.org/ns/swc/ontology#heldBy',
      'http://www.w3.org/2000/01/rdf-schema#label',
      'http://xmlns.com/foaf/0.1/maker') as $url) {
    echo "fullurl: ".$url.'</br>';
    echo "domain: ".$c->domain($url)."</br>" ;
    echo "shortname: ".$c->shortname($url)."</br>" ;
    echo "base: ".$c->base($url)."</br>" ;
    echo "prefixed: ".$c->prefixed($url)."</br>" ;
    echo "</br>" ;
  }
}

function saveTripleSet($tripleset,$format,$filename) {
  echo "<p> Saving the tripleset into $filename (format:$format) ..." ;
  $r = $tripleset->save($format,$filename) ;
  assert('$r!==false') ;
  echo " $r bytes saved</p>" ; 
}

function testRDFTripleSet() {
  echo "<h1>Testing RDFTripleSet</h1>" ;
  $tripleset = new RDFTripleSet() ;
  echo "<p>loading ".ICPW2009_RDF." ... " ;
  $n = $tripleset->load(ICPW2009_RDF) ;
  echo $n.' triples loaded.' ;
  saveTripleSet($tripleset,'HTML','output/test.html') ;
  saveTripleSet($tripleset,'Turtle','output/test.ttl') ;
  saveTripleSet($tripleset,'RDFXML','output/test.rdf') ;  
  return $tripleset ; 
}

function testRDFAsGraphml($tripleset) {
  echo "<h1>Testing RDFAsGraphml</h1>";
  saveTripleSet($tripleset,'GraphML','output/test.graphml') ;
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
  
  $ser = ARC2::getTurtleSerializer();
  
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

if (1) {
  testRDFConfiguration() ;
  $tripleset = testRDFTripleSet() ;
  testRDFAsGraphml($tripleset) ;
}
$store = testRDFStore() ;
//echo htmlAsIs($store->getARC2Store()->dump()) ;

testRDFStoreIntrospector($store) ;


$store->startSparqlEndpoint() ;


// function test() {
//   foreach( array("n2","n21","n31","n51","n61",'ttodgpsf') as $n) {
//     echo "<h2>perspective:$n</h2>" ;
//     $perspectiverdfid = '<http://localhost/asop/srdf$acme/Perspective/'.$n.'>' ;
//     echo 'is perspective :' ;
//     print( $this->isItFact($perspectiverdfid,'rdf:type','soo:Perspective')) ;
//     echo ' <br/>\n' ;
//     print_r($this->tryEvalPropertySetExpression($perspectiverdfid,'soo:Perspective',
//         'soo:perspectiveRepository! soo:name! rdf:type! soo:perspectiveOwner! soo:classFragmentExcluded* soo:classFragmentIncluded* ~soo:classFragmentPerspective*')) ;
//   }
// }