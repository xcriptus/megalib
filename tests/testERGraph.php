<?php
require_once 'main.config.local.php' ;
require_once '../HTML.php' ;
require_once '../ERGraph.php' ;

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
  implementations:implementation* ;
  technologies:technology*;
  whatever:Entity*
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
  $graph->DATA["technology"]["J2E"]["implementations"]=array(
    array("type"=>"implementation","id"=>"simple101j2e"),
    array("type"=>"implementation","id"=>"RefToNonExistingImplementation") ) ;
  $graph->DATA["technology"]["J2E"]["whatever"]=array(
    array("type"=>"implementation","id"=>"simple101j2e"),
    array("type"=>"language","id"=>"RefToNonExistingLanguage")) ;
  $graph->DATA["implementation"]["simple101j2e"]["technologies"]=array(array("type"=>"technology","id"=>"J2E")) ;
  $graph->DATA["implementation"]["simple101j2e"]["name"]="simple101j2e" ;
  $graph->DATA["implementation"]["simple101j2e"]["languages"]=array(array("type"=>"language","id"=>"RefToNonExistingLanguage")) ;
  $graph->DATA["implementation"]["simple101j2e"]["summary"]="test is the summary" ;  
  $graph->DATA["implementation"]["simple101j2e"]["motivation"]="this is the motivation" ;  
' ;
echo htmlAsIs($code) ;
eval($code) ;

echo "done" ;

echo '<h2>Checking the constraints on the graph above</h2>' ;
$ghostEntities = $graph->checkReferentialConstraints() ;
echo '<p>Ghost entities added are</p>' ;
echo arrayMapToHTMLTable($ghostEntities) ;
echo '<h2>Checking again on the constraints on the modified graph</h2>' ;
$ghostEntities = $graph->checkReferentialConstraints() ;
if (count($ghostEntities)>=1) {
  echo '<p>Ghost entities added are</p>' ;
  echo arrayMapToHTMLTable($ghostEntities) ;
} else {
  echo '<p>no ghost entities</p>' ;
}
echo arrayMapToHTMLTable($ghostEntities) ;
echo '<h1>END OF TESTS</h1>' ;
