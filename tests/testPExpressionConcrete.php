<?php
require_once 'main.config.local.php' ;
require_once '../PExpressionConcrete.php' ;


$exprs=array(
    "basename",
    "dirname",
    "eval deux",
    'eval $nothing',
    'dirname | exec dir $$',
    "endsWith .cpp",
    "dirname | set parent",
    "basename | endsWith .cpp",
    'eval 1 | set x | eval 2 | set y | eval "$x $y"',
    'set path | extension | equals cpp | eval "$path is a cpp file"',
    "basename | matches #(.*)\\.cpp#    ",
    "( basename | endsWithOne .cpp .h .hpp && content | set c | equals '' ) ",
    "endsWithOne .cpp .hpp .h && content | matches '@ *#include@' ",
    "endsWithOne .cpp .hpp .h && content | matches '@ *#include *<QtGui/QApplication> *@' ",
    'endsWith .hs && exec technologies/HsImportMatcher/matcher.py Text.XML.HXT' ) ;



$env1 = array("one"=>1,"two"=>2,"bob"=>"robert") ;
$input='data/input/test.cpp' ;

foreach($exprs as $expr) {
  echo '<hr/><h3>'.htmlAsIs($input.' < '.$expr).'</h3><hr/>' ;
  $env = array() ;
  $concreteExpr=new ConcretePExpression($expr) ;
  $result = $concreteExpr->doEval($input,$env) ;
  $abstractExpr = $concreteExpr->getAbstractExpression() ;
  echo 'RESULT<br/>';
  var_dump($result) ;
  echo 'ENVIRONMENT<br/>';
  var_dump($env) ;
  
  if ($abstractExpr===null) {
    echo "PARSING FAILED!" ;
    var_dump($concreteExpr->getErrors()) ;
  } else {
    echo 'PARSED EXPRESSION<br/>';
    var_dump($abstractExpr) ;
    $errors=$concreteExpr->getErrors() ;
    if (count($errors)!==0) {
      echo 'ERRORS<br/>';
      var_dump($concreteExpr->getErrors()) ;
    }
  }
}
