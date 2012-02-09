<?php
require_once '../SimpleGraph.php' ;

$SCHEMA_EXAMPLE = '
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

echo '<h1>Loading an example of schema</h1>' ;
$schema = new SimpleSchema($SCHEMA_EXAMPLE) ;
echo '<p>This schema defines the following entity kinds: ' ;
echo implode(' ',$schema->getEntityKinds()) ;
echo '<h1>Creating a simple graph with this schema</h1>' ;
$graph = new SimpleGraph($schema) ;
echo '<h1>Checking the constraints on the graph</h1>' ;
$graph->checkConstraints() ;
echo '<h1>END OF TESTS</h1>' ;

