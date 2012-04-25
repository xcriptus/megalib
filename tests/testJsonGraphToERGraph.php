<?php
require_once 'main.config.local.php' ;
require_once '../HTML.php' ;
require_once '../ERGraph.php' ;
require_once '../JsonGraphAsERGraph.php' ;

$datadir='data/' ;
$inputdir=$datadir.'input/' ;
$jsongraph=$inputdir.'wiki.json' ;
$schema=$inputdir.'wikiSchema.ers' ;
//$dataURIPattern='${type}:http://data.megaplanet.org/data/${type}/|${id}' ;
$dataURIPattern='http://data.megaplanet.org/data/${type}/${id}' ;
$schemaprefix='http://data.megaplanet.org/schema#' ;
$outputcore=$datadir.'generated/wiki' ;
$formats = 'HTML,GraphML,Graphviz,Turtle,RDFXML,RDFJSON,NTriples' ;

echo '<h2>Transforming jsonGraph '.$jsongraph.' with schema '.$schema.'</h2>';
$graph = jsonGraphToERGraph($jsongraph,$schema,$inputdir.'wikiKindToTag.json') ;
echo "done";

echo '<h2>Conversion of the graph to RDF</h2>' ;
$graphasrdf = new ERGraphAsRDF() ;
$graphasrdf->addERGraph($graph,$dataURIPattern,$schemaprefix) ;
echo 'done' ;

echo '<h2>Saving the triples in various formats</h2>' ;
echo "file: $outputcore formats:$formats" ;
$tripleset = $graphasrdf->getTripleSet() ;
$tripleset->saveFiles($formats,$outputcore) ;


echo '<h1>END OF TESTS</h1>' ;