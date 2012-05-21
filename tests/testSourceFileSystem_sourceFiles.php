<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
require_once '../SourceFileSystem.php' ;

define('OUTPUT_DIR','data/generated/') ;

$sources = array(
    array('s'=>'data/input/Company.xsd', 'l'=>'xml', 'x'=>''),
    array('s'=>'testNAGraph.php', 'l'=>'php', 'x'=>''),
    array('s'=>'../Graphviz.php', 'l'=>'php', 'x'=>'') );

processSourceCode($sources,OUTPUT_DIR) ;



function processSourceCode($sources,$outputDirectory) {
  foreach ($sources as $src) {
        
    echo "<h2>Generation from ".$src['s']. " in $outputDirectory</h2>" ;
    $sourcefile = new SourceFile($src['s']) ;
    $sourcefile->generate($outputDirectory) ;
  }
}

echo "<h1>END OF TESTS</h1>" ;

