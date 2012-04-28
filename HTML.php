<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'Strings.php' ;
require_once 'Structures.php' ;


/**
 * Return a html string keeping the format of the existing string.
 * For convinence if the passed parameter is a boolean or the null value
 * then display the corresponding value.
 * @param String|Boolean|null $value
 * @return HTML!
 */
function htmlAsIs($value) {
  if (is_bool($value)) {
    $value = boolStr($value) ;
  } else if ($value ===null) {
    $value = "null" ;
  }
  return '<pre>'.htmlentities($value).'</pre>' ;
}
    
/*
 * Generate HTML output according to basic data structure. Useful to debug.
 */

/**
 * Return a list from a map where all pair in the map is on a line.
 * @param Map*(String!,String!)! $map
 * @param String? $separator Separator between the key and the value (' => ' by default).
 * @return HTML!
 */
function mapToHTMLList($map,$separator=' => ') {
  $r ='' ;
  if (count($map)!=0) {
    $r .= '<ul>' ;
    foreach($map as $att => $val) {
      $r .= '<li><b>'.$att.'</b>'.$separator.$val.'</li>' ;
    }
    $r .= '</ul>' ;
  }
  return $r ;
}

/**
 * 
 * @param List*(Map(String!,String!))! $arraymap
 * @return HTML!
 */
function arrayMapToHTMLList($arraymap) {
  $r ='' ;
  if (count($arraymap)!=0) {
    $r .= '<ul>' ;
    $i = 0 ;
    foreach ($arraymap as $map) {
      if (count($map)!=0) {
        $r .=  '<li>#'.$i++ ;
        $r .= mapToHTMLList($map) ;
        $r .=  '</li>' ;
      }
    }
    echo '</ul>' ;
  }
  return $r ;
}


/**
 * Transform map of map to into a two dimentsional array indexed by integers and
 * with optional column names and row names.
 * Each inside map becomes a row (with the key if $printKeys is selected).
 * Each key in a inside map leads to a colum. The first row is the table header
 * if $addRowKeys is selected.
 *
 * @param Map*(Scalar!,Map*(Scalar!,Any!)!)! $mapOfMap A map of map not necessarily
 * filled (homogeneous) and with arbitrary scalar keys.
 *
 * @param String? $filler an optional filler that will be used if a cell has no value.
 *
 * @param false|true|RegExp|List*(String!*)? $columnSpec
 * If false there will be no header (no special first row) but all columns are included.
 * If true the first row is a header, and all columns are included.
 * If a string is provided then it is assumbed to be a regular expression. Only matching
 * column names will be added to the table.
 * If $displayFilter is a list, this list will constitute the list of columns headers.
 * Default is true.
 *
 * @param Boolean? $rowSpec
 * If true then the first column will contains the key of rows.
 * Default to true.
 * 
 * @param FunctionName $cellRenderer the name of the function to render a cell value.
 * Takes the cell value as an argument. If null then the cell value is not transformed.
 * Default to 'htmlAsIs' so that all characters and break lines are rendered. null
 * should be passed explicitely if the cell contains HTML that should be interpreted.

 * @return List*(List*(Scalar!)) the resulting table.
 */

function mapOfMapToHTMLTable($mapOfMap,$filler='',$columnSpec=true,$rowSpec=true,$cellRenderer='htmlAsIs',$border="1") {
  if (count($mapOfMap) == 0) {
    return "<b>(empty)</b>" ;
  } else {
    $table=mapOfMapToTable($mapOfMap,$filler,$columnSpec,$rowSpec) ;// fill the map if necessary
    $html = '<table'.($border?" border=$border":"").'>' ;

    $irow = 0 ;
    // if the first row is a header, render it as an html table header
    if ($columnSpec!==false) {
      $html .= '<tr>' ;
      foreach ($table[$irow] as $cell) {
        $html .= '<th><b>'.(isset($cellRenderer)?$cellRenderer($cell):$cell).'</b></th>' ;
      }
      $html .= '</tr>' ;
      $irow++ ;
    }

    // output the table body
    $nbrows=count($table) ;
    for($i=$irow; $i<$nbrows; $i++) {
      $html .= '<tr>' ;
      foreach ($table[$i] as $cell) {
        $html .= '<td>'.(isset($cellRenderer)?$cellRenderer($cell):$cell).'</td>' ;
      }
      $html .= '</tr>' ;
    } 
    $html .= '</table>' ;
    return $html ;
  }
}



