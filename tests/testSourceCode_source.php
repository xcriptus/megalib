<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;

define('OUTPUT_DIR','data/generated/') ;

$basedir = '../../101repo/' ;

$sources = array(
    array('s'=>'data/input/Company.xsd', 'l'=>'xml', 'x'=>''),
    array('s'=>'testNAGraph.php', 'l'=>'php', 'x'=>''),
    array('s'=>'../Graphviz.php', 'l'=>'php', 'x'=>'') );

processSourceCode($sources,OUTPUT_DIR) ;



function processSourceCode($sources,$outputDirectory) {
  foreach ($sources as $src) {
    
    echo '<h2>Generating HTML for '.$src['s']." lines</h2>" ;
    
    $text = file_get_contents($src['s']) ;
    $source = new SourceCode($text,$src['l']) ;
  
    echo $source->getHTMLHeader('4-11','background:#ffffaa ;') ;
    
    echo $source->getHTML() ;
  
    echo '<h2>Source as a token list</h2>' ;
    $tokens = $source->getTokens() ;
    echo 'Represented as a string with tab as separator' ;
    echo htmlAsIs(SourceCode::tokensToString($tokens,':')) ;
    echo 'Represented as a json string' ;
    echo htmlAsIs(json_encode($tokens)) ;
    
    echo '<h2>Summary</h2>' ;
    $summary=$source->getSummary($tokens) ;
    echo 'This is a simplified summary nicer to display' ;
    var_dump (SourceCode::simplifiedSummary($summary)) ;
  }
}

echo "<h1>END OF TESTS</h1>" ;

