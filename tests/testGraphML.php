<?php
require_once '../GraphML.php' ;
require_once '../HTML.php' ;

$text = file_get_contents('data/input/g1.html') ;
var_dump(GraphMLAsHTML::getImageAreas($text)) ;


// preg_match_all($regexpr,$text, $matches, PREG_SET_ORDER) ;
// foreach ($matches as $match) {
//   echo "<li>" ;
//   for($i=1; $i<count($match);$i++) {
//     echo $match[$i] . " --- " ;
//   }
//   echo "</li>" ;
// }

// exit ;