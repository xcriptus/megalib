<?php
require_once 'main.config.local.php' ;

require_once '../Structures.php' ;
require_once '../HTML.php' ;

$a = array(
    'x' => 'x1',
    'y' => array('y1','y2','y3')) ;
echo htmlAsIs(json_encode_formatted($a)) ;

echo htmlAsIs(jsonBeautifier(json_encode($a))) ;
