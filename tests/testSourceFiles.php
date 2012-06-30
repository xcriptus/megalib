<?php
require_once 'main.config.local.php' ;

require_once '../SourceFiles.php' ;

define('OUTPUT_DIR','data/generated/') ;

$sources = array(
    array('s'=>'data/input/Company.xsd'),
    array('s'=>'testNAGraph.php'),
    array('s'=>'../Graphviz.php') 
    );

processSourceCode($sources,OUTPUT_DIR) ;

function processSourceCode($sources,$outputDirectory) {
  foreach ($sources as $src) {        
    echo "<h2>Generation from ".$src['s']."</h2>" ;
    $sourcefile = new SourceFile($src['s']) ;
    $sourcefile->generate($outputDirectory) ;
    echo 'results are in <a href="'.$outputDirectory.'">'.$outputDirectory.'</a>' ;
  }
}

echo "<h1>END OF TESTS</h1>" ;

