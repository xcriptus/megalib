<?php
//define('DEBUG','20') ;

require_once 'main.config.local.php' ;

require_once '../Strings.php' ;
require_once '../HTML.php' ;

$testCases=array(
    array('match "/test /" | suffixIn .x .y .z | eval ${1} | regexpr /titi/ '),
    array(' word1  "word 2" word\ \'3\'  "word \"4\"" \'word 5\' /word6/   /word\ 7/ ' ),
    array(' ( basename | suffixIn .x .y .z ) && ')
  ) ;

preg_match('/(?U:(.).*\1)/',"|dskjfh,sdf| fsqdfkj|" ,$matches) ;
var_dump($matches);

foreach($testCases as $testCase) {
  echo htmlAsIs($testCase[0]) ;
  var_dump(words($testCase[0],'"\'/')) ;
}



  

exit ;
testRangesExpression() ;
testWords() ;
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

function testWords() {
  echo "<h2>Testing words</h2>" ;
  $x = "   un deux  trois " ;
  echo "<pre>words($x)=</pre>" ;
  var_dump(words($x));
}

// function testEvalTemplate() {
//   echo "<h2>Testing EvalTemplate</h2>" ;
//   $data=array(
      
//       'this ${is} the ${first} example with an ${unmatch}' =>
//         array("is"=>"IS","first"=>"NB 1","nada"=>"NOTHING"),

//       'one with ${various} ${times} the variable ${times}' =>
//         array("is"=>"IS","various"=>'${two}',"two"=>"2",'times'=>'times'),
      
//       'in ${_ID_}, ${one} is ${nb1}.' =>
//         array("french" => array("one"=>"un","nb1"=>"1"),
//               "spanish" => array("one"=>"uno","nb1"=>"1")) 
//     ) ;  
//   foreach( $data as $template => $mapping) {
//     echo "<hr/>" ;
//     echo "TEMPLATE: $template<br/>" ;
//     echo "MAPPING:  ". 
//       (is_map_of_map($mapping) 
//         ? mapOfMapToHTMLTable($mapping)
//         : mapToHTMLList($mapping)) ;
//     $r = evalTemplate($template,$mapping,"*UNDEFINED*") ;
//     echo "RESULT:   ".
//       (is_string($r)
//         ? $r
//         : implode('<br/>',$r) ) ;
//   }
// }
echo "<h1>END OF TESTS</h1>" ;