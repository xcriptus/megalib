<?php
require_once 'main.config.local.php' ;
require_once '../PExpressionAbstract.php' ;

$env1 = array("one"=>1,"two"=>2,"bob"=>"robert") ;

echo "<h2>Build abstract PExpressions without using any concrete syntax</h2>" ;
$bname= new PFunApplication() ;
$bname->name='basename' ;
$bname->parameters=array() ;
$bname->argsMode=0 ;
$bname->phpName='basename' ;
$bname->native=true ;

$f2= new PFunApplication() ;
$f2->name='endsWith' ;
$f2->parameters=array(new PConstant('.gif')) ;
$f2->argsMode=1 ;
$f2->phpName='_endsWith' ;
$f2->native=false ;

$e1=new POperatorExpression() ;
$e1->operator='|' ;
$e1->operands=array($bname,$f2) ;

$pexprs = array($bname,$f2,$e1) ;
foreach(array("/test/x/y.gif") as $value) {
  foreach ($pexprs as $pexpr) {
    var_dump($pexpr->doEval($value,$env1)) ;
  }
}

echo '<h2>END OF TESTS</h2>' ;