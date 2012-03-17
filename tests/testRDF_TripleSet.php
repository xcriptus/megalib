<?php
define('DEBUG',0) ;
echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
echo 'Note that this page access the web</br>' ;
require_once '../RDF.php';
require_once '../RDFAsGraph.php' ;
require_once '../HTML.php';
define('OUTPUT_DIR','data/generated/') ;

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

function testTemplate() {
  echo "<h1>test Template</h1>" ;
  $template = '
  ?res
  a rss:item ;
  dc:title ?title ;
  dc:creator ?creator ;
  rss:description ?description ;
  dc:date ?now .
  ';
  echo '<p>The triples below are generated thanks to the following template</p>' ;
  echo htmlAsIs($template) ;

  $a = array(
      'res' => 'http://mega/res/1',
      'title' => 'this is the title',
      'creator' => 'ahmed',
      'toto' => 'tt',
      'description' => 'voici un exemple de texte qui ne decrit que lui meme',
      'now' => date('Y-m-d', time())
  ) ;
  $b = array(
      'res' => 'http://mega/res/2',
      'title' => 'this is another title',
      'creator' => 'bob',
      'toto' => 'tt',
      'description' => 'C est le deuxieme article',
      'now' => date('Y-m-d', time())
  ) ;

  $a['link'] = $a['res'];

  $tripleset = new RDFTripleSet() ;
  $tripleset->addFromTemplate($template, array($a,$b)) ;
  $tripleset2 = new RDFTripleSet() ;
  $tripleset->merge($tripleset2) ;
  echo $tripleset->toHTML() ;

}



function testRDFTripleSet() {
  echo "<h1>Testing RDFTripleSet</h1>" ;
  $tripleset = new RDFTripleSet() ;
  echo "<p>loading ".ICPW2009_RDF." ... " ;
  $n = $tripleset->load(ICPW2009_RDF) ;
  echo $n.' triples loaded.' ;
  $tripleset->saveFiles('HTML,Turtle,RDFXML',OUTPUT_DIR.'testRDF1') ;
  return $tripleset ; 
}

function testRDFAsGraphml($tripleset) {
  echo "<h1>Testing RDFAsGraphml</h1>";
  $tripleset->saveFiles('GraphML',OUTPUT_DIR.'testRDF1') ;
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


testTemplate() ;

testRDFConfiguration() ;
$tripleset = testRDFTripleSet() ;
testRDFAsGraphml($tripleset) ;



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

echo '<h1>End of tests</h1>' ;
