<?php
require_once 'main.config.local.php' ;

require_once('../HTML.php') ;
$optionsInfo =
  array(
    "stars" => array("Ranking","ENUM",
        "1|bad",
        "2|so so",
        "3|good",
        "super"),
    "text" => array("An example of text","STRING","the default value is here and is long"),
    "validated" => array("has been validated","BOOLEAN",0),
) ;

// echo HTMLFormFromOptions("testHTML.php",$options,$_GET) ;

$options = new Options($optionsInfo) ;
$PARAM = $options->getValues($_GET) ;
var_dump($PARAM) ;
echo $options->getHTML("testHTML.php",$PARAM) ;