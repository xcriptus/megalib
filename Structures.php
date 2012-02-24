<?php


/**
 * Merge flat arrays and remove duplicates. Does not work with nested arrays 
 * because it uses array_unique. 
 * @param List*(Any) $array2
 * @param List*(Any) $array2
 * @return Set*(Any)
 */
function union($array1, $array2) {
  var_dump(array_unique(array_merge($array1,$array2))) ;
  return array_unique(array_merge($array1,$array2)) ;
}

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



/**
 * Return the last error message produced by json_encode and json_decode.
 * @return String! Error message.
 */
function jsonLastErrorMessage() {
  $JSON_ERRORS = array(
      JSON_ERROR_NONE => 'No errors|',
      JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
      JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
      JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
      JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
      JSON_ERROR_UTF8 =>'Malformed UTF-8 characters, possibly incorrectly encoded'
  );
  return $JSON_ERRORS[json_last_error()];
}

/**
 * Get the type of a variable
 * @param Mixed $value
 * @return 'null'|'string'|'bool'|'integer'|'float'|'array'|'resource'|classname|null
 */
function typeOf($var) {
  if(is_string($var)) return 'string';
  if(is_int($var)) return 'integer';
  if(is_bool($var)) return 'boolean';
  if(is_null($var)) return 'null';
  if(is_float($var)) return 'float';
  if(is_object($var)) return get_class($var);
  if(is_array($var)) return 'array';
  if(is_resource($var)) return 'resource';
  return null ; 
}