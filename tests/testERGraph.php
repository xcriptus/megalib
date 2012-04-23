<?php
require_once '../HTML.php' ;
require_once '../ERGraph.php' ;
define('DEBUG',1) ;

$ER_SCHEMA_EXAMPLE = '
// Some example of comments
feature {
  name:string@ ;         // @ means key
  summary:string! ;      // ! means compulsory
  description:string? ;  // ? means optional
  illustration:string? ;
  implementations:implementation*  // * means set of references
}                                  // there is no single reference
implementation {
  name:string@ ;
  summary:string! ;
  motivation:string! ;
  features:feature* ;
  languages:language* ;
  technologies:technology* ;
  usage:string?
}
language {
  name:string@ ;
  summary:string! ;
  description:string? ;
  implementations:implementation*
}
technology {
  name:string@ ;
  summary:string! ;
  description:string? ;
  implementations:implementation*
}
' ;

echo '<h2>An example of Entity-Relationship Schema</h2>' ;
echo htmlAsIs($ER_SCHEMA_EXAMPLE) ;

echo '<h2>Loading this schema</h2>' ;
$schema = new ERSchema($ER_SCHEMA_EXAMPLE) ;
echo '<p>This entity-relationship schema defines the following entity kinds: ' ;
echo implode(' ',$schema->getEntityKinds()) ;

echo '<h2>Creating a simple graph using this schema with the following code</h2>' ;
$code = '
  $graph = new ERGraph($schema) ;
  $graph->DATA["technology"]["J2E"]["name"]="J2E" ;
  $graph->DATA["technology"]["J2E"]["summary"]="J2E is oracle platform for Java Entreprise Edition" ;
  $graph->DATA["technology"]["J2E"]["implementations"]=array("simple101j2e","advanced101j2e") ;
' ;
echo htmlAsIs($code) ;
eval($code) ;

echo "done" ;

echo '<h2>Checking the constraints on the graph above</h2>' ;
echo '<p>Two references are not defined in the graph</p>.' ;
echo '<p>ghost entities added are :' ;
print_r($graph->checkReferentialConstraints()) ;
echo '<h1>END OF TESTS</h1>' ;

