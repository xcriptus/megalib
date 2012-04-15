<?php
require_once '../SourceCode.php' ;
echo "<h2>Testing SourceCode::generateHighlightedSource</h2>" ;
echo "Results go to data/generated/*" ;
SourceCode::generateHighlightedSource('data/input/Company.xsd', 'xml', 'data/generated',
    array('Company'=>'4-11', 'Department'=>'13-20', 'Employee'=>'22-28')) ;
SourceCode::generateHighlightedSource('data/input/Company.cs', 'csharp', 'data/generated',
    array('Company'=>'25-51', 'Department'=>'59-110', 'Employee'=>'118-155')) ;

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

