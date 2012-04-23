<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;

//echo "Results go to data/generated/*" ;

echo "<h2>Testing other elements</h2>" ;

$text = file_get_contents('data/input/Company.xsd') ;
$source = new SourceCode($text,'xml') ;

echo $source->getHighlightingHeader('4-11','background:#ffffaa ;') ;

//echo $source->getRawHTML() ;

echo $source->getHighlightedHTML() ;

echo '<h2>The source above has '.$source->getNLOC()." lines</h2>" ;

// var_dump(getElements($xml,'re0')) ;
//var_dump($source->getTokensAsTexts('br0')) ;

//echo '<h2>End of tests</h2>' ;

echo "<h1>END OF TESTS</h1>" ;

