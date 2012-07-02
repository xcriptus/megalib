<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Files.php' ;



/*----------------------------------------------------------------------------------
 *     Json processing
*----------------------------------------------------------------------------------
*/


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
 * Decode a json string and die if the result is not a map
 * @param JSON! $json
 * @param $die
 * @return Map$(Scalar!,Any!)! the map
 * @die if the results is not an map
 */
function jsonDecodeAsMap($json,$dieIfInvalidJson=true) {
  $result = json_decode($json,true) ;
  if ($dieIfInvalidJson && !is_array($result)) {
    die('jsonDecodeAsMap: cannot be decoded as a map : '.$json) ;
  }
  return $result ;
}


/**
 * Load a json file and decoded it as a map. Die in case of error.
 * @param Filename! $jsonFilename
 * @return Map$(Scalar!,Any!)! the map
 * @die if the file doesn't exist or is not a valid json, or is not an map
 */
function jsonLoadFileAsMap($jsonFilename,$dieIfInvalidJson=true) {
  $json = loadFile($jsonFilename,$results) ;
  return jsonDecodeAsMap($json,$dieIfInvalidJson) ;
}

/**
 * Indents a flat JSON string to make it more human-readable.
 * @param string $json The original JSON string to process.
 * @return string Indented version of the original JSON string.
 */
function jsonBeautifier($json) {

  $result      = '';
  $pos         = 0;
  $strLen      = strlen($json);
  $indentStr   = '  ';
  $newLine     = "\n";
  $prevChar    = '';
  $outOfQuotes = true;

  for ($i=0; $i<=$strLen; $i++) {

    // Grab the next character in the string.
    $char = substr($json, $i, 1);

    // Are we inside a quoted string?
    if ($char == '"' && $prevChar != '\\') {
      $outOfQuotes = !$outOfQuotes;

      // If this character is the end of an element,
      // output a new line and indent the next line.
    } else if(($char == '}' || $char == ']') && $outOfQuotes) {
      $result .= $newLine;
      $pos --;
      for ($j=0; $j<$pos; $j++) {
        $result .= $indentStr;
      }
    }

    // Add the character to the result string.
    $result .= $char;

    // If the last character was the beginning of an element,
    // output a new line and indent the next line.
    if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
      $result .= $newLine;
      if ($char == '{' || $char == '[') {
        $pos ++;
      }

      for ($j = 0; $j < $pos; $j++) {
        $result .= $indentStr;
      }
    }

    $prevChar = $char;
  }
  return $result;
}

/**
 * Encode a value in json and beautify it if requested
 * @param Any $value
 * @param Boolean? $beautify whether the json results should be indented or not.
 * Default to false.
 * @return JSON! The json string.
 * @Die in case of error.
 */
function jsonEncode($value, $beautify=false) {
  $json = json_encode($value) ;
  if ($json===null) {
    $msg = jsonLastErrorMessage() ;
    die('jsonEncode: '.msg) ;
  }
  if ($beautify) {
    $json=jsonBeautifier($json) ;
  }
  return $json ;
}

/**
 * Save a value (typically a map) as a json file.
 * @param Filename! $filename The name of the file to save.
 * Directory will be created recursively if necessary.
 * @param Any! $value the value to save. Typically a map.
 * @param inout>Map(Filename,Integer|String) $results an array in which
 * results are accumulated. That is if the filename is save then
 * its name will be added in the map with the number of byte saved
 * otherwise an error message will be returned. Use is_string to
 * check if an error occured.
 * @param Boolean? $beautify whether the json results should be indented or not.
 * Default to false.
 * @return Boolean! true if the file as been saved successfully,
 * false otherwise. It is not necessary to test this value after
 * each file save as the result is keep anyway in $results.
 */
function saveAsJsonFile($filename,$value,&$results=array(),$beautify=false) {
  $json = jsonEncode($value,$beautify) ;
  return saveFile($filename,$json,$results) ;
}


/**
 * Save a map to as as json file or merge this map to the existing map if the file already
 * exist. By contrast to saveAsJsonFile that override an potential existing file, here the
 * previous and current value are merged.
 * @param Filename! $filename name of the file to save or to merge
 * @param Map*(Scalar!,Any!)! $map map to save or to merge
 * @param Function? $merger the function to merge the two arrays. Default to "array_merge_recursive"
 * but could be set also to "array_merge" or any other functions taking two maps and returning a map.
 * @param Boolean? $beautify whether the json results should be indented or not.
 * Default to false.
 * @return Boolean! true in case of successof the file writing, false otherwise. It is not
 * necessary to test the result directly as it is recorded in $results anyway.
 * @die if the file exist and is not a valid json map.
 */
function saveOrMergeJsonFile($filename,$map,$merger='array_merge_recursive',&$results=array(),$beautify=false) {
  if (file_exists($filename)) {
    // the file exist, so load the existing structure
    $existingMap = jsonLoadFileAsMap($filename) ;
    $newMap = $merger($existingMap,$map) ;
    $result = saveAsJsonFile($filename,$newMap,$results,$beautify) ;
  } else {
    $result = saveAsJsonFile($filename,$map,$results,$beautify) ;
  }
  return $result ;
}


/**
 *
 * @param unknown_type $root
 * @param unknown_type $findFileParams
 * @param unknown_type $keyTemplate
 * @die if the directory is not readable
 * @die if one of the files found is not a json map
 */
function mapFromJsonDirectory($root,$findFileParams,$keyTemplate='${0}') {
  var_dump($findFileParams) ;

  if (!isset($findFileParams['pattern'])) {
    $findFileParams['pattern'] = 'endsWith json' ;
  }
  // get the list of filenames with the parameters above
  // we defintively need the full file name  and only files
  $findFileParams["apply"] = "path" ;
  $findFileParams["types"] = "file" ;

  $jsonFullFilenames = findFiles($root, $findFileParams) ;
  if ($jsonFullFilenames === null) {
    die(__FUNCTION__.': directory "'.$root.'" cannot be read') ;
  }
  $results = array () ;
  foreach ($jsonFullFilenames as $jsonFullFilename) {
    $map = jsonLoadFileAsMap($jsonFullFilename,false) ;

    if ($map===null) {
      echo "<li>File $jsonFullFilename contains is not a valid json</li>" ;
      $map = array("ERROR") ;
      die(__FUNCTION__."Should we continue") ;
    } else {
    $key = matchToTemplate($findFileParams['pattern'],$jsonFullFilename,$keyTemplate) ;
  }
  $results[$key] = $map ;
}
return $results ;
}


