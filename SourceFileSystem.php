<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'configs/SourceCode.config.php';
require_once 'HTML.php' ;
require_once 'Strings.php' ;
require_once 'Structures.php' ;
require_once 'Files.php' ;
require_once 'SourceCode.php' ;


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
   * Return the fileSystemPatternMatcher used match paths or null if non has been specified.
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
   * @param SourceDirectory! $sourcedirectory
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











/**
 * SourceDirectory.
 *
 */
abstract class SourceDirectory extends PropertyHolder {

  /**
   * @var Map*(String!,SomeFile!)? The map that given each
   * short file name (the basename), the corresponding SomeFile objects.
   * These objects are either SourceFile or NonSourceFile.
   * Computed once but on demand.
   */
  protected $fileMap ;

  /**
   * @var List*(SourceSubDirectory!)? The map that given each
   * short directory name (the basename) yields the corresponding
   * SubDirectory object.
   * Computed once but on demand.
   */
  protected $subDirectoryMap ;


  /**
   * @var DirectoryName! Relative path of the current directory (with respect to the base)
   */
  protected $directoryPath ;


  /**
   * All 'relative' pathname that are returned are relative to this base.
   * @return DirectoryName! The name of the base directory.
   */
  public abstract function getBase() ;


  /**
   * Direct Access to the top directory.
   */
  public abstract function getTopDirectory() ;

  /**
   * The fileSystemPatternMatcher used to identify file and directory kind
   * @return FileSystemPatternMatcher!
   */
  public abstract function getFileSystemMatcher() ;

  /**
   * Directory path (relative to the base)
   * @return DirectoryPath!
   */
  public function getDirectoryPath() {
    return $this->directoryPath ;
  }

  /**
   * Full path name of this directory
   * @return DirectoryName!
   */
  public function getFullDirectoryName() {
    return addToPath($this->getBase(),$this->getDirectoryPath()) ;
  }


  /* ???*/
  public function getFullFileName($relativeFileName) {
    return addToPath($this->getBase(),$relativeFileName) ;
  }

  /**
   * Make the path relative to the base
   * @param Path! $path a path
   * @return Path!
   */
  public function getRelativePath($path) {
    return substr($path,strlen($this->getBase())) ;
  }

  /**
   * Return the default output base if any
   * @return DirectotyName?
   */
  public function getDefaultOutputBase() {
    return $this->getTopDirectory()->getDefaultOutputBase() ;
  }

  
  
  /**
   * This function will be improved. Currently it just check the extension
   * TODO should this function be here? This is weird. It could be static
   * instead and in some file class. But may be we can use local information
   * stored in the current directory.
   *
   * @param $fullFileName
   * @return String! a language code if it is a source code or "" otherwise.
   */
  public function getFileKind($fullFileName) {
    $geshiLanguage = GeSHiExtended::getLanguageFromExtension(fileExtension($fullFileName)) ;
    return $geshiLanguage ;
  }


  /**
   * Return the map of files (directly) contained in the directory.
   * @return Map*(String!SomeFile!)! The map basename => SomeFile objects.
   */
  public function getFileMap() {
    if (!isset($this->fileMap)) {
      $this->fileMap=array() ;
      $fullFileNames=
        listFileNames(
            $this->getFullDirectoryName(),
            'file',   // only files
            null,     // No regular expression
            false,    // do not ignore dot files
            true,     // return file path not only basenames
            true) ;   // ignore dot directories

      foreach($fullFileNames as $fullFileName) {
        $relativeFileName = $this->getRelativePath($fullFileName) ;
        $properties = $this->getFileSystemMatcher()->matchPath('file',$relativeFileName) ;
        if (!isset($properties) || !isset($properties['elementKind'])) {
          $properties['elementKind']="unknown" ;
        }
        switch (trim($properties['elementKind'])) {
          
          case "source":
            $geshiLanguage = 
              isset($properties['geshiLanguage']) 
                ? trim($properties['geshiLanguage'])
                : null ;
            $element = new SourceFile($relativeFileName,$this,$properties,$geshiLanguage) ;
            break ;
            
          case "image":
            $element = new ImageFile($relativeFileName,$this,$properties) ;
            break ;
          
          case "archive":
            $element = new ArchiveFile($relativeFileName,$this,$properties) ;
            break ;           

          case "ignore":
            $element = null ;
            break ;
              
          case "unknown";
          default:
            $element = new NonSourceFile($relativeFileName,$this,$properties) ;
                   
        }
        if (isset($element)) {
          $this->fileMap[basename($relativeFileName)] = $element ;
        }
      }
    }
    return $this->fileMap ;
  }

  /**
   * Return the map of sub directories.
   * @var Map*(String!=>SubDirectory!)!
   */
  public function getDirectoryMap() {
    if (!isset($this->subDirectoryMap)) {
      $this->subDirectoryMap=array() ;
      $fullDirNames=
        listFileNames(
            $this->getFullDirectoryName(),
            'dir',        // only directory
            null,         // No regular expression
            false,        // do not ignore dot files
            true,         // return full path not only basenames
            true) ;       // ignore dot directories
      foreach($fullDirNames as $fullDirName) {
        $relativeDirName = $this->getRelativePath($fullDirName) ;
        // TODO: enable this line. path: should be implmented first
        // $properties = $this->getFileSystemMatcher()->matchPath('directory',$relativeDirName) ;
        if (!isset($properties) || !isset($properties['elementKind'])) {
          $properties['elementKind']="unknown" ;
        }
        switch (trim($properties['elementKind'])) {
          case 'ignore' :
            $element = null ;
            break ;
          
          case 'unknown' :
          default:
            $element = new SourceSubDirectory($relativeDirName,$this,$properties) ;
        }
        if (isset($element)) {
          $this->subDirectoryMap[basename($relativeDirName)] = $element ;
        }
      }
    }
    return $this->subDirectoryMap ;
  }


  public function getAllFileMap() {
    $all=$this->getFileMap() ;
    foreach($this->getDirectoryMap() as $dirBasename=>$dir) {
      $all = $all + array_change_keys($dir->getAllFileMap(),$dirBasename.'/') ;
    }
    echo "in ".$this->getDirectoryPath()." there is ".count($all)." files in total\n" ;
    return $all ;
  }
  
  public function getAllDirectoryMap() {
    
  }
  
  /*-------------------------------------------------------------------------------
   *   Summary of the directory
  *-------------------------------------------------------------------------------
  */

  
  /**
   * Summarize information about the fileMap
   * @param inOut>Map*(String!,Any!) $summary
   */
  protected function getFileSetSummary($setName,$fileSet,&$summary) {
    // Summarize information about files directly in this directory
    
    $setSummary = array() ;
    $setSummary['count'] = count($fileSet) ;
    $setSummary[$setName] = array() ;
    
    foreach(array("GeSHi.language","language","technology") as $mainProperty) { 
      $mainPropertyDistribution=array() ;
      foreach($fileSet as $fileShortName => $file) {
        $fileSummary = $file->getSummary() ;
        $filename=$fileSummary['path'] ;
        $setSummary[$setName][$fileShortName]=array() ;
        $setSummary[$setName][$fileShortName]['path']=$filename ;
      
        if (isset($fileSummary[$mainProperty])) {
          $geshiLanguage=$fileSummary[$mainProperty] ;
      
          $setSummary[$setName][$fileShortName][$mainProperty]=$geshiLanguage ;
      
          // initialize integer fields to 0 if necessary
          if (!isset($mainPropertyDistribution[$geshiLanguage])) {
            $mainPropertyDistribution[$geshiLanguage][$setName.'Count']=0 ;
            foreach($fileSummary as $key => $value) {
              if (is_numeric($value)) {
                $mainPropertyDistribution[$geshiLanguage][$key]=0;
              }
            }
          }
          $mainPropertyDistribution[$geshiLanguage][$setName][$fileShortName]['path']=$filename;
          // add integer fields.
          $mainPropertyDistribution[$geshiLanguage][$setName.'Count']++ ;
          foreach($fileSummary as $key => $value) {
            if (is_numeric($value)) {
              $mainPropertyDistribution[$geshiLanguage][$key] += $value;
            }
          }
        }
      }
      $setSummary[$mainProperty.'Distribution']=$mainPropertyDistribution ;
    }
    $summary[$setName]=$setSummary ;
  }
  
  protected function getDirectorySetSummary($setName,$directorySet,&$summary) {
    // Summarize information about direct subdirectories
    
    $summary[$setName] = array() ;
    $summary[$setName]['count'] = count($directorySet) ;
    foreach($directorySet as $dirShortName => $dir) {
      $summary[$setName][$dirShortName] = array() ;
      $summary[$setName][$dirShortName]['path'] = $dir->getDirectoryPath() ;
    }
  }
  
  public function getSummary() {
    if (!isset($this->summary)) {
      $summary = parent::getSummary() ;      
      $this->getFileSetSummary("directFiles",$this->getFileMap(),$summary) ;
      $this->getFileSetSummary("allFiles",$this->getAllFileMap(),$summary) ;
      
      $this->getDirectorySetSummary("directSubdirectories",$this->getDirectoryMap(),$summary) ;   
         
      $this->updatedSummary($summary) ;
    }
    return $this->summary ;
  }



  /*-------------------------------------------------------------------------------
   *   HTML Representation of this directory
  *-------------------------------------------------------------------------------
  */

  public function getHTML_Path($outputBase) {
    $html = '<div class="dirPath">' ;
    $ancestors=$this->getAncestors() ;
    $nbAncestors=count($ancestors) ;
    for ($i = 0; $i<$nbAncestors; $i++) {
      $html .= '<span class="dirName">' ;
      $basename = basename($ancestors[$i]->getDirectoryPath()) ;
      $relativePath = str_repeat('../', $nbAncestors-$i) ;
      $html .= '<a href="'.$relativePath.'">'.$basename.'</a>' ;
      $html .= '</span> > ';
    }

    $html .= '<span class="dirName currentDirName">' ;
    $html .= '<b>'.basename($this->getDirectoryPath()).'</b>' ;
    $html .= '</span>  ' ;
    $html .= '</div>' ;
    return $html ;
  }

  public function getHTML_DirectorySummary($outputBase) {
    $html = '<div class="dirSummary">' ;
    $html .= '<a href="index.summary.json">summary</a>' ;
    $html .= '</div>' ;
    return $html ;
  }

  public function getHTML_Listing($outputBase) {
    // add the listing box
    $html = '<div class="dirListing"><table border=1>' ;

    foreach($this->getDirectoryMap() as $shortdirname => $sourceDirectory) {
      $html .= '<tr class="dirItem">' ;
      $html .= '<td>DIR</td>' ;
      $html .= '<td><a href="'.$shortdirname.'/index.html"><b>'.$shortdirname.'</b></a></td>' ;
      $html .= '<td><a href="'.$shortdirname.'/index.summary.json">summary</a></td>' ;
      $html .= '<td></td>' ;
      $html .= '</tr>' ;
    }

    foreach($this->getFileMap() as $shortfilename => $someFile) {
      $html .= '<tr class="fileItem">' ;
      if ($someFile instanceof SourceFile) {
        $html .= '<td>SOURCE</td>' ;
      } else {
        $html .= '<td>NON SOURCE</td>' ;
      }
      $html .= '<td><a href="'.$shortfilename.'.html">'.$shortfilename.'</a></td>' ;
      $html .= '<td><a href="'.$shortfilename.'.summary.json">summary</a></td>' ;
      if ($someFile instanceof SourceFile) {
        $html .= '<td><a href="'.$shortfilename.'.txt">txt</a></td>';
      } else {
        $html .= '<td></td>' ;
      }
      $html .= '</tr>' ;
    }

    $html .= '</table></div>' ;
    return $html ;
  }


  public function getHTML($outputBase) {
    $html = '<div class="dirBox">' ;

    $html .= $this->getHTML_Path($outputBase) ;
    $html .= $this->getHTML_DirectorySummary($outputBase) ;
    $html .= $this->getHTML_Listing($outputBase) ;
    return $html ;
  }





  /*-------------------------------------------------------------------------------
   *   Generation for this directory
  *-------------------------------------------------------------------------------
  */


  /**
   * Generate elements for all files in this source directory
   * @param DirectoryName $outputDirectory
   */
  public function generate($outputBase=null) {
    // compute the output base
    if (!isset($outputBase)) {
      $outputBase = $this->getDefaultOutputBase() ;
      if ($outputBase===null) {
        die('SourceDirectory: not output base specified') ;
      }
    }
    $outputDirectory=addToPath($outputBase,$this->getDirectoryPath()) ;
    $outputDirectoryRootFileName=addToPath($outputDirectory,'index') ;

    // for each file generate corresponding derived files
    foreach($this->getFileMap() as $shortfilename => $someFile) {
      echo "File:    ".$shortfilename."\n" ;
      $someFile->generate($outputBase) ;
    }

    // for each directory generate what should be generated
    foreach($this->getDirectoryMap() as $shortdirname => $sourceDirectory) {
      echo "Dir:     ".$shortdirname."\n" ;
      $sourceDirectory->generate($outputBase) ;
    }
    saveFile($outputDirectoryRootFileName.'.summary.json',$this->getSummaryAsJson()) ;
    saveFile($outputDirectoryRootFileName.'.html',$this->getHTML($outputBase)) ;

  }

  /**
   * @param DirectoryName! $directoryPath the directory path relative to the base.
   */
  public function __construct($directoryPath,$properties=array()) {
    parent::__construct($properties) ;
    $this->directoryPath = $directoryPath ;
    $this->setProperty('fullPath',$this->getFullDirectoryName());
    $this->setProperty('path',$directoryPath);
    $this->setProperty('basename',basename($directoryPath));
  }

}








/**
 * SourceSubDirectory.
 */
class SourceSubDirectory extends SourceDirectory {
  /**
   * @var SourceDirectory! Always defined as we are in a subdirectory.
   */
  protected $parentDirectory ;
  /**
   * @var SourceTopDirectory! Always defined as we are in a subdirectory.
   */
  protected $topDirectory ;

  /**
   * Parent directory. That is either a SourceSubDirectory or SourceTopDirectory.
   * @return SourceDirectory!
   */
  public function getParentDirectory() {
    return $this->parentDirectory ;
  }

  /**
   * Top directory.
   * @return SourceTopDirectory! The top directory.
   */
  public function getTopDirectory() {
    return $this->topDirectory ;
  }


  public function getBase() {
    return $this->getTopDirectory()->getBase() ;
  }

  /**
   * The fileSystemPatternMatcher used to identify file and directory kind
   * @return FileSystemPatternMatcher!
   */
  public function getFileSystemMatcher() {
    return $this->getTopDirectory()->getFileSystemMatcher() ;
  }


  /**
   * List of ancestor source directories, the toplevel directory being first,
   * the current directory being excluded.
   * @return List*(SourceDirectory!) List of ancestors.
   */
  public function getAncestors() {
    $ancestors = $this->getParentDirectory()->getAncestors() ;
    $ancestors[] = $this->getParentDirectory() ;
    return $ancestors ;
  }


  /**
   * @param DirectoryName! $directoryPath the directory path relative to the base.
   * @parem SourceDirectory! $parentDirectory A source directory (either top or sub)
   */
  public function __construct($directoryPath,$parentDirectory,$properties=array()) {
    $this->parentDirectory = $parentDirectory ;    
    $this->topDirectory = $this->parentDirectory->getTopDirectory() ;
    parent::__construct($directoryPath,$properties) ;
  }

}



/**
 * Top level Source Directory.  By contrast to subdirectories we store
 * here various global information.
 *
 */
class SourceTopDirectory extends SourceDirectory {

  /**
   * @var DirectoryName! The name of the base directory.
   */
  protected $base ;

  /**
   * @var FileSystemPatternMatcher! a file system matcher used for matching path.
   */
  protected $fileSystemMatcher ;


  /**
   * @var DirectoryName? The default for the output base.
   */
  protected $defaultOutputBase ;

  /**
   * @var The results of the generation process.
   */
  protected $processingResults ;

  /**
   * @var Boolean! Indicates if the generation process should be traced
   * on the standard output.
   */
  protected $traceOnStdout ;


  /**
   * Top directory.
   * @return SourceTopDirectory! The top directory.
   */
  public function getTopDirectory() {
    return $this ;
  }

  public function getBase() {
    return $this->base;
  }

  /**
   * List of ancestor source directories. Returns empty here as this is the top level.
   * @return List*(SourceDirectory!) .
   */
  public function getAncestors() {
    return array() ;
  }

  /**
   * The fileSystemPatternMatcher used to identify file and directory kind
   * @return FileSystemPatternMatcher!
   */
  public function getFileSystemMatcher() {
    return $this->fileSystemMatcher ;
  }

  /**
   * @param DirectoryName! $basedir a directory that will serve as the base of
   * everything. That is all path will be relative to this base.
   *
   * @param DirectoryName! $directoryPath the directory to consider for analysis. Its
   * value is relative to the base.
   *
   * @param List*(String!)? $properties ???
   * 
   * @param 
   * 
   * @param DirectoryName! $defaultOutputBase the default base directory for
   * output. It is not necessary to specify it if generation is not to be used
   * of if a base is specified in the generate method. Default to null.
   *
   * @param $traceOnStdout? $traceOnStdout  as the generation can be quite
   * time consuming this parameter allow to trace the process via some output
   * on stdout. Default to true which means verbose mode. Otherwise nothing
   * is displayed.
   *
   */
  public function __construct($basedir,$directoryPath,$properties=array(),$fileSystemMatcher=null,$defaultOutputBase=null,$traceOnStdout=true) {
    parent::__construct($directoryPath,$properties) ;
    $this->base = $basedir ;
    if (isset($fileSystemMatcher)) {
      $this->fileSystemMatcher = $fileSystemMatcher ;
    } else {
      $this->fileSystemMatcher = new NeverMatchFileSystemPatternMatcher() ;
    }
    $this->defaultOutputBase = $defaultOutputBase ;
    $this->traceOnStdout     = $traceOnStdout ;
    $this->processingResults = array() ;
  }
}


