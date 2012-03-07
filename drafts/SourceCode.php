<?php
require '../../geshi/geshi.php' ;


function outputHeader($geshi) {
  echo '<html><head><style type="text/css"><!--';
  $geshi->get_stylesheet();
  echo '--></style></head><body>';
}

function getElements($xml, $classname) {
  return $xml->xpath('//span[@class="'.$classname.'"]') ;  
}

function getTexts($xml,$classname) {
  $texts = array() ;
  foreach (getElements($xml,$classname) as $element) {
    $texts[] = (string) $element ;
  }
  return $texts ;
}

$source = file_get_contents('SourceCode.php') ;
$geshi = new GeSHi($source, 'php');
$geshi->set_overall_id('code') ;
$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS) ;
$geshi->enable_classes();
$geshi->enable_ids(true);
outputHeader($geshi) ;
$geshi->highlight_lines_extra(array(2,6,7)) ;
$html = $geshi->parse_code();
$html=str_replace('&nbsp;',' ',$html) ;
$xml = simplexml_load_string($html) ;
if ($xml===false) {
  die('error: the HMTL is not valid XML') ;
}
echo $html ;
var_dump(getElements($xml,'re0')) ;
var_dump(getTexts($xml,'re0')) ;
