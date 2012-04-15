<?php
define('DEBUG',10) ;
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

echo "<h1>END OF TESTS</h1>" ;