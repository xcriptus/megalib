<?php
require_once '../YEd.php' ;
require_once '../HTML.php' ;

$text = file_get_contents('data/input/g1.html') ;
var_dump(YEdHTML::getImageAreas($text)) ;

echo '<h1>END OF TESTS</h1>' ;