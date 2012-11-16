<?php
require_once '../libraries/stemmer/Stemmer.php' ;

/*------------------------------------------------------------------------------
 *  Text and Symbol decomposition
 *------------------------------------------------------------------------------
 * Used in the context of Symbols.php and Corpus.php
 * Decomposers, that is text and symbol decomposers provide operations to decompose 
 * text and symbols at different levels of granularity.
 * This represents the operational, usually transient extraction of components from
 * bigger elements. 
*/








/*------------------------------------------------------------------------------
 *  Interfaces
 *------------------------------------------------------------------------------
 */

/**
 * Decomposer. Just to generalize TextDecomposer or SymbolDecomposer.
 */
interface Decomposer {
}


/**
 * Text decomposers
 */
interface TextDecomposer extends Decomposer {
  /**
   * Extract qualified symbols from a text and return frequencies of occurrences.
   * @param Text! $text
   * @return Map*(QSymbol!,Integer>=1)! a frequency map of qualified symbols in the text
   */
  public function textToQualifiedSymbolFrequencies($text) ;
}

/**
 * Symbol decomposer interface.
 * This interface provides the core hierarchy navigation Q2C, C2A, A2R
 * That is, the decomposition top-down from qualified symbols to root symbol
 * Implementations can realize this hierarchy in anyway they want.
 */
interface SymbolDecomposer extends Decomposer {
  /**
   * Extract the composite symbol sequence from a qualified symbol
   * @param String $qualifiedSymbol
   * @return List+(String!)! the list of composite symbols
   */
  public function qualifiedSymbolToCompositeSymbol($qualifiedSymbol) ;
  /**
   * Extract the atomic symbol sequence from a composite symbol
   * @param String $compositeSymbol
   * @return List+(String!)! the list of atomic symbols
   */
  public function compositeSymbolToAtomicSymbol($compositeSymbol) ;

  /**
   * Get the root of the atomic symbol
   * @param String! $atomicSymbol
   * @param String! the root of the atomic symbol
   * @return String!
   */
  public function atomicSymbolToRootSymbol($atomicSymbol) ;
}


interface TextSymbolDecomposer extends TextDecomposer,SymbolDecomposer {
}








/*------------------------------------------------------------------------------
 *  Implementation
 *------------------------------------------------------------------------------
 */


/**
 * This implementation is rather adhoc, and use a stemmer at the root level.
 * 
 * A symbol decomposer based on Regular Expressions for T2Q#, Q2C, C2A and
 * stemming for A2R
 * TODO: this class has to be reviewed as the implementation may work only on particular
 * parameter configuration
 */
class RegExprBasedSymbolDecomposer implements TextSymbolDecomposer {
  /**
   * @var String! for in  QSymbol, separator character between CSymbol
   * Used by qualifiedSymbolToCompositeSymbol with explode TODO change this
   */
  protected $qualifier ;
  /**
   * @var RegExprPart! Same as above but as a RegExprPart
   */
  protected $qualifiedSymbolSeparatorRegExprPart ;

  protected $atomicSymbolRemoveRegExpr ;

  /**
   * @var Stemmer for A2R
   */
  protected $stemmer ;

  public function textToQualifiedSymbolFrequencies($text) {
    $freqids = idsFrequencies($text,'/\w+(?:'.$this->qualifiedSymbolSeparatorRegExprPart.'\w+)*/') ;
    remove_non_string_keys($freqids) ;
    return $freqids ;
  }
  public function qualifiedSymbolToCompositeSymbol($qualifiedSymbol) {
    return array_exclude_matches(explode($this->qualifier,$qualifiedSymbol),'/^[0-9]*$/') ;
  }
  public function compositeSymbolToAtomicSymbol($compositeSymbol) {
    return array_exclude_matches(idSegments($compositeSymbol,null,$this->atomicSymbolRemoveRegExpr),'/^[0-9]*$/') ;
  }
  public function atomicSymbolToRootSymbol($atomicSymbol) {
    return $this->stemmer->stem(strtolower($atomicSymbol)) ;
  }

  public function __construct($qualifier='.',$qualifiedSymbolSeparatorRegExprPart='\.',$atomicSymbolRemoveRegExpr='/[0-9_\.]/') {
    $this->qualifier = $qualifier ;
    $this->qualifiedSymbolSeparatorRegExprPart = $qualifiedSymbolSeparatorRegExprPart ;
    $this->atomicSymbolRemoveRegExpr= $atomicSymbolRemoveRegExpr;
    $this->stemmer = new Stemmer() ;
  }
}




/**
 * Extract identifier occurrences from a text.
 * Identifiers are segments that are between nonIdRegExpr (see below).
 * No processing is done on these identifiers.
 * Does not apply any kind of lexical analysis to avoid comments, strings, etc.
 * @param String $text The text to analyse.
 * @param RegExpr? $nonIdRegexpr non ids segments that will be ignored.
 * Default to /[^a-zA-Z_]+/
 * @param 'alpha'|'frequence' $order order of the result. Default to 'alpha'
 * @return Map(String!,Integer>0!)! The frequency map of each identifiers.
 */
function idsFrequencies($text,$idRegExpr='/[a-zA-Z_]\w*/',$order='alpha') {
  if (preg_match_all($idRegExpr,$text,$matches,PREG_PATTERN_ORDER)) {
    $ids=$matches[0] ;
    $frequencies = array_frequencies($ids) ;
    remove_non_string_keys($frequencies) ;
    switch($order) {
      case 'alpha':
        ksort($frequencies) ;
        break ;
      case '':
        arsort($frequencies) ;
        break ;
      default :
    }
    return $frequencies ;
  } else {
    return array() ;
  }
}


/**
 * Split an identifier in its logical segmements.
 * @param String $id
 * @param (Function(String!):String!)? A function to apply on each segment.
 * Could be either a anonymous function or a function name like "strtolower",
 * "strtoupper" or "ucfirst". If null is provided then each segment is left as is.
 * Default to strtolower.
 * left as is.
 * @param $regExpr
 * @param $replacement
 * @return List*(String!*)! The list of segment in the identifier
 */
function idSegments($id,$fun="strtolower",$regExpr='/_/',$replacement=' ') {
  $idnew = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $id );
  $idnew = preg_replace($regExpr, $replacement, $idnew) ;
  $idnew = preg_replace( '/([A-Z]+)([A-Z][a-z]+)/', "$1 $2", $idnew );
  $idnew = trim(preg_replace('/  /',' ',$idnew)) ;
  $segments = explode(' ',$idnew) ;
  if (isset($fun)) {
    $segments = array_map($fun,$segments) ;
  }
  return $segments ;
}