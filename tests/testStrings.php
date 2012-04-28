<?php
//define('DEBUG','20') ;

require_once 'main.config.local.php' ;

require_once '../Strings.php' ;
require_once '../Files.php' ;

testRangesExpression() ;

//var_dump(explodeId('todldELEMETSToto_titi_TIT239ITTotoTOT__','strtolower')) ;

function testRangesExpression() {
  echo '<h2>Testing rangesExpression</h2>' ;
  $expressions = array(
      "",
      "1",
      "1-4",
      "1,2,5",
      "9-6",
      "1-3,2-5,12-20,9-8") ;      
  foreach ($expressions as $expression) {
    echo '<li>rangeExpression("<b>'.$expression.'</b>") = array(' ;
    echo implode(',',rangesExpression($expression)).')' ;
    echo "</li>" ;
  }
}

function testEvalTemplate() {
  echo "<h2>Testing EvalTemplate</h2>" ;
  $data=array(
      
      'this ${is} the ${first} example with an ${unmatch}' =>
        array("is"=>"IS","first"=>"NB 1","nada"=>"NOTHING"),

      'one with ${various} ${times} the variable ${times}' =>
        array("is"=>"IS","various"=>'${two}',"two"=>"2",'times'=>'times'),
      
      'in ${_ID_}, ${one} is ${nb1}.' =>
        array("french" => array("one"=>"un","nb1"=>"1"),
              "spanish" => array("one"=>"uno","nb1"=>"1")) 
    ) ;  
  foreach( $data as $template => $mapping) {
    echo "<hr/>" ;
    echo "TEMPLATE: $template<br/>" ;
    echo "MAPPING:  ". 
      (is_map_of_map($mapping) 
        ? mapOfMapToHTMLTable($mapping)
        : mapToHTMLList($mapping)) ;
    $r = evalTemplate($template,$mapping,"*UNDEFINED*") ;
    echo "RESULT:   ".
      (is_string($r)
        ? $r
        : implode('<br/>',$r) ) ;
  }
}
echo "<h1>END OF TESTS</h1>" ;