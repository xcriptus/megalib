<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'configs/SourceCode.config.php';
require_once 'HTML.php' ;
require_once 'Strings.php' ;
require_once 'Structures.php' ;
require_once 'Files.php' ;
require_once 'SourceCode.php' ;


/**
 * TODO this class should go elsewhere
 *
 */
class PropertyHolder {
  /**
   * A map of store properties
   * @var Map*(String!,Any!)! properties ;
   */
  protected $properties ;

  /**
   * A derived summary. Computed on demand but at once.
   * @var Map*(String!,Any!)! properties ;
   */
  protected $summary ;

  public function getProperties() {
    return $this->properties ;
  }

  public function getProperty($key,$defaultValue=null) {
    if (isset($this->properties[$key])) {
      return $this->properties[$key] ;
    } else {
      return $defaultValue ;
    }
  }

  public function setProperty($key,$value) {
    if (isset($key)&&isset($value)) {
      $this->properties[$key]=$value ;
    }
    return $this->properties ;
  }

  public function fusionProperties($map) {
    if (isset($map)) {
      $this->properties=array_fusion($this->properties,$map) ;
    }
    return $this->properties ;
  }

  public function getSummary() {
    if (!isset($this->summary)) {
      $this->summary = $this->getProperties() ;
    }
    return $this->summary ;
  }

  protected function updatedSummary($summary) {
    $this->summary = $summary ;
    return $this->summary ;
  }

  public function getSummaryAsJson($beautify=false) {
    return jsonEncode($this->getSummary(),$beautify) ;
  }


  public function __construct($properties=array()) {
    $this->properties=$properties ;
    $this->summary = null ;
  }
}



/**
 * Interface that both SourceFile and NonSourceFile implements. Such file can
 * be declared to be relative with a SourceDirectory in which case the filename
 * can be relative to the base of the SourceDirectory.
 *
 */
interface SomeFile {
  /**
   * Return the source directory if the source file have been declared within such
   * directory. Otherwise return null.
   * @return SourceDirectory? null or the source directory object.
   */
  public function getSourceDirectory() ;

  /**
   * Return the short filename of the file. It is relative to the source directory
   * if provided. Otherwise this is the same value as full filename.
   * @return Filename! the filename.
   */
  public function getShortFilename() ;

  /**
   * Return the full filename of the file. If the this source file pertains to
   * a source directory then this is the base of the directory + the short file name.
   * Otherwise it is the same as the short filename.
   */
  public function getFullFilename() ;


  /**
   * Return the fileSystemPatternMatcher used to match paths or null if none has been specified.
   * @return FileSystemPatternMatcher?
   */
  public function getFileSystemPatternMatcher() ;

  public function getGenerationResults() ;


  /**
   * Gompute where the derived files should go given the 'base' argument.
   * See the explaintion in the generate function descrbed below.
   * This offers various level of overloading but this is a bit tricky.
   * @param DirectoryName
   * @param Boolean|String|null $base
   */
  public function getOutputFileName($outputBase,$base) ;

  /**
   * Generate derived files associated with this file
   *
   * @param DirectoryName! $outputBase the directory where the derived files should go.
   * In principle this directory is outside the base for the source.
   *
   * @param Boolean|String|null $base @see parameter $base of rebasePath.
   * Here if nothing is specified, the base will take the value true if the
   * file is not inside a source directory, and false if it is. That is
   * by default the basename will be used if the file is not in a source directory
   * (so all files will be generated at the same level, leading to a flat structure),
   * otherwise the short name will be used, meaning that the output will be
   * isomorphic to the source directory.
   *
   * @return multitype:
   */
  public function generate($outputBase,$base=null) ;

}



class AbstractFile extends PropertyHolder implements SomeFile {
  /**
   * @var SourceDirectory? If set then this source file pertains to
   * a given source directory. It this case the relative filename
   * will be relative to the base of the source directory.
   */
  protected $sourceDirectory ;

  /**
   * @var Filename! The name of the source file.
   * If the source directory is specified then this filename is relative to the
   * base of the source directory. Otherwise it is a regular filename.
   */
  protected $filename ;

  /**
   * @var Map(Filename,Integer|String)? file generated and corresponding results
   */
  protected $generationResults ;



  /**
   * @see SomeFile interface
   */
  public function getSourceDirectory() {
    return $this->sourceDirectory ;
  }

  /**
   * @see SomeFile interface
   */
  public function getShortFilename() {
    return $this->filename ;
  }

  /**
   * @see SomeFile interface
   */
  public function getFullFilename() {
    if ($this->getSourceDirectory()!==null) {
      return addToPath($this->getSourceDirectory()->getBase(),$this->getShortFilename()) ;
    } else {
      return $this->getShortFilename() ;
    }
  }

  /**
   * Return the fileSystemPatternMatcher used match paths.
   * @return FileSystemPatternMatcher
   */
  public function getFileSystemPatternMatcher() {
    $dir = $this->getSourceDirectory() ;
    if (isset($dir)) {
      return $dir->getFileSystemPatternMatcher() ;
    } else {
      return null ;
    }
  }

  public function getGenerationResults() {
    return $this->generationResults ;
  }

  /**
   * Gompute where the derived files should go given the 'base' argument.
   * See the explaintion in the generate function descrbed below.
   * This offers various level of overloading but this is a bit tricky.
   * @param
   * @param Boolean|String|null $base
   */
  public function getOutputFileName($outputBase,$base) {
    $base = isset($base)
    ? $base
    : ($this->getSourceDirectory()===null) ; // trick here.
    return rebasePath($this->getShortFilename(),$outputBase,$base) ;
  }


  /**
   * Generate summary associated with this source file
   * @see interface SomeSource for documentation.
   */
  public function generate($outputBase,$base=null) {
    $outputfilename = $this->getOutputFileName($outputBase,$base) ;
    //-- generate the json summary
    $generated = array() ;
    saveFile(
        $outputfilename.'.summary.json',
        $this->getSummaryAsJson(),
        $generated) ;
    $this->generationResults = array_merge($this->generationResults,$generated) ;
    return $generated ;
  }

  public function __construct($filename,SourceDirectory $sourceDirectory=null,$properties=array()) {
    parent::__construct($properties) ;
    $this->filename = $filename ;
    $this->sourceDirectory = $sourceDirectory ;
    $this->generationResults = array() ;
    $this->setProperty('fullPath',$this->getFullFilename());
    $this->setProperty('path',$filename);
    $this->setProperty('basename',basename($filename));
    $this->setProperty('extension',fileExtension($filename)) ;
  }

}







/**
 * A source file. This class extends SourceCode but additionally a source file has a filename,
 * possibly relative to a specific SourceDirectory. It also has methods to generate various
 * files from the source code in a output directory.
 */
class SourceFile extends AbstractFile implements SomeFile {
  /**
   * @var SourceCode? The source code contained in the file.
   * Loaded only once but on demand.
   */
  protected $sourceCode ;

  /**
   * @var GeshiLanguage! the geshi language of this file.
   * This information is redundant with the language in the sourceCode object
   * if this one is loaded.
   */
  protected $geshiLanguage ;

  /**
   * @var String? an optional source id given as parameter.
   * This information is justed stored so that we can create the sourceCode
   * object. It may not be the same as the source object value as this one
   * is computed if non is given here.
   *
   */
  protected $sourceId ;

  /**
   * Get the sourceCode object for this source file.
   * This object is computed on demand.
   * If the file cannot be read then $this->error is set
   * and the text of the code will be an error message.
   * Its language will be text.
   * @return SourceCode! the sourceCode object.
   */
  public function getSourceCode() {
    if (!isset($this->sourceCode)) {
      $fullfilename = $this->getFullFilename() ;
      $text = file_get_contents($fullfilename) ;
      $geshiLanguage = $this->geshiLanguage ;
      if ($text===false) {
        $text = "The file ".$fullfilename." cannot be read." ;
        $geshiLanguage = "text" ;
        $this->error = 'cannot read file '.$fullfilename ;
        echo $this->error ;
      }
      $this->sourceCode = new SourceCode($text,$geshiLanguage,$this->sourceId) ;
    }
    return $this->sourceCode ;
  }



  /**
   * Get the summary of this source file
   */

  public function getSummary() {
    $basicSummary = parent::getSummary() ;
    $sourceCodeSummary = $this->getSourceCode()->getSummary() ;
    $summary = array_fusion($sourceCodeSummary,$basicSummary) ;
    return $this->updatedSummary($summary) ;
  }


  /**
   * Generate derived files associated with this source file
   * @see interface SomeSource for documentation.
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) {
    $generated = parent::generate($outputBase,$base) ;

    $outputfilename = $this->getOutputFileName($outputBase,$base) ;

    //----- generate html ------------------------------------------------------
    //-- generate the main html file with no fragment emphasis
    $sourceCode = $this->getSourceCode() ;
    $htmlBody = $sourceCode->getHTML() ;

    $simpleHeader = $sourceCode->getHTMLHeader() ;
    saveFile(
        $outputfilename.'.html',
        $simpleHeader.$htmlBody,
        $generated) ;

    //-- generate one html file per fragment
    if (isset($fragmentSpecs)) {
      // generate a file for each fragmentSpec
      foreach($fragmentSpecs as $fragmentName => $fragmentSpec) {
        $header = $sourceCode->getHTMLHeader($fragmentSpec,'background:#ffffaa ;') ;
        saveFile(
            $outputfilename.'__'.$fragmentName.'.html',
            $header.$htmlBody,
            $generated) ;
      }
    }

    //-- generate the json summary
    saveFile(
        $outputfilename.'.tokens.json',
        $sourceCode->getTokensAsJson(),
        $generated) ;

    //-- generate the raw text file
    saveFile(
        $outputfilename.'.txt',
        $sourceCode->getPlainSourceCode(),
        $generated) ;
    $this->generationResults = array_merge($this->generationResults,$generated) ;


    //-- we have finished with generation for this file so release the resource
    // to avoid out of memory errors.
    unset($this->sourceCode) ;

    return $generated ;
  }

  /**
   * Compute the geshi language to be associated to the source code based on the
   * file extension. If the extension is not found then "text" is returned.
   * This function is called by the constructor only if no language is defined.
   * In this case it can be assumed that the default language is "text" as this
   * class is about souce code.
   * @param FileName $filename
   * @return GeshiLanguage! geshi language code.
   */
  protected function computeGeshiLanguageFromExtension($filename) {
    $geshiLanguage = GeSHiExtended::getLanguageFromExtension(fileExtension($filename)) ;
    if ($geshiLanguage==='') {
      $geshiLanguage='text' ;
    }
    return $geshiLanguage ;
  }

  /**
   * Create a SourceFile potentially within a source directory.
   *
   * @param Filename! $filename The filename of the source
   *
   * @param SourceDirectory? $sourcedirectory The source directory in which
   * this file is connected. This parameter is optional.
   *
   * @param GeshiLanguage? $geshiLanguage Geshi language code.
   * If not provided then the language code will be computed via
   * the computeLanguageByExtension method.
   * If this fail then 'text' will be taken as default. That is we assume
   * that this is a source file otherwise this object should not be
   * created on the first place.
   *
   * @param String? $sourceid see the constructor of SourceCode.
   *
   * @return after calling this constructor, $this->error() should be called.
   * If it returns false everything is fine. Otherwise it is an error message.
   */
  public function __construct($filename,SourceDirectory $sourcedirectory=null,$properties=array(),$geshiLanguage=null,$sourceId=null) {
    parent::__construct($filename,$sourcedirectory,$properties) ;
    $this->sourceId = $sourceId ;
    if (!isset($geshiLanguage)) {
      $this->geshiLanguage = $this->computeGeshiLanguageFromExtension($filename) ;
    } else {
      $this->geshiLanguage = $geshiLanguage ;
    }
    $this->setProperty('geshiLanguage',$geshiLanguage);
    $fullfilename = $this->getFullFilename() ;
  }

}



/**
 * Non source file.
 * Currently this class do not do anything usefull. It is just used for by SourceDirectory
 * in the case where non source file are found.
 */
class NonSourceFile extends AbstractFile implements SomeFile {

  public function __construct($filename,SourceDirectory $sourceDirectory=null,$properties=array()) {
    parent::__construct($filename,$sourceDirectory,$properties) ;
  }

}





class ImageFile extends NonSourceFile implements SomeFile {
  public function __construct($filename,SourceDirectory $sourceDirectory=null,$properties=array()) {
    parent::__construct($filename,$sourceDirectory,$properties) ;
  }
}

class ArchiveFile extends NonSourceFile implements SomeFile {
  public function __construct($filename,SourceDirectory $sourceDirectory=null,$properties=array()) {
    parent::__construct($filename,$sourceDirectory,$properties) ;
  }
}

class BinaryFile extends NonSourceFile implements SomeFile {
  public function __construct($filename,SourceDirectory $sourceDirectory=null,$properties=array()) {
    parent::__construct($filename,$sourceDirectory,$properties) ;
  }
}





