<?php
require_once 'main.config.local.php' ;
require_once '../Lexing.php' ;

$text = '  just a test with so "me ele" ments, such as this:hello ' ;
//$text=array() ;
function wordspaces($x) {
  return explode(' ',$x) ;
} 

$wslexer = new FunBasedLexer("wordspaces") ;
$wslexer = new FunBasedLexer("words") ;

$wslexer->setExpression($text) ;

echo implode(' | ',$wslexer->getTokenList()) ;
