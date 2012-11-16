<?php defined('_MEGALIB') or die("No direct access") ;

require_once '../libraries/tagCloud/TagCloud.php' ;

require_once 'Summary.php' ;
require_once 'Decomposers.php';
require_once 'Corpus.php' ;


class SymbolTable {
  protected $nb = 0;
  protected $stringToSymbol ;
  protected $symbolToString ;

  /**
   * Convert a string into a symbol. Ensure that there is a 1:1 mapping
   * between string and symbols.
   * @param String! $string
   * @return The id of the symbol
   */
  public function symbol($string) {
    // add the string to the symbol table if not already there
    if (!isset($this->stringToSymbol[$string])) {
      $this->nb++ ;
      $symbol = $this->nb ;
      $this->stringToSymbol[$string] = $symbol ;
      $this->symbolToString[$symbol] = $string ;
    }

    // return $this->stringToSymbol[$string] ;
    return $string ;
  }
  
  public function string($symbol) {
    if (!isset($this->stringToSymbol[$symbol])) {
      die ('UNDEFINED SYMBOLS '.$symbol) ;
    }
  }
}


/**
 * 
 * This module is based on the following concepts where each levels "aggregate"
 * the level below. For the sake of concision the letters T,Q,C,A,R are used
 * with the following meaning.
 *   
 *   TextId                     (TSymbol) 
 *     * QualifiedSymbol        (QSymbol)
 *         * CompositeSymbol    (CSymbol)
 *             * AtomicSymbol   (ASymbol)
 *                * RootSymbol  (RSymbol)
 * 
 * The first level is TextMap corresponding to a collection of texts, indexed by
 * TSymbols. Could be for instance a file name.
 * 
 * type TextMap == Map*(TSymbol!,Text!)
 * type Text == String
 * 
 * type AnySymbol == TSymbol|QSymbol|CSymbol|ASymbol|RSymbol
 * type TSymbol == Scalar!
 * type QSymbol == String!
 * type CSymbol == String!
 * type ASymbol == String!
 * type RSymbol == String!
 * 
 * 
 * Then functions allows to get frequencies of QualifiedSymbols
 * 
 * T2Q#: TSymbol -> Map*(QSymbol,Integer>=1)
 * 
 * Q2C : QSymbol -> List*(CSymbol)
 * 
 * C2A : CSymbol -> List*(ASymbol)
 * 
 * A2R : ASymbol -> RSymbol
 *
 * T2C : TSymbol -> Map*(CSymbol,Inteqer>=1)
 *
 */




/*------------------------------------------------------------------------------
 *   Symbol Indexes
 *------------------------------------------------------------------------------
 * Persitence of symbol decomposition. Gather results computed by SymbolDecomposers 
 * and store them, with some aggregation functions.
 */


/**
 * Navigate in the schema of indexes at different levels
 *
 * type SymbolKind = 'T'|'Q'|'C'|'A'|'R'
 * type SymbolKindName = 'Text','Qualified','Composite','Atomic','Root'
 * type SymbolKindIndex = Integer [0..4]
 * 
 * type IndexKind = SymbolKind | 'Q#' | 'cLength' | 'aLength' 
 */
class SymbolIndexesSchema {

  protected $levelNames = array('Text','Qualified','Composite','Atomic','Root') ;
  protected $levels     = array('T','Q','C','A','R') ;

  /**
   * Return the index of a level. Will be used for previous/next features.
   * @param SymbolKind! $kind
   * @return SymbolKindIndex!
   */
  public function _getLevelIndex($kind) {
    $index = array_search($kind,$this->levels) ;
    if ($index===false) {
      die(__FUNCTION__.': no level called "'.$kind.'"') ;
    }
    return $index ;
  }

  /**
   * Return the full name of a level.
   * @param SymbolKind! $kind
   * @return SymbolKindName!
   */
  public function getLevelName($kind) {
    return $this->levelNames[$this->_getLevelIndex($kind)] ;
  }

  /**
   * Return the next levels from a given level
   * @param SymbolKind? $level the level to start with. Default to the
   * first level.
   * @param SymbolKind $toLevelOrLength the level to stop at, or the number
   * of levels to get including the starting level. Default to the last level.
   * @return List*(SymbolKind!) The list of levels 
   */
  public function getNextLevels($level='T',$toLevelOrLength='R') {
    $index = $this->_getLevelIndex($level) ;
    if (is_integer($toLevelOrLength)) {
      $length = $toLevelOrLength ;
    } else {
      $toIndex = $this->_getLevelIndex($toLevelOrLength) ;
      $length = $toIndex-$index ;
    }
    return array_slice($this->levels,$index+1,$length) ;
  }

  /**
   * Return the next level or null if this is the last level
   * @param SymbolKind! $level
   * @return SymbolKind? The next level or null
   */
  public function getNextLevel($level) {
    $index = $this->_getLevelIndex($level) ;
    if ($index+1<count($this->levels)){
      return $this->levels[$index+1] ;
    } else {
      return null ;
    }
  }

  public function getPreviousLevel($level) {
    $index = $this->_getLevelIndex($level) ;
    if ($index-1>=0){
      return $this->levels[$index-1] ;
    } else {
      return null ;
    }
  }

  public function getPreviousLevels($level='R',$toLevelOrLength='T') {
    $index = $this->_getLevelIndex($level) ;
    if (is_integer($toLevelOrLength)) {
      $length = $toLevelOrLength ;
    } else {
      $toIndex = $this->_getLevelIndex($toLevelOrLength) ;
      $length = $index-$toIndex ;
    }
    return array_reverse(array_slice($this->levels,$index-$length,$length)) ;
  }

}



/**
 * Symbol indexes structure with save and load operations
 */
class SymbolIndexesStore extends SymbolIndexesSchema {

  /**
   * @var A symbol table containing a global index of all strings to save storage space
   */
  protected $s ;
  /**
   * @var The various indexes.
   *
   *
   * indexes['Q#'][q] -> Integer
   *
   * --- Q2T & T2Q ---
   * indexes['Q'][q]['T'][t] -> Integer
   *
   * indexes['T'][t]['Q'][q] -> Integer
   *
   *
   * --- Q2C & C2Q ---
   * indexes['Q'][q]['C'] -> List*(C)
   * indexes['Q'][q]['cLength'] -> Integer
   *
   * indexes['C'][c]['Q'][q] -> Integer
   * indexes['cLength'][int] -> List*(Q)
   *
   * --- C2A & A2C ---
   * indexes['C'][c]['A'] -> List*(A)
   * indexes['C'][c]['aLength'] -> Integer
   *
   * indexes['A'][a]['C'][c] -> Integer
   * indexes['aLength'][int]-> List*(A)
   *
   * --- A2R & R2A ---
   * indexes['A'][a]['R']=r ;
   * indexes['R'][r]['A'][a] -> Integer ;
   */
  protected $indexes ;

  public function toJson($beautify=true) {
    return jsonEncode($this->indexes,$beautify) ;
  }

  /**
   * Save a value (typically a map) as a json file.
   * @param Filename! $filename The name of the file to save.
   * Directory will be created recursively if necessary.
   * @param inout>Map(Filename,Integer|String) $results an array in which
   * results are accumulated. See function savaAsJsonFile.
   * @param Boolean? $beautify whether the json results should be indented or not.
   * Default to false.
   * @return Boolean! true if the file as been saved successfully,
   * false otherwise. It is not necessary to test this value after
   * each file save as the result is keep anyway in $results.
   */

  public function saveAsJsonFile($filename,&$results=array(),$beautify=false) {
    $map = array (
        "s"=>$this->s, 
        "indexes"=>$this->indexes) ;
    return saveAsJsonFile($filename,$map,$results,$beautify) ;
  }

  /**
   * Load the index from a previously saved json file
   */
  public function loadJsonFile($filename) {
    $map = jsonLoadFileAsMap($filename) ;
    $this->s = $map['s'] ;
    $this->indexes = $map['indexes'] ;
  }

  public function __construct($filename=null, SymbolTable $symbolTable=null) {
    if (is_string($filename)) {
      $this->loadJsonFile($filename) ;
    } else {
      $this->indexes=array() ;
    }
    if ($symbolTable===null) {
      $this->s = new SymbolTable() ;
    } else {
      $this->s = $symbolTable ;
    }
  }
}




/**
 * Create symbol indexes by adding texts and using a SymbolDecomposer
 */
class SymbolIndexesBuilder extends SymbolIndexesStore {  
  
  /**
   * Add all the texts in the corpus. This operation can be called
   * various time for different corpus if necessary.
   * @param DecomposableTextCorpus! $corpus
   * @return void
   */
  public function addCorpus(DecomposableTextCorpus $corpus) {
    foreach($corpus->ids() as $tSymbol) {
      $this->addText($tSymbol,$corpus->text($tSymbol),$corpus->decomposer($tSymbol)) ;
    }
  }
  
  
  /**
   * addCorpus method is preferred.
   * Add new text maps and update indexes accordingly.
   * This function is just a helper if no corpus class is defined
   * and if the corpus is actually represented by a map in extension.
   * @param Map*(TextId!,Text)! $textMap
   * @param TextSymbolDecomposer! the decomposer
   * @return void
   */
  public function addTexts($textMap,TextSymbolDecomposer $decomposer) {
    foreach($textMap as $tSymbol => $text) {
      $this->addText($Symbol,$text,$decomposer) ;
    }
  }
  
  /**
   * addCorpus method is preferred.
   * Add a new text and update indexes accordingly.
   * Runs at T2Q level but trigger levels below
   * @param TextId! $tSymbol id of the text to add
   * @param String! $text the text to index
   * @param TextSymbolDecomposer! the decomposer
   * @return List*(QSymbol!)! new qualified symbols added 
   */
  public function addText($tSymbol,$text,TextSymbolDecomposer $decomposer) {
    $newQualifiedSymbols = array() ;
    $tIdSymbol = $this->s->symbol($tSymbol) ;
    if (!isset($this->indexes[$tIdSymbol])) {
    
      // Computation
      $frequencies = $decomposer->textToQualifiedSymbolFrequencies($text) ;
      foreach($frequencies as $qSymbol => $count) {
        $qIdSymbol = $this->s->symbol($qSymbol) ;
        // Forward
        $this->indexes['T'][$tIdSymbol]['Q'][$qIdSymbol] = $count ;
        @ $this->indexes['T'][$tIdSymbol]['#'] += $count ;
    
        // Chaining
        if (!isset($this->indexes['Q'][$qIdSymbol])) {
          $newQualifiedSymbols[]=$qSymbol ;
        }
    
        // Backward
        @ $this->indexes['Q#'][$qIdSymbol] += $count ;
        $this->indexes['Q'][$qIdSymbol]['T'][$tIdSymbol] = $count ;
      }
    }
    $this->addQualifiedSymbols($newQualifiedSymbols,$decomposer) ;
  }
  
  
  
  
  /**
   * Add new qualified symbols. This function is normally called only by addTexts.
   * Runs at Q2C level but trigger levels below
   * @param List*(QSymbol)! $qualifiedSymbols
   * @return List*(CSymbol)! list of new composite symbol added
   */
  protected function addQualifiedSymbols($qualifiedSymbols, SymbolDecomposer $decomposer) {
    $newCompositeSymbols = array() ;
    // for all qualified symbols
    foreach($qualifiedSymbols as $qSymbol) {
      $qIdSymbol = $this->s->symbol($qSymbol) ;
      
      if (!isset($this->indexes['Q'][$qIdSymbol]['C'])) {
        // Computation Q2C
        // split qualified symbols into compound symbols
        $cSymbols = $decomposer->qualifiedSymbolToCompositeSymbol($qSymbol) ;
        
        $cIdsSymbols = array() ;
        foreach ($cSymbols as $cSymbol) {
          $cIdSymbol = $this->s->symbol($cSymbol) ;
          $cIdsSymbols[] = $cIdSymbol ;
          
          // Chaining
          if (!isset($this->indexes['C'][$cIdSymbol])) {
            $newCompositeSymbols[] = $cSymbol ;
          }
          
          // Backward
          @ $this->indexes['C'][$cIdSymbol]['Q'][$qIdSymbol] += 1 ;
        }
        
        // Forward
        $this->indexes['Q'][$qIdSymbol]['C'] = $cIdsSymbols ;
        $this->indexes['Q'][$qIdSymbol]['cLength'] = count($cIdsSymbols) ;
        @ $this->indexes['cLength'][count($cIdsSymbols)][] = $qIdSymbol ;
        
      }
    }
    $this->addCompositeSymbols($newCompositeSymbols,$decomposer) ;
    return $newCompositeSymbols ; 
  }

  /**
   * Add new composite symbols. This function is normally called only by addQualifiedSymbols.
   * Runs at C2A level but trigger levels below
   */
  protected function addCompositeSymbols($compositeSymbols, SymbolDecomposer $decomposer) {
    $newAtomicSymbols = array();
    // for all compound symbols found in the previous step
    foreach($compositeSymbols as $cSymbol) {
      $cIdSymbol = $this->s->symbol($cSymbol) ;
      
      if (!isset($this->indexes['C'][$cIdSymbol]['A'])) {
        // Computation C2A
        // split compound symbols into atomic symbols
        $aSymbols = $decomposer->compositeSymbolToAtomicSymbol($cSymbol) ;

        $aIdsSymbols = array() ;
        foreach ($aSymbols as $aSymbol) {
          $aIdSymbol = $this->s->symbol($aSymbol) ;
          $aIdsSymbols[] = $aIdSymbol ;
          
          // Chaining 
          if (!isset($this->indexes['A'][$aIdSymbol])) {
            $newAtomicSymbols[] = $aSymbol ;
          }
          
          // Backward
          @ $this->indexes['A'][$aIdSymbol]['C'][$cIdSymbol] += 1 ;
        }
        // Forward
        $this->indexes['C'][$cIdSymbol]['A'] = $aIdsSymbols ;
        $this->indexes['C'][$cIdSymbol]['aLength'] = count($aIdsSymbols) ;
        @ $this->indexes['aLength'][count($aIdsSymbols)][] = $cIdSymbol ;
        
      }
    }
    $this->addAtomicSymbols($newAtomicSymbols,$decomposer) ;
    return $newAtomicSymbols ;
  }

  /**
   * Add new atomic symbols. This function is normally called only by addCompositeSymbols.
   * Runs at A2R level
   */
  protected function addAtomicSymbols($atomicSymbols, SymbolDecomposer $decomposer) {
    // for all atomic symbols found in the previous step
    foreach($atomicSymbols as $aSymbol) {
      if (!isset($this->indexes['A'][$aSymbol]['R'])) {
        
        // Computation A2R
        $rSymbol = $decomposer->atomicSymbolToRootSymbol($aSymbol) ;
        
        // Forward
        $this->indexes['A'][$aSymbol]['R']=$rSymbol ;
        
        // Backward
        @ $this->indexes['R'][$rSymbol]['A'][$aSymbol] += 1 ;
      }
    }
  }

  /**
   * Build indexes from a corpus or load existing indexes from the file specified
   * @param DecomposableTextCorpus|Filename|null $corpusOrExistingIndexFilenameOrNull 
   * If a corpus is given, then the index is build from this initial corpus.
   * If a string is given, it is assumed to be an existing indexes file name. Initialize
   * the indexes with it.  
   * If null is provided the indexes are initialized to empty.
   * @param SymbolTable? $symbolTable an optional symbol table.
   */
  public function __construct($corpusOrExistingIndexFilenameOrNull=null, SymbolTable $symbolTable=null) {
    if ($corpusOrExistingIndexFilenameOrNull instanceof TextCorpus) {
      $corpus = $corpusOrExistingIndexFilenameOrNull ;
      // a corpus is given. Initialize the index from this corpus
      parent::__construct(null,$symbolTable) ;
      $this->addCorpus($corpus) ;
    } else {
      // this is not a corpus, just initialize the parent class
      $existingIndexFilenameOrNull = $corpusOrExistingIndexFilenameOrNull ;
      parent::__construct($existingIndexFilenameOrNull,$symbolTable) ;
    }
  }
}




/**
 * Symbol indexes with navigation, synthesized information, etc.
 */
class SymbolIndexes extends SymbolIndexesBuilder {
    
  // TSymbol -> Map*(QSymbol,Integer>=1)
  // QSymbol -> List*(CSymbol)
  // CSymbol -> List*(ASymbol)
  // ASymbol -> RSymbol
  
  /**
   * Get the index tree corresponding to each kind.
   * Only (forward and backward direct) relations are returned.
   * For each kind 
   *   'T' : t -> 'Q' -> q -> Integer
   *   
   *   'Q' : q -> 'T' -> t -> Integer
   *              'C' -> List*(C)
   *              'cLength' -> Integer
   * 
   *   'C' : c -> 'Q' -> q -> Integer
   *              'A' -> List*(A)
   *              'aLength' -> Integer
   * 
   *   'A' : a -> 'C' -> c -> Integer
   *              'R' -> r
   * 
   *   'R' : r -> 'A' -> a -> Integer ;
   * 
   *   'cLength' : int -> List*(Q)
   *   'aLength' : int -> List*(A)
   *   
   *   'Q#': q -> Integer
   *
   */
  public function getSymbolsIndexTree($kind) {
    return array_keys($this->indexes[$kind]) ;
  }
  
  /**
   * Get the index tree for a given symbol of a given kind
   * or an integer if cLength, aLength
   * @param  $symbolOrInteger
   * @param unknown_type $kind
   * @return multitype:
   */
  public function getSymbolIndexTree($kind,$symbolOrInteger) {
    return array_keys($this->indexes[$kind][$symbolOrInteger]) ;
  }
  
  public function getSymbolAttributes($kind,$targetKind,$symbolOrInteger) {
    return array_keys($this->indexes[$kind][$symbolOrInteger][$targetKind]) ;
  }
  
  
  /**
   * Return all symbols of a given kind
   * @param unknown_type $kind
   */
  public function getSymbols($kind) {
    return array_keys($this->indexes[$kind]) ;
  }
  
  public function exists($kind,$symbol) {
    return isset($this->indexes[$kind][$symbol]) ;
  }
  
  
  
  /*--------------------------------------------------------------------------
   * Navigation down (starting from texts and going towards smaller symbols)
   *-------------------------------------------------------------------------- 
   */
  
  /**
   * Given a text or the whole set or subset of texts added to the index, get the 
   * frequencies for the kind of elements specified.
   * This function goes down from text(s) to the kind of element selected by
   * computing frequencies. It allows to get information such as clouds for the whole
   * corpus or slice of this corpus.
   * 
   * @param $tSymbolSet? the text identifier indicating on which text(s), frequencies should be
   * computed. If null is provided then all the texts are considered. If it is a string, it corresponds
   * to the only text id to consider. If this is a set of textId, then they are all considered.
   * 
   * @param SymbolKind! $targetElementKind the kind of element one want the frequencies to be computed
   * 
   * @return Map(XSymbol,Integer>=0) a map which domain is the set of all symbols of type
   * $targetElementKind that occur in the text(s) specified.
   */
  public function getFrequencies($tSymbolSet=null,$targetElementKind) {  
    
    // --- T2Q ---
    // compute the number of occurrence of qualified symbol according to the symbol set specified 
    if ($tSymbolSet===null) {
      // null means that all texts are considered. 
      // So the global computation from the index is just fine.
      $qFrequencies = $this->indexes['Q#'] ;
    } elseif (is_string($tSymbolSet)) {
      // only one text is specified. So get the qualified symbol information for this text
      $qFrequencies =  $this->indexes['T'][$tSymbolSet]['Q'] ;      
    } else {
      // collect all frequencies and merge them
      $frequenciesSet = array() ;
      foreach ($tSymbolSet as $tSymbol) {
        $frequenciesSet[]=$this->indexes['T'][$tSymbol]['Q'] ;
      }
      $qFrequencies = merge_frequencies($frequencies) ;
    }
    if ($targetElementKind==='Q') {
      return $qFrequencies ;
    }
    
    // TODO: the three piece of code below could be generalized as the composition of
    // a function on a frequencies. This function could go to Summary.php
    
    // --- Q2C ---
    $cFrequencies = array() ;
    foreach( $qFrequencies as $qSymbol => $count) {
      $cSymbolList = $this->indexes['Q'][$qSymbol]['C'] ;
      foreach( $cSymbolList as $cSymbol ) {
        @ $cFrequencies[$cSymbol] += $count ;
      }
    }
    if ($targetElementKind==='C') {
      return $cFrequencies ;   
    }
    
    // --- C2A ---
    $aFrequencies = array() ;
    foreach( $cFrequencies as $cSymbol => $count) {
      $aSymbolList = $this->indexes['C'][$cSymbol]['A'] ;
      foreach( $aSymbolList as $aSymbol ) {
        @ $aFrequencies[$aSymbol] += $count ;
      }
    }
    if ($targetElementKind==='A') {
      return $aFrequencies ;   
    }

    // --- A2R ---
    $rFrequencies = array() ;
    foreach( $aFrequencies as $aSymbol => $count) {
      $rSymbol = $this->indexes['A'][$aSymbol]['R'] ;
      @ $rFrequencies[$rSymbol] += $count ;
    }
    if ($targetElementKind==='R') {
      return $rFrequencies ;
    }
    die(__FUNCTION__.': Unrecognized symbol kind "'.$targetElementKind.'"') ;        
  }
  
  /**
   * Return the number of distinct symbols in a given tSymbolSet 
   * @see getFrequencies for the documentation
   * @param $tSymbolSet? $tSymbolSet  
   * @param SymbolKind! $targetElementKind
   * @return Integer 
   */
  public function getSymbolCount($tSymbolSet=null,$targetElementKind) {
    return count($this->getFrequencies($tSymbolSet,$targetElementKind)) ;
  }
  
  
  /*--------------------------------------------------------------------------
   * Navigation up
   *--------------------------------------------------------------------------
   * Staring from a given symbol at a given level, indicates where this symbol
   * is used.
   */
  
  /**
   * Return the direct containers of a given element
   */
  public function getDirectContainers($element,$elementKind) {
    $previousKind = $this->getPreviousLevel($elementKind) ;
    if ($previousKind===null) {
      return array($element) ;
    }
    return array_keys($this->indexes[$elementKind][$element][$previousKind]) ;
  }
  
  /**
   * Return the direct containers of a given element
   */
  public function getContainersTree($element,$elementKind,$containerKind='T') {
    if ($elementKind==$containerKind) {
      return null;
    } else {
      $containerTree = array() ;
      $directContainers = $this->getDirectContainers($element,$elementKind) ;
      $previousKind = $this->getPreviousLevel($elementKind) ;
      foreach($directContainers as $directContainer) {
        $containerSubTree = $this->getContainersTree($directContainer,$previousKind,$containerKind) ;
        if ($containerSubTree===null) {
          $containerTree[]=$directContainer ;
        } else {
          $containerTree[$directContainer]=$containerSubTree ;
        }
      }
      return $containerTree ;
    }
  }
  
  /**
   *
   */
  public function treeToTxt($tree,$indent='  ',$level=0) {
    $margin = str_repeat($indent,$level) ;
    if (is_string($tree)) {
      return $margin . $tree."\n" ;
    } else {
      $out = '' ;
      foreach ($tree as $key=>$value) {
        if (is_string($key)) {
          $out .= $margin.$key.' => '."\n" ;
        }
        $out .= $this->treeToTxt($value,$indent,$level+1) ;
      }
      return $out ;
    }
  }
  
  
  /**
   * Return a tag cloud representing the frequencies of symbols in a given tSymbolSet 
   * @see getFrequencies for the documentation
   * @param $tSymbolSet? $tSymbolSet  
   * @param SymbolKind! $targetElementKind
   * @return HTMLString the tag cloud corresponding to symbol usage
   */
  public function getCloud($tSymbolSet=null,$targetElementKind) {
    return cloud($this->getFrequencies($tSymbolSet,$targetElementKind)) ;
  }
  

 
    
  

  /**
   * Build indexes from a corpus or load existing indexes from the file specified
   * @param DecomposableTextCorpus|Filename|null $corpusOrExistingIndexFilenameOrNull
   * If a corpus is given, then the index is build from this initial corpus.
   * If a string is given, it is assumed to be an existing indexes file name. Initialize
   * the indexes with it.
   * If null is provided the indexes are initialized to empty.
   * @param SymbolTable? $symbolTable an optional symbol table.
   */
  public function __construct($corpusOrExistingIndexFilenameOrNull=null, SymbolTable $symbolTable=null) {
    parent::__construct($corpusOrExistingIndexFilenameOrNull,$symbolTable) ;
  }
}







/* tag clouds
 http://www.smashingapps.com/2011/12/15/nine-excellent-yet-free-online-word-cloud-generators.html
*/

function cloud($frequencies) {
  $cloud = new TagCloud(count($frequencies)) ;
  foreach ($frequencies as $item => $count) {
    $cloud->addTag($item,$count) ;
  }
  return $cloud->cloud() ;
}





