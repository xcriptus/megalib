<?php
require 'Structures.php' ;
/*
 * Generate HTML output according to basic data structure. Useful to debug.
 */

/**
 * @param Map*<String!,String!>! $map
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
 * @param List*<String!,String!>! $arraymap
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
 * Transform an homogeneous array map to an html table.
 * The function ArrayMapToHTMLTable can be used for all array map
 * including hereogeneous ones, but since it first try to fill the
 * map to make it homogeneous it is less performant.
 * TODO the table construction should fo to homoArrayMapToTable
 * @param List*<Map*<String!,String!>!>! $arraymap An homogeneous array map.
 * @param Boolean? $printHeader
 * @param RegExp|List*<String!*>? $displayFilter
 * @return HTML!
 */
function homoArrayMapToHTMLTable($arrayMap,$printHeader = true,$displayFilter = null) {
  if (count($arrayMap) == 0) {
    return "<b>(no result)</b>" ;
  } else {  
    // infer the actual headers from the first row
    // this is ok because the arraymap is homogeneous
    $allExistingHeaders = array_keys($arrayMap[0]) ;
    
    // compute the headersToDisplay     
    if ($displayFilter == null) {
      $headersToDisplay = $allExistingHeaders ;
    } elseif (is_array($displayFilter)) {
      $headersToDisplay = $displayFilter ;
    } elseif (is_string($displayFilter)) {
      $headersToDisplay=array() ;
      foreach($allExistingHeaders as $header) {
        if (preg_match($displayFilter,$header)) {
          $headersToDisplay[]=$header ;
        }
      }
    } else {
      die('wrong argument for rowsToHTMLTable: displayFilter='.$displayfilter) ;
    }

    $html = '<table>' ;
    
    // output header if necessary
    if ($printHeader) {
      $html .= '<tr>' ;
      foreach ($headersToDisplay as $header) {
        $html .= '<th><b>'.$header.'</b></th>' ;
      }
      $html .= '</tr>' ;
    }
   
    // output the table body    
    foreach ($arrayMap as $row) {
      $html .= '<tr>' ;
      foreach ($headersToDisplay as $header) {
        $html .= '<td>' ;
        // $type = $row[$header.' type'] ;
        $value = $row[$header] ;
        // if ($type=='uri') {
        //  $html .= '<a href="'.$value.'">'.$value.'</a>' ;
        // } else {
          $html .= $value ;
        // }
        $html .= '</td>' ;
      }
      $html .= '</tr>' ;
    }
    $html .= '</table>' ;
    return $html ;
  }
}

/**
 * Make an array map homogeneous and then transform it to a table
 * TODO the table construction should fo to homoArrayMapToTable
 * @param List*<Map*<String!,String!>!>! $arraymap an arbitray array map
 * @param Any! $filler the filler value to make the map homogeneous
 * @param Boolean? $printHeader
 * @param RegExp|List*<String!*>? $displayFilter
 * @return HTML!
 */
function arrayMapToHTMLTable($arrayMap,$filler='',$printHeader = true,$displayFilter = null) {
  $homoArrayMap=heteroToHomoArrayMap($arrayMap,$filler) ;
  return homoArrayMapToHTMLTable($homoArrayMap,$printHeader,$displayFilter) ;
}
