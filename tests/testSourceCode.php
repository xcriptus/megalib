<?php
require_once '../SourceCode.php' ;
$text = file_get_contents('../SourceCode.php') ;
$source = new SourceCode($text,'php') ;
//echo $source->getRawHTML() ;
echo $source->getHighlightingStyle('1-62,72-123','font-size:4pt ; background:#eeeeaa ; ') ;

echo $source->getHighlightedHTML() ;

//$geshi->highlight_lines_extra(array(2,6,7)) ;




// var_dump(getElements($xml,'re0')) ;
var_dump($source->getTokensAsTexts('br0')) ;

echo '<h2>End of tests</h2>' ;
