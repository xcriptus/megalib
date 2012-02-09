<?php
/* 
 * String helpers
 */
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
 * Remove comments and replace them by a given string.
 * @param String! $expr The string to clean.
 * @param RegExpr? $commentRegExpr the regular expression corresponding to a comment. 
 * Default to C_LINE_COMMENT_REGEXPR for suppressing // comments.
 * @param String? $replacement Replacement string (default to a newline). 
 */

define ('C_LINE_COMMENT_REGEXPR','/\/\/[^\n]*\n/') ;

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



