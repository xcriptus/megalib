<?php
require_once 'main.config.local.php' ;
require_once '../HTML.php' ;
require_once '../ERGraph.php' ;
require_once '../JsonGraphAsERGraph.php' ;
require_once '../RDFAsNAGraph' ;

$datadir='data/' ;
$inputdir=$datadir.'input/' ;
$jsongraph=$inputdir.'wiki.json' ;
$schema=$inputdir.'wikiSchema.ers' ;

echo '<h2>Transforming jsonGraph '.$jsongraph.' with schema '.$schema.'</h2>';
$graph = jsonGraphToERGraph($jsongraph,$schema,$inputdir.'wikiKindToTag.json') ;
echo "done";

echo '<h2>Checking the constraints on the graph above</h2>' ;
$ghostEntities = $graph->checkReferentialConstraints() ;
if (count($ghostEntities)>=1) {
  echo '<p>Ghost entities added are</p>' ;
  echo arrayMapToHTMLTable($ghostEntities) ;
} else {
  echo '<p>no ghost entities</p>' ;
}

ech
echo '<h1>END OF TESTS</h1>' ;