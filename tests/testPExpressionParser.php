<?php
require_once 'main.config.local.php' ;
require_once '../PExpressionConcrete.php' ;

$parser = new PExpressionParser() ;

$exprs=array(
    "nop",
    "isFile",
    "lower",
    "basename",
    "content | matches '#!/bin/sh'",
    "endsWith .gif",
    "basename | endsWith .gif",
    "( basename | endsWithOne .cpp .h .hpp && content | equals '' ) ",
    "endsWithOne .cpp .hpp .h && content | matches `/^ *#include *<QtGui/QApplication> *$/` ",
    'endsWith .hs && exec technologies/HsImportMatcher/matcher.py Text.XML.HXT') ;

foreach($exprs as $expr) {
  echo '<hr/>' ;
  echo '<b>'.htmlAsIs($expr).'</b>' ;
  var_dump($parser->parse($expr)) ;
  if (count($parser->getErrors())) {
    echo 'ERROR founds' ;
    var_dump($parser->getErrors()) ;
  }
}
