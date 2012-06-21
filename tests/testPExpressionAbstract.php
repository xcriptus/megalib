<?php
require_once 'main.config.local.php' ;
require_once '../PExpressionAbstract.php' ;

$env1 = array("one"=>1,"two"=>2,"bob"=>"robert") ;

$bname= new PFunApplication() ;
$bname->name='basename' ;
$bname->parameters=array() ;
$bname->argsMode=0 ;
$bname->phpName='basename' ;
$bname->native=true ;

$f2= new PFunApplication() ;
$f2->name='endsWith' ;
$f2->parameters=array('.gif') ;
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
