<?php defined('_MEGALIB') or die("No direct access") ;


require_once 'Decomposers.php';

/**
 * Text corpuses are set of texts. These corpus are to be used in
 * conjuction with the module Symbol.php. Here are defined the
 * interfaces, some general implementations, as well as corpuses for
 * special purposes such as filename corpus (texts are formed by
 * filenames), source corpus, etc.
 */



/*------------------------------------------------------------------------------
 *   Text corpuses
 *------------------------------------------------------------------------------
 * Interface and general implementations
 */


/**
 * A text corpus.
 * Represents a (virtual) map of textId to texts. The implementation 
 * have all the liberty to get texts on demand, store them, etc.
 */
interface TextCorpus  {
  /**
   * List the text ids in the corpus.
   * @return Set*(tSymbol!)! the list of ids.
   */
  public function ids() ;
  /**
   * Return for a given textId the corresponding text.
   * @param tSymbol! the id of the text, also called tSymbol for consistency with levels of symbols
   * @return Text! a frequency map of qualified symbols in the text
   */
  public function text($textId) ;
}


/**
 * A decomposable text corpus. That is a text corpus
 * that know for each textId how to decompose the corresponding text.
 */
interface DecomposableTextCorpus extends TextCorpus {
  /**
   * Indicates which decomposer to use for a given text id.
   * This method can return the same decomposer for all text ids.
   * Note that the actual type of Decomposer, that is TextDecomposer or
   * TextSymbol decomposer is important but is left open, so that
   * the class can be used in different context.
   * @return Decomposer! the decomposer to apply
   */
  public function decomposer($textId) ;
}


abstract class AbstractDecomposableTextCorpus implements DecomposableTextCorpus {
  /**
   * @var Map*(tSymbol!,TextDecomposer!)! the decomposer for each language
   */
  protected $mapOfDecomposers ;
  protected $defaultDecomposer ;
  public function addDecomposer($textId,$decomposer) {
    $this->mapOfDecomposers[$textId]=$decomposer ;
  }
  public function decomposer($textId) {
    if (isset($this->mapOfDecomposers[$textId])) {
      return $this->mapOfDecomposers[$textId] ;
    } else {
      return $this->defaultDecomposer ;
    }
  }
  public function setDefaultDecomposer($decomposer) {
    return $this->defaultDecomposer = $decomposer ;
  }
  /**
   * @param Map*(tSymbol,Decomposer)|Decomposer|null $decomposerMapOrDefaultDecomposer
   */
  public function __construct($decomposerMapOrDefaultDecomposer=null) {
    if (is_array($decomposerMapOrDefaultDecomposer)) {
      $this->mapOfDecomposers = $decomposerMapOrDefaultDecomposer ;
      $this->defaultDecomposer = null ;
    } else {
      $this->mapOfDecomposers = array() ;
      $this->defaultDecomposer = $decomposerMapOrDefaultDecomposer ;
    }
  }
}

/**
 * Corpus where the text is get on demand via a given function. This implementation
 * is suitable for large corpus where we do not want to store text.
 */
abstract class AbstractTextOnDemandCorpus extends AbstractDecomposableTextCorpus {
  protected $ids ;
  public function ids() {
    return $this->ids ;
  }
  public function addId($id) {
    if (!in_array($id,$this->ids)) {
      $this->ids[] = $id ;
    }
  }
  /**
   * @param Set*(tSymbol!)? $ids
   * @param Fun(tSymbol->Text!)! $getTextFun the function to get the text on demand
   * @param Map*(tSymbol,Decomposer)|Decomposer|null $decomposerMapOrDefaultDecomposer
   */
  public function __construct($ids=array(),$decomposerMapOrDefaultDecomposer=null) {
    $this->ids = $ids ;
    parent::__construct($decomposerMapOrDefaultDecomposer) ;
  }
}

/**
 * Corpus where the map of text is stored. Not suitable for large corpus.
 */
class StoredTextCorpus extends AbstractDecomposableTextCorpus implements DecomposableTextCorpus {
  /**
   * @var Map*(tSymbol!,Text!)! all the texts
   */
  protected $mapOfText ;
  public function ids() {
    return array_keys($this->mapOfText) ; 
  }
  public function text($textId) {
    return $this->mapOfText[$textId] ;
  }
  public function addText($textId,$text) {
    $this->mapOfText[$textId] = $text ;
  }
  public function __construct($mapOfText=array(),$decomposerMapOrDefaultDecomposer=null) {
    parent::__construct($decomposerMapOrDefaultDecomposer) ;
    $this->mapOfText = $mapOfText ;
  }
}








/*------------------------------------------------------------------------------
 *   Filenames corpus
 *------------------------------------------------------------------------------
 * Each text is a list of filenames. 
 * Suitable for filename symbol analysis.
 */

/**
 * Filenames corpus where a set of directories have as text the file names
 * contained in these directories. In this class the 'top level' directories
 * can by at arbitrary level in the file system. If you want to get all
 * subdirectories use instead the subclass SubDirectoriesFilenameCorpus  
 * Texts are constituted by the list of filenames into each 'top level' directories
 * with one line per filename. The $findFileParameters can be used to adjust which
 * file are selected, if their basename, their paths are to be collected, etc. 
 * TextIds are top level directory names
 */
class FilenamesCorpus extends StoredTextCorpus {
  /**
   * Create a Filename corpus based on some toplevel directories
   * @param Set*(DirectoryName)!  $topLevelDirectories
   * @param Map(String!,String!)? $findFileParameters indicates what to put in the text. 
   * This parameter is the one used for the findFiles. @see findFiles
   */
  public function __construct($topLevelDirectories,$findFileParameters=array('apply'=>'basename'),$decomposerMapOrDefaultDecomposer=null) {
    parent::__construct(array(),$decomposerMapOrDefaultDecomposer) ;
    if (!is_array($topLevelDirectories)) {
      echo __FUNCTION__.": the value above is not a array of directories" ;
      exit(10) ;
    }
    
    foreach($topLevelDirectories as $topLevelDirectory) {
      $text = implode("\n",findFiles($topLevelDirectory,$findFileParameters)) ;
      $this->addText($topLevelDirectory,$text) ;
    } 
  }
}

/**
 * Given a directory, all direct subdirectories will be used to constiture the
 * different texts.
 */
class SubDirectoriesFilenamesCorpus extends FilenamesCorpus {
  /**
   * Create a Filename corpus based on some toplevel directories
   * @param Directory!  $directory
   * @param Map(String!,String!)? $findFileParameters indicates what to put in the text.
   * This parameter is the one used for the findFiles. @see findFiles
   */
  public function __construct($directory,$findFileParameters=array('apply'=>'basename'),$decomposerMapOrDefaultDecomposer=null) {
    if (!is_dir($directory)) {
      echo __FUNCTION__.": $directory is not a directory" ;
      exit(10) ;
    } 
    $subdirectories = findDirectFiles($directory,array("types"=>"dir")) ;
    parent::__construct($subdirectories,$findFileParameters,$decomposerMapOrDefaultDecomposer) ;
  }
}




/*------------------------------------------------------------------------------
 * FileContent corpus
 *------------------------------------------------------------------------------
 * A corpus where file content is used as text.
 * The whole content is used.
 */

/**
 * A corpus of file content.
 * File contents are get on demand and not stored. 
 */
class FileContentsCorpus extends AbstractTextOnDemandCorpus {
  public function text($id) {
    return file_get_contents($id) ;
  }
  public function __construct($filenames,$decomposerMapOrDefaultDecomposer=null) {
    parent::__construct($filenames,$decomposerMapOrDefaultDecomposer) ;
  }
} 

class DirectoryFileContentsCorpus extends FileContentsCorpus {
  public function __construct($directory, $findFileParameters, $decomposerMapOrDefaultDecomposer=null) {
    $findFileParameters['types'] = "file" ;
    $filesnames = findFiles($directory,$findFileParameters) ;
    parent::__construct($filesnames,$decomposerMapOrDefaultDecomposer) ;
  }
  
}






/*------------------------------------------------------------------------------
 * Tokenized source corpus produced by the class GeSHI. @see SourceCode.php
 *------------------------------------------------------------------------------
 * Texts are typically source code.
 */

class TokenizedSourceCodeDirectoryCorpus extends AbstractTextOnDemandCorpus {
  /**
   * @var Set*(String!)! token classes to be included. @see SourceCode.php
   */
  protected $selectedTokenClasses ;
  public function text($jsonTokenFile) {
    $json = file_get_contents($jsonTokenFile) ;
    $tokens = jsonDecodeAsMap($json) ;
    $selectedTokenTexts = array() ;
    foreach ($tokens as $token) {
      if (in_array($token['class'],$this->selectedTokenClasses)) {
        $selectedTokenTexts[]=$token['text'] ;
      }
    }
    return implode("\n",$selectedTokenTexts) ;
  }
  
  public function __construct($directory,$decomposerMapOrDefaultDecomposer=null,$selectedTokenClasses=array('kw')) {
    $this->selectedTokenClasses = $selectedTokenClasses ;
    $files = findFiles($directory,array("types"=>"file",'pattern'=>'endsWithOne .tokens.json')) ;
    parent::__construct($files,$decomposerMapOrDefaultDecomposer) ;
  }
}