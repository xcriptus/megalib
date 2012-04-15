<?php

/**
 * Count identifiers occurrence in a text.
 * Does not apply any kind of lexical analysis to avoid comments, strings, etc.
 * @param String $text The text to analyse.
 * @param RegExpr? $nonIdRegexpr non ids segments that will be ignored.
 * Default to /[^a-zA-Z_]+/
 * @return
 */
function extractIds($text,$nonIdRegexpr='/[^a-zA-Z_]+/') {
  $onlyids = trim(preg_replace($nonIdRegexpr,' ',$text)) ;
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

