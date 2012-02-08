<?php


/**
 * Return all the possible keys that are used in the different rows.
 * @param List*(String!,Any!)! $arrayMap
 * @return List*(String!)!
 */
function keysFromArrayMap($arrayMap) { 
  $keys = array() ;
  foreach ($arrayMap as $row) {
    $keys = array_unique(array_merge($keys,array_keys($row))) ; 
  }
  return $keys ;
} 

/**
 * Build a homogeneous ArrayMap from a potentialy heterogeneous ArrayMap.
 * All keys for all rows are first computed, and then each a value is
 * attributed for each row using the filler value if necessary.
 * @param List*(String!,Any!)! $arrayMap
 * @param Any! $filler 
 * @return List*(List*(Any!)!)! 
 */
function heteroToHomoArrayMap($arrayMap,$filler='') {
  $allKeys = keysFromArrayMap($arrayMap) ;
  $fullArrayMap = array() ;
  foreach ($arrayMap as $map){
    $fullMap = array();
    foreach($allKeys as $key) {
      $fullMap[$key] = (isset($map[$key]) ? $map[$key] : $filler ) ;
    }
    $fullArrayMap[] = $fullMap;
  }
  return $fullArrayMap ;
}

/**
 * TODO should be taken from homoArrayMapToHTMLTable
 * @param unknown_type $homoArrayMap
 */
function homoArrayMapToTable($homoArrayMap) {
  
}


/**
 * 
 * @param List*(Map(String!,Any!)!)! $arrayMap
 * @param String! $key
 * @param Boolean! $distinct
 */
function columnValuesFromArrayMap($arrayMap,$key,$distinct=false) {
  $result = array() ;
  foreach($arrayMap as $map) {
    $result[] = $map[$key] ;
  }
  return ($distinct ? array_unique($result) : $result) ;
}
