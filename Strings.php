<?php

/**
 * Return a string representation of the value interpreted as a boolean and possibly null.
 * @param Any $value A boolean or null or in fact any value
 * @param Seq(String!,',') $strings a sequence of strings separated by , where the first one
 * is used for 'false', the second one for 'true' and the third one if exist for 'null'.
 * If the third one doesn't exist the 'false' value will be taken.
 * Default to 'false,true,null'.
 * @return String! One of the values indicated as parameter $strings
 */
function boolStr($value,$strings="false,true,null") {
  $strs=explode(",",$strings);
  return 
    $value===null 
      ? (isset($strs[2])?$strs[2]:$strs[0])
      : ($value ?$strs[1]:$strs[0]) ;
}

function startsWith($haystack, $needle){
  return substr($haystack, 0, strlen($needle)) === $needle;
}

function endsWith($haystack, $needle)
{
  return (substr($haystack, - strlen($needle)) === $needle);
}

function prefixIfNeeded($str, $prefix) {
  return startsWith($str,$prefix) ? $str : $prefix . $str ;
}

/**
 * Compose two strings according by a given mode.
 * @param String! $s1
 * @param String! $s2
 * @param $mode
 * '1,2' means is $s1 and $s2 with the separator in between if necessaryc $s1 is not empty
 * '1>2' means $s1 if not empty otherwise $s2
 * '1 (2?)' means $s1 ($s2) if 2!=1, 1 if 2==1
 * othewise 1 is replaced by $s1 and 2 by $s2
 * @param String! $separator used as a separator in the mode '1,2'. Default to ' '.
 * @return String!
 */
function format12($s1,$s2,$mode="1,2",$separator=' ') {
  switch ($mode) {
    case '1':
      return $s1 ;
      break ;
    case '2' :
      return $s1 ;
      break ;
    case "1,2":
      return strlen($s1)===0 ? $s1.$separator.$s2 : $s2 ;
      break ;
    case "1>2":
      return strlen($s1)===0 ? $s1 : $s2 ;
      break ;
    case '1 (2?)':
      if ($s1===$s2) {
        return $s1 ;
      } else {
        return $s1.' ('.$s2.')' ;
      }
      break ;
    default:
      $r = str_replace('1',$s1,$mode) ;
    $r = str_replace('2',$s2,$mode) ;
    return $r ;
  }
}
/**
 * Remove comments and replace them by a given string.
 * @param String! $expr The string to clean.
 * @param RegExpr? $commentRegExpr the regular expression corresponding to a comment.
 * Default to C_LINE_COMMENT_REGEXPR for suppressing // comments.
 * @param String? $replacement Replacement string (default to a newline).
 */

define ('LINE_COMMENT_SUFFIX','[^\n]*\n') ;
define ('SHELL_LINE_COMMENT_REGEXPR','/#'.LINE_COMMENT_SUFFIX.'/') ;
define ('C_LINE_COMMENT_REGEXPR','/\/\/'.LINE_COMMENT_SUFFIX.'/') ;
define ('ADA_LINE_COMMENT_REGEXPR','/--'.LINE_COMMENT_SUFFIX.'/') ;

function removeComments($expr,$commentRegExpr=C_LINE_COMMENT_REGEXPR,$replacement="\n") {
  return preg_replace($commentRegExpr,$replacement,$expr) ;
}


function withoutOptionalPrefix($str, $prefix) {
  return startsWith($str,$prefix) ? substr($str,strlen($prefix)) : $str  ;
}

// from http://php.net/manual/en/function.strtr.php
$_TRANSLATE['removeDiacritics'] = array(
    'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
    'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
    'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
    'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
    'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
    'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
    'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
);

// Added this to handle umlaut in german, but also some other stuff. The rules below are to be checked
// According to wikipedia the rules depend on the language and and not so precise, so...
// see http://en.wikipedia.org/wiki/Diaeresis_%28diacritic%29
// The table below might help in some approximate string matching giving more alternatives than the one in "removeDiacritics"
$_TRANSLATE['alternativeToDiacritics'] = array(
    'Ð'=>'D', 'Ä'=>'Ae','Æ'=>'Ae','Ï'=>'Ie', 'Ö'=>'Oe','Ø'=>'O', 'Ü'=>'Ue','ß'=>'Ss',
    'ä'=>'ae','æ'=>'ae','ë'=>'ee','ï'=>'i','ö'=>'oe','ÿ'=>'ye'
);

function removeDiacritics($text) {
  return strtr($text,$_TRANSLATE['removeDiacritics']) ;
}

function alternativeToDiacritics($textWithAccents) {
  return strtr($text,$_TRANSLATE['alternativeToDiacritics']) ;
}


/**
 * Extract identifiers from a text
 * @param String $text
 * @return 
 */
function extractIds($text) { 
  // $regexpr = '/[^a-zA-Z_0-9]+/' ;
  $regexpr = '/[^a-zA-Z_]+/' ;
  $onlyids = trim(preg_replace($regexpr,' ',$text)) ;
  $ids = array_count_values(explode(' ',$onlyids)) ;
  ksort($ids) ;
  return $ids ;
}


/**
 * Split an identifier in its logical segmements.
 * @param String $id
 * @param (Function(String!):String!)? A function to apply on each segment. 
 * Could be either a anonymous function or a function name like "strtolower", 
 * "strtoupper" or "ucfirst". If null is provided then each segment is left as is.
 * Default to strtolower.
 * left as is.
 * @param $removeNumbers If set to true remove the numeric segments. Default is true.
 * @param $removeUnderscores If set to true remove the underscore segments. Default is true.
 * @return List*(String!*)! The list of segment in the identifier
 */
function explodeId($id,$fun="strtolower",$removeNumbers=true,$removeUnderscores=true) {
  $numberReplacement = ($removeNumbers ? ' ' : ' $1 ') ;
  $underscoresReplacement = ($removeUnderscores ? ' ' : ' $1 ') ;
  $idnew = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $id );
  $idnew = preg_replace( '/(_+)/', $underscoresReplacement, $idnew) ;
  $idnew = preg_replace( '/([0-9]+)/',$numberReplacement, $idnew) ; 
  $idnew = preg_replace( '/([A-Z]+)([A-Z][a-z]+)/', "$1 $2", $idnew );
  $idnew = trim(preg_replace('/  /',' ',$idnew)) ;
  $segments = explode(' ',$idnew) ;
  if (isset($fun)) {
    $segments = array_map($fun,$segments) ;
  }
  return $segments ;  
}

