<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'Summary.php' ;

function getRGB($color) {
  return array(
      'R' => ($color & 0xff0000) >> 16, 
      'G' => ($color & 0x00ff00) >> 8,
      'B' => ($color & 0x0000ff) >> 0
    ) ;
}

function fromRGB($rgb) {
  return ((($rgb['R'] << 8) | $rgb['G']) << 8) | $rgb['B'];
}

function colorString($color) {
  return sprintf("#%06X",$color) ;
}


function getColorGrades($color1,$color2,$nbSteps,$asHTMLString=true) {
  $colors = array() ;
  $rgb1 = getRGB($color1) ;
  $rgb2 = getRGB($color2) ;
  for ($i=0; $i<=$nbSteps; $i++) {
    $rgbMerged = array() ;
    foreach($rgb1 as $index=>$value) {
      $rgbMerged[$index]=interpolate($i,$nbSteps,$rgb1[$index],$rgb2[$index]) ;
    }
    $colorMerged = fromRGB($rgbMerged) ;
    if ($asHTMLString) {
      $colors[$i]=colorString($colorMerged) ;
    } else {
      $colors[$i]=$colorMerged ;
    }
  }  
  return $colors ;  
}

