<?php
require_once 'main.config.local.php' ;
require_once '../PExpressionConcrete.php' ;


$exprs=array(
    "lower",
    "isFile",
    "nop",
    "content",
    "basename",
    "dirname",
    "eval deux",
    'eval $nothing',
    "deux",
    "dirname | files | count",
    "dirname | files | basename",
    "dirname | files | count",
    "dirname | files | matches #.*/wiki.*Tag*.json$#  | jsonContent | -> Page",
    '$nothing',
    'dirname | exec dir $_last',
    "endsWith .cpp",
    "dirname | set parent",
    "( basename )",
    '[ ( basename ) ( dirname ) ( endsWith toto ) ]',
    '{ a [ ] b 2 }',
    '{ a }',
    'set file | basename | set name | { $file "$name is a file" } | savesAsJson data/generated/tests.json',
    '{ a [ ] b 2 } | count',
    '[ 23 10 7 ] | sum',
    '{ "name" ( basename )  "dir" ( dirname ) }',
    "basename | endsWith .cpp",
    'eval 1 | set x | eval 2 | set y | eval "$x $y"',
    'set path | extension | equals cpp | eval "$path is a cpp file"',
    "basename | matches #(.*)\\.cpp#    ",
    "( basename | endsWithOne .cpp .h .hpp && content | set c | equals '' ) ",
    "endsWithOne .cpp .hpp .h && content | matches '@ *#include@' ",
    "endsWithOne .cpp .hpp .h && content | matches '@ *#include *<QtGui/QApplication> *@' ",
    'endsWith .hs && exec technologies/HsImportMatcher/matcher.py Text.XML.HXT' ,
    'endsWith .cpp && [ { language CPlusPlus comment "a C++ file" } ]' 
  ) ;



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
    // var_dump($abstractExpr) ;
    //var_dump($abstractExpr) ;
    
    echo $abstractExpr->toJson(true) ;
    $errors=$concreteExpr->getErrors() ;
    if (count($errors)!==0) {
      echo 'ERRORS<br/>';
      var_dump($concreteExpr->getErrors()) ;
    }
  }
}
