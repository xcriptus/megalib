<?php defined('_MEGALIB') or die("No direct access") ;


function cloud($frequencies) {
  $cloud = new TagCloud(count($frequencies)) ;
  foreach ($frequencies as $item => $count) {
    $cloud->addTag($item,$count) ;
  }
  return $cloud->cloud() ;
}


/**
 * extract identifier occurrences from a text.
 * Identifiers are segments that are between nonIdRegExpr (see below).
 * No processing is done on these identifiers.
 * Does not apply any kind of lexical analysis to avoid comments, strings, etc.
 * @param String $text The text to analyse.
 * @param RegExpr? $nonIdRegexpr non ids segments that will be ignored.
 * Default to /[^a-zA-Z_]+/
 * @param 'alpha'|'frequence' $order order of the . Default to 'alpha'
 * @return Map(String!,Integer>0!)! The frequency map of each identifiers.
 */
function idsFrequencies($text,$idRegExpr='/[a-zA-Z_]\w*/',$order='alpha') {
  if (preg_match_all($idRegExpr,$text,$matches,PREG_PATTERN_ORDER)) {
    $ids=$matches[0] ;
    $ids = array_count_values($ids) ;
    switch($order) {
      case 'alpha':
        ksort($ids) ;
        break ;
      case '':
        arsort($ids) ;
        break ;
      default :
        
    }
    return $ids ;
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

/* tag clouds 
http://www.smashingapps.com/2011/12/15/nine-excellent-yet-free-online-word-cloud-generators.html
*/
  



interface SymbolDecomposer {
  /**
   * Extract qualified symbols from a text
   * @param String $text
   * @return Map*(String!,Integer>=1)! a frequency map of qualified symbols in the text
   */
  public function textToQualifiedSymbolFrequencies($text) ;
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
   */
  public function atomicSymbolToRoot($atomicSymbol) ;
}



class RegExprBasedSymbolDecomposer {
  protected $qualifier ;
  protected $qualifiedSymbolSeparatorRegExprPart ;
  protected $atomicSymbolRemoveRegExpr ;
  protected $stemmer ;

  public function textToQualifiedSymbolFrequencies($text) {
    return idsFrequencies($text,'/\w+(?:'.$this->qualifiedSymbolSeparatorRegExprPart.'\w+)*/') ;
  }
  public function qualifiedSymbolToCompositeSymbol($qualifiedSymbol) {
    return explode($this->qualifier,$qualifiedSymbol) ;
  }
  public function compositeSymbolToAtomicSymbol($compositeSymbol) {
    return idSegments($compositeSymbol,null,$this->atomicSymbolRemoveRegExpr) ;
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
 * TextMap == Map*(TSymbol!,Text!)
 * TSymbol == Scalar!
 * Text == String
 * 
 * T2M : TSymbol -> Map*(QSymbol,Integer>=1)
 * Q2C : QSymbol -> List*(CSymbol)
 * C2A : CSymbol -> List*(ASymbol)
 * A2R : ASymbol -> RSymbol
 * 
 * T2C : TSymbol )> Map*(CSymbol,Integqer>=1)
 * 
 *   TextId                     (TSymbol)
 *     * QualifiedSymbol        (QSymbol)
 *         * CompositeSymbol    (CSymbol)
 *             * AtomicSymbol   (ASymbol)
 *                * RootSymbol  (RSymbol)
 *
 *
 */
class CoreSymbolIndexes {

  protected $levelNames = array('Text','Qualified','Composite','Atomic','Root') ;
  protected $levels     = array('T','Q','C','A','R') ;
  protected $indexes ;
  
  protected $currentSymbolDecomposer ;

  public function setSymbolDecomposer(SymboDecomposer $decomposer) {
    $this->currentSymbolDecomposer = $decomposer ;
  }
  
  public function getLevelIndex($kind) {
    $index = array_search($kind,$this->levels) ;
    if ($index===false) {
      die(__FUNCTION__.': no level '.$kind) ;
    }
    return $index ;
  }
  
  public function getLevelName($kind) {
    return $this->levelNames[$this->getlevelIndex($kind)] ;
  }
  
  public function getNextLevels($level='T',$toLevelOrLength='R') {
    $index = $this->getLevelIndex($level) ;
    if (is_integer($toLevelOrLength)) {
      $length = $toLevelOrLength ;
    } else {
      $toIndex = $this->getLevelIndex($toLevelOrLength) ;
      $length = $toIndex-$index ;
    }
    return array_slice($this->levels,$index+1,$length) ;
  }  
  
  public function getNextLevel($level) {
    $index = $this->getLevelIndex($level) ;
    if ($index+1<count($this->levels)){
      return $this->levels[$index+1] ;
    } else {
      return null ;
    }
  }
  
  public function getPreviousLevel($level) {
    $index = $this->getLevelIndex($level) ;
    if ($index-1>=0){
      return $this->levels[$index-1] ;
    } else {
      return null ;
    }
  }
  
  public function getPreviousLevels($level='R',$toLevelOrLength='T') {
    $index = $this->getLevelIndex($level) ;
    if (is_integer($toLevelOrLength)) {
      $length = $toLevelOrLength ;
    } else {
      $toIndex = $this->getLevelIndex($toLevelOrLength) ;
      $length = $index-$toIndex ;
    }
    return array_reverse(array_slice($this->levels,$index-$length,$length)) ;
  }
  
  /**
   * @param Map*(TextId!,Text)! $textMap
   * @return List*(QualifiedSymbol!)! new qualified symbols added 
   */
  public function addTexts($textMap) {
    $newQualifiedSymbols = array() ;
    // for all new texts
    foreach($textMap as $tSymbol => $text) {
      if (!isset($this->indexes[$tSymbol])) {
        // extract qualified symbols
        $frequencies = $this->currentSymbolDecomposer->textToQualifiedSymbolFrequencies($text) ;
        foreach($frequencies as $qSymbol => $count) {
          if (!isset($this->indexes['Q'][$qSymbol])) {
            $newQualifiedSymbols[]=$qSymbol ;
          }
          @ $this->indexes['Q#'][$qSymbol] += $count ;
          $this->indexes['Q'][$qSymbol]['T'][$tSymbol] = $count ;
          $this->indexes['T'][$tSymbol]['Q'][$qSymbol] = $count ;
          @ $this->indexes['T#'][$tSymbol] += $count ;
        }
      }
    }
    $this->addQualifiedSymbols($newQualifiedSymbols) ;
    return $newQualifiedSymbols ;
  }


  public function addQualifiedSymbols($qualifiedSymbols) {
    $newCompositeSymbols = array() ;
    // for all qualified symbols
    foreach($qualifiedSymbols as $qSymbol) {
      if (!isset($this->indexes['Q'][$qSymbol]['C'])) {
        // split qualified symbols into compound symbols
        $cSymbols = $this->currentSymbolDecomposer->qualifiedSymbolToCompositeSymbol($qSymbol) ;
        $this->indexes['Q'][$qSymbol]['C'] = $cSymbols ;
        $this->indexes['Q'][$qSymbol]['cLength'] = count($cSymbols) ;
        @ $this->indexes['cLength'][count($cSymbols)][] = $qSymbol ;
        foreach($cSymbols as $cSymbol) {
          if (!isset($this->indexes['C'][$cSymbol])) {
            $newCompositeSymbols[] = $cSymbol ;
          }
          @ $this->indexes['C'][$cSymbol]['Q'][$qSymbol] += 1 ;
        }
      }
    }
    $this->addCompositeSymbols($newCompositeSymbols) ;
    return $newCompositeSymbols ; 
  }

  public function addCompositeSymbols($compositeSymbols) {
    $newAtomicSymbols = array();
    // for all compound symbols found in the previous step
    foreach($compositeSymbols as $cSymbol) {
      if (!isset($this->indexes['C'][$cSymbol]['A'])) {
        // split compound symbols into atomic symbols
        $aSymbols = $this->currentSymbolDecomposer->compositeSymbolToAtomicSymbol($cSymbol) ;
        $this->indexes['C'][$cSymbol]['A'] = $aSymbols ;
        $this->indexes['C'][$cSymbol]['aLength'] = count($aSymbols) ;
        @ $this->indexes['aLength'][count($aSymbols)][] = aSymbols ;
        foreach ($aSymbols as $aSymbol) {
          if (!isset($this->indexes['A'][$aSymbol])) {
            $newAtomicSymbols[] = $aSymbol ;
          }
          @ $this->indexes['A'][$aSymbol]['C'][$cSymbol] += 1 ;
        }
      }
    }
    $this->addAtomicSymbols($newAtomicSymbols) ;
    return $newAtomicSymbols ;
  }

  public function addAtomicSymbols($atomicSymbols) {
    // for all atomic symbols found in the previous step
    foreach($atomicSymbols as $aSymbol) {
      if (!isset($this->indexes['A'][$aSymbol]['R'])) {
        $rSymbol = $this->currentSymbolDecomposer->atomicSymbolToRootSymbol($aSymbol) ;
        $this->indexes['A'][$aSymbol]['R']=$rSymbol ;
        @ $this->indexes['R'][$rSymbol]['A'][$aSymbol] += 1 ;
      }
    }
  }

  public function __construct($currentSymbolDecomposer=null) {
    $this->indexes=array() ;
    $this->currentSymbolDecomposer=$currentSymbolDecomposer ;
  }
}


// texts -> (qSymbols -> count)
// qSymbols  -> List+(cSymbols)


//  
class SymbolIndexes extends CoreSymbolIndexes {
  
  // TSymbol -> Map*(QSymbol,Integer>=1)
  // QSymbol -> List*(CSymbol)
  // CSymbol -> List*(ASymbol)
  // ASymbol -> RSymbol
  
  public function getSymbols($kind) {
    return array_keys($this->indexes[$kind]) ;
  }
  
  
  public function getFrequencies($tSymbol,$kindElement) {
    
    if (isset($tSymbol)) {
      $qFrequencies =  $this->indexes['T'][$tSymbol]['Q'] ;
    } else {
      $qFrequencies = $this->indexes['Q#'] ;
    }
    if ($kindElement==='Q') {
      return $qFrequencies ;
    }
    
    $cFrequencies = array() ;
    foreach( $qFrequencies as $qSymbol => $count) {
      $cSymbolList = $this->indexes['Q'][$qSymbol]['C'] ;
      foreach( $cSymbolList as $cSymbol ) {
        @ $cFrequencies[$cSymbol] += $count ;
      }
    }
    if ($kindElement==='C') {
      return $cFrequencies ;   
    }
    
    $aFrequencies = array() ;
    foreach( $cFrequencies as $cSymbol => $count) {
      $aSymbolList = $this->indexes['C'][$cSymbol]['A'] ;
      foreach( $aSymbolList as $aSymbol ) {
        @ $aFrequencies[$aSymbol] += $count ;
      }
    }
    if ($kindElement==='A') {
      return $aFrequencies ;   
    }
    
    $rFrequencies = array() ;
    foreach( $aFrequencies as $aSymbol => $count) {
      $rSymbol = $this->indexes['A'][$aSymbol]['R'] ;
      @ $rFrequencies[$rSymbol] += $count ;
    }
    if ($kindElement==='R') {
      return $rFrequencies ;
    }
    die(__FUNCTION__.': Unrecognized symbol kind "'.$kindElement.'"') ;        
  }

  public function getDirectContainers($element,$elementKind) {
    $previousKind = $this->getPreviousLevel($elementKind) ;
    if ($previousKind===null) {
      return array($element) ;
    }
    return array_keys($this->indexes[$elementKind][$element][$previousKind]) ;
  }
  
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
    
  
  
  public function getSymbolCount($tSymbol,$kindElement) {
    return count($this->getFrequencies($tSymbol,$kindElement)) ;
  }
  
  public function getCloud($tSymbol,$kindElement) {
    return cloud($this->getFrequencies($tSymbol,$kindElement)) ;
  }
  
  public function textIdToQualifiedSymbolFrequencies($textId) {
    return $this->indexes['texts'][$textId]['qSymbols'] ;
  }
  
  public function qualifiedSymbolToCompositeSymbolSequence() {
    
  }
  
  
    
  public function __construct($currentSymbolDecomposer=null) {
    parent::__construct($currentSymbolDecomposer) ;
  }
}
