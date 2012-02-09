<?php
echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
require_once '../RDF.php';
require_once '../HTML.php';

define('ICPW2009_RDF','http://data.semanticweb.org/dumps/conferences/icpw-2009-complete.rdf') ;

// Testing Store creation
$testdbaccount = new DatabaseAccount(RDF_TEST_DATABASE_NAME, RDF_TEST_DATABASE_USER, RDF_TEST_DATABASE_PASSWORD) ;
$configuration = new RDFStoreConfiguration(array(),$testdbaccount, 'test') ;
$store = new RDFStore($configuration,'') ;


$count = $store->selectTheValue('SELECT count(?x) AS ?count WHERE { ?x ?y ?z }','count') ;
echo $count.' are triple(s) in the store.<br/>' ;
$store->reset() ;
echo 'The store has been emptied.<br/>' ;

echo 'Loading '.ICPW2009_RDF.' ... ' ;
$n = $store->loadDocument(ICPW2009_RDF) ;
echo $n.' triples loaded.' ;

$introspector = new RDFStoreIntrospector($store) ;
$querynames = array_keys($introspector->QUERIES) ;
foreach($querynames as $queryname) {
  echo '<h2>'.$queryname.'</h2>' ;
  echo homoArrayMapToHTMLTable($introspector->introspect($queryname)) ;
}

//$store->startSparqlEndpoint() ;


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