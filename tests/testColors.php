<?php
require_once 'main.config.local.php' ;
require_once '../Colors.php' ;
/* Inspired by http://www.herethere.net/~samson/php/color_gradient/ */ 

$color1 =  0xF20204;
$color2 =  0x2C4B48 ;
$nbSteps = 10 ;


printf("<p>from %s to  %s </p>\n", colorString($color1), colorString($color2));
echo "<table width='100%' cellpadding='8' style='border-collapse:collapse'>\n";
$colors = getColorGrades($color1,$color2,$nbSteps,false) ;
for ($i = 0; $i <= $nbSteps; $i++) {
  $color = $colors[$i] ;
  $theTDTag = sprintf("<td bgcolor='#%06X'>", $color);
  $theTDARTag = sprintf("<td bgcolor='#%06X' align='right'>", $color);

  $theFC0Tag = "<font color='#000000'>";
  $theFC1Tag = "<font color='#ffffff'>";
  printf("<tr>$theTDTag$theFC0Tag%d</font></td>$theTDTag$theFC0Tag%d%%</font></td>$theTDARTag$theFC0Tag%d</font></td>$theTDARTag$theFC0Tag%06X</font></td>", $i, ($i/$nbSteps) * 100, $color, $color);
  printf("$theTDTag$theFC1Tag%06X</font></td>$theTDTag$theFC1Tag%d</font></td>$theTDARTag$theFC1Tag%d%%</font></td>$theTDARTag$theFC1Tag%d</font></td></tr>\n", $color, $color, ($i/$nbSteps) * 100, $i);
}
echo "</table>\n";
