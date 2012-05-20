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
   * @param Map*(String!,RangeString!)? $fragmentSpecs a map that give for
   * each fragment id (an arbitrary string that will appear in a filename), the
   * range of lines considered. If null then this parameter is ignored.
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
  public function generate($outputBase,$fragmentSpecs=null,$base=null) ;

}



/**
 * A source file. This class extends SourceCode but additionally a source file has a filename,
 * possibly relative to a specific SourceDirectory. It also has methods to generate various
 * files from the source code in a output directory.
 */
class SourceFile extends SourceCode implements SomeFile {
  /**
   * @var SourceDirectory? If set then this source file pertains to
   * a given source directory. In this case the relative filename
   * will be relative to the base of the source directory.
   */
  private $sourceDirectory ;

  /**
   * @var Filename! The name of the source file.
   * If the source directory is specified then this filename is relative to the
   * base of the source directory. Otherwise it is a regular filename.
   */
  private $filename ;

  /**
   * @var Map(Filename,Integer|String)? file generated and corresponding results
   */
  private $generationResults ;


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

  /**
   * Overload the method to add filename as an additional field.
   */

  public function getSummary($tokens=null) {
    $summary = parent::getSummary($tokens) ;
    $summary['filename']=$this->getShortFilename() ;
    if ($this->getSourceDirectory()===null) {
      $summary['fullFilename']=$this->getFullFilename() ;
    }
    return $summary ;
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
   * Generate derived files associated with this source file
   * @see interface SomeSource for documentation.
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) {
    $outputfilename = $this->getOutputFileName($outputBase,$base) ;

    $htmlBody = $this->getHTML() ;

    $generated=array() ;
    //----- generate html ------------------------------------------------------
    //-- generate the main html file with no fragment emphasis
    $simpleHeader = $this->getHTMLHeader() ;
    saveFile(
        $outputfilename.'.html',
        $simpleHeader.$htmlBody,
        $generated) ;

    //-- generate one html file per fragment
    if (isset($fragmentSpecs)) {
      // generate a file for each fragmentSpec
      foreach($fragmentSpecs as $fragmentName => $fragmentSpec) {
        $header = $this->getHTMLHeader($fragmentSpec,'background:#ffffaa ;') ;
        saveFile(
            $outputfilename.'__'.$fragmentName.'.html',
            $header.$htmlBody,
            $generated) ;
      }
    }

    //-- generate the json summary
    saveFile(
        $outputfilename.'.summary.json',
        $this->getTokensAndSummaryAsJson(),
        $generated) ;

    //-- generate the raw text file
    saveFile(
        $outputfilename.'.txt',
        $this->getPlainSourceCode(),
        $generated) ;

    $this->generationResults = array_merge($this->generationResults,$generated) ;


    //-- we have finished with generation for this file so release the resource
    // to avoid out of memory errors.
    $this->releaseMemory() ;

    return $generated ;
  }

  /**
   * Compute the language to be associated to the source code based on the
   * file extension. If the extension is not found then "text" is returned.
   * This function is called by the constructor only if no language is defined.
   * In this case it can be assumed that the default language is "text" as this
   * class is about souce code.
   * @param FileName $filename
   * @return String! geshi language code.
   */
  protected function computeLanguageByExtension($filename) {
    $language = GeSHiExtended::getLanguageFromExtension(fileExtension($filename)) ;
    if ($language==='') {
      $language='text' ;
    }
    return $language ;
  }

  /**
   * Create a SourceFile potentially within a source directory.
   *
   * @param Filename! $filename The filename of the source
   *
   * @param SourceDirectory! $sourcedirectory
   *
   * @param String? $language Geshi language code. If not provided then
   * the language code will be computed via the computeLanguageByExtension
   * method.
   * If this fail then 'text' will be taken as default. That is we assume
   * that this is a source file otherwise this object should not be
   * created on the first place.
   *
   * @param String? $sourceid see the constructor of SourceCode.
   *
   * @return after calling this constructor, $this->error() should be called.
   * If it returns false everything is fine. Otherwise it is an error message.
   */
  public function __construct($filename,SourceDirectory $sourcedirectory=null,$language=null,$sourceid=null) {
    $this->filename = $filename ;
    $this->generationResults = array() ;
    $this->sourceDirectory = $sourcedirectory ;
    if (!isset($language)) {
      $language = $this->computeLanguageByExtension($filename) ;
    }
    $fullfilename = $this->getFullFilename() ;
    $text = file_get_contents($fullfilename) ;
    if ($text===false) {
      $this->error = 'cannot read file '.$fullfilename ;
      echo $this->error ;
    }
    parent::__construct($text,$language,$sourceid) ;
  }

}



/**
 * Non source file.
 * Currently this class do not do anything usefull. It is just used for by SourceDirectory
 * in the case where non source file are found.
 */
class NonSourceFile implements SomeFile {
  /**
   * @var SourceDirectory? If set then this source file pertains to
   * a given source directory. It this case the relative filename
   * will be relative to the base of the source directory.
   */
  private $sourceDirectory ;

  /**
   * @var Filename! The name of the source file.
   * If the source directory is specified then this filename is relative to the
   * base of the source directory. Otherwise it is a regular filename.
   */
  private $filename ;

  /**
   * @var Map(Filename,Integer|String)? file generated and corresponding results
   */
  private $generationResults ;


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
   * Compute where the derived files should go given the 'base' argument.
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

  public function getSummary() {
    $s = array(
        "filename" => $this->getShortFilename(),
        "language" => "",
        "size" => filesize($this->getFullFilename())
    ) ;
    return $s ;
  }

  public function getSummaryAsJson() {
    return json_encode($this->getSummary()) ;
  }

  /**
   * Generate derived files associated with this source file
   * @see interface SomeSource for documentation.
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) {
    $outputfilename = $this->getOutputFileName($outputBase,$base) ;
    //-- generate the json summary
    $generated = array() ;
    saveFile(
        $outputfilename.'.html',
        "<html>Sorry, the content of <b>".basename($this->getShortFilename())." cannot be rendered</html>",
        $generated) ;
    saveFile(
        $outputfilename.'.summary.json',
        $this->getSummaryAsJson(),
        $generated) ;
    $this->generationResults = array_merge($this->generationResults,$generated) ;
    return $generated ;
  }

  public function __construct($filename,SourceDirectory $sourceDirectory=null) {
    $this->filename = $filename ;
    $this->sourceDirectory = $sourceDirectory ;
    $this->generationResults = array() ;
  }

}





/**
 * SourceDirectory.
 *
 */
abstract class SourceDirectory {

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
    // get the language
    $language = GeSHiExtended::getLanguageFromExtension(fileExtension($fullFileName)) ;
    //    if ($language==='javascript') {
    //      $language='' ;
    //    }
    return $language ;
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
          null,         // No regular expression
          false,        // do not ignore dot files
          true,         // return file path not only basenames
          true) ;       // ignore dot directories

      foreach($fullFileNames as $fullFileName) {
        $relativeFileName = $this->getRelativePath($fullFileName) ;
        $fileProperties = $this->getFileSystemMatcher()->matchPath('file',$relativeFileName) ;
        // TODO do something with these properties...
        $language=$this->getFileKind($fullFileName) ;
        if ($language==="") {
          $someFile = new NonSourceFile($relativeFileName,$this) ;
        } else {
          $someFile = new SourceFile($relativeFileName,$this,$language) ;
        }
        $this->fileMap[basename($relativeFileName)] = $someFile ;
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
        $this->subDirectoryMap[basename($relativeDirName)] =
        new SourceSubDirectory($relativeDirName,$this) ;
      }
    }
    return $this->subDirectoryMap ;
  }



  /*-------------------------------------------------------------------------------
   *   Summary of the directory
  *-------------------------------------------------------------------------------
  */

  public function getSummary() {
    $summary = array() ;

    // Summarize information about files directly in this directory
    $fileMap = $this->getFileMap() ;
    $summary["nbOfFiles"] = count($fileMap) ;
    $summary["files"] = array() ;
    $languageDistribution=array() ;
    foreach($fileMap as $fileShortName => $file) {
      $fileSummary = $file->getSummary() ;
      $language=$fileSummary['language'] ;
      $filename=$fileSummary['filename'] ;
      $summary['files'][$fileShortName]=array() ;
      $summary['files'][$fileShortName]['filename']=$filename ;
      $summary['files'][$fileShortName]['language']=$language ;

      // initialize integer fields to 0 if necessary
      if (!isset($languageDistribution[$language])) {
        $languageDistribution[$language]['nbFiles']=0 ;
        foreach($fileSummary as $key => $value) {
          if (is_numeric($value)) {
            $languageDistribution[$language][$key]=0;
          }
        }
      }
      $languageDistribution[$language]['files'][$fileShortName]['filename']=$filename;
      // add integer fields.
      $languageDistribution[$language]['nbFiles']++ ;
      foreach($fileSummary as $key => $value) {
        if (is_numeric($value)) {
          $languageDistribution[$language][$key] += $value;
        }
      }
    }
    $summary['languages']=$languageDistribution ;


    // Summarize information about direct subdirectories
    $dirMap = $this->getDirectoryMap() ;

    $summary["nbOfSubDirectories"] = count($dirMap) ;
    $summary["subDirectories"] = array() ;
    foreach($dirMap as $dirShortName => $dir) {
      $summary['subDirectories'][$dirShortName] = array() ;
      $summary['subDirectories'][$dirShortName]['name'] = $dirShortName ;
    }
    return $summary ;
  }


  public function getSummaryAsJson() {
    return json_encode($this->getSummary()) ;
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
  public function __construct($directoryPath) {
    $this->directoryPath = $directoryPath ;
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
  public function __construct($directoryPath,$parentDirectory) {
    parent::__construct($directoryPath) ;
    $this->parentDirectory = $parentDirectory ;
    $this->topDirectory = $this->parentDirectory->getTopDirectory() ;
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
  public function __construct($basedir,$directoryPath,$fileSystemMatcher=null,$defaultOutputBase=null,$traceOnStdout=true) {
    parent::__construct($directoryPath) ;
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













interface FileSystemPatternMatcher {
  /**
   * Match a path.
   * @param 'directory'|'file'! $type
   * @param Pathname! $path
   * @return Map*(String!,String!)? The map of properties for this path
   * or null in case of no match.
   */
  public function matchPath($type,$path) ;
}




/**
 * A FileSystemPatternMatcher that never match anything.
 */
class NeverMatchFileSystemPatternMatcher implements FileSystemPatternMatcher {
  public function matchPath($type,$path) {
    return null ;
  }
}


/**
 * A FileSystemPatternMatcher that based on file extension defines the geshi language code.
 * This implementation is based on the extension definitions that comes with
 * the GeSHi package itself.
 * Only files with know extensions are matched. In this case, "extension" and "geshiLanguage"
 * are returned.
 */
class GeSHiExtensionPatternMatcher implements FileSystemPatternMatcher {
  public function matchPath($type,$path) {
    if ($type==="file") {
      $extension = fileExtension($path) ;
      $geshiLanguage = GeSHiExtended::getLanguageFromExtension(fileExtension($extension)) ;
      if ($geshiLanguage==="") {
        return null ;
      } else {
        return array(
            "extension"=>$extension,
            "geshiLanguage"=>$geshiLanguage) ;
      }
    } else {
      return null ;
    }
  }

}


class FirstFileSystemPatternMatcher implements FileSystemPatternMatcher {
  /**
   * @var List*(FileSystemPatternMatcher)!
   */
  protected $patternMatcherList ;

  public function matchPath($type,$path) {
    foreach ($this->patternMatcherList as $matcher) {
      $r = $matcher->matchPath($type,$path) ;
      if (isset($r)) {
        return $r ;
      }
    }
    return $r ;
  }

  public function __construct($patternMatcherList) {
    $this->patternMatcherList = $patternMatcherList ;
  }
}




/**
 *
 *
 *
 * type RuleList == List*(Rule)!
 *
 * type Rule ==
 *   RuleLHS + Rule
 *
 * type RuleLHS ==
 *   Map {
 *     'patternRestriction' => String!,
 *     'patternType' => PatternType!
 *     'pattern' => Pattern
 *   }
 *
 * type RuleRHS ==
 *   Map*(String!,Template)!
 *
 *
 */

class RuleBasedFileSystemPatternMatcher implements FileSystemPatternMatcher {
  protected $rules ;

  public function getRules() {
    return $this->rules ;
  }

  public function getRulesNumber() {
    return count($this->getRules()) ;
  }

  public function getRulesSummary($groups=array()) {
    $summary['rules']=$this->rules ;
    foreach($groups as $group) {
      $summary[$group] = groupedBy($group,$this->rules) ;
    }
    return $summary ;
  }

  public function getRulesAsJSON($beautify=true) {
    $json = jsonjson_encode($this->getRules()) ;
    if ($beautify) {
      $json = jsonBeautifier($json) ;
    }
    return $json ;
  }

  public function getPredefinedKeys() {
    return array(
        'patternRestriction',
        'patternType',
        'pattern',
        'patternLength',
        'merging') ;
  }

  /**
   * @param unknown_type $key
   * @return boolean
   */
  public function isUserDefinedKey($key) {
    return !in_array($key,$this->getPredefinedKeys()) ;
  }


  /**
   * Match a path against the set of rules and returns the list
   * of matched rules
   *
   * type MatchedRuleList == List*(MatchedRule)
   * type MatchedRule ==
   *   RuleHS + MatchedRuleRHS
   *
   * type MatchedRuleRHS ==
   *   Map {
   *     'patternLength' => Integer>=0!
   *   }
   *   + MatchedUserRuleRHS
   *
   * type MatchedUserRuleRHS ==
   *   Map*(String!,TResult!)!
   *
   * @param 'directory'|'file'! $type
   * @param Pathname! $path
   * @return MatchedRuleList! The list of matching rules
   * where each non pattern keys have been evaluated as a template.
   * The list will be empty if no rules have matched.
   */
  public function matchingRulesForPath($type,$path) {
    $results = array() ;
    // compute the list of
    foreach ($this->rules as $rule) {
      if ($type===$rule['patternRestriction']) {
        if (matchPattern($rule['patternType'].':'.$rule['pattern'],$path,$matches)) {
          $result = array() ;
          foreach($rule as $key=>$value) {
            if (!$this->isUserDefinedKey($key)) {
              $result[$key]=$value ;
            } else {
              $result[$key]=doEvalTemplate($value,$matches) ;
            }
          }
          $result['patternLength']=strlen($rule['pattern']) ;
          $results[] = $result ;
        }
      }
    }
    return $results ;
  }


  /**
   * Merge a list of matched rule according to a mergeMethod.
   * Return only one MergedMatchRule or null if not matched rules
   * are provided.
   *
   * type MergedRule ==
   *     MatchedRule    // in case of a singe match
   *   | MatchedUserRuleRHS
   *     +
   *     Map{
   *       'merging' => Map{
   *         'nbOfMatchingRules' => Integer>=2,
   *         'patternLength' => Map+(String!,Integer>=0)?, // for a key, the maximum length of the matching rules
   *         'conflicts' => Map+(String!,List+(Any!))?,    // for a key, the list of possible values to select from
   *       }
   *     }
   *
   * type MergingMethod == // method to select which rule win for a key having various values
   *     'longestPattern'
   *   | 'lastRule'
   *   | 'firstRule'
   *   | 'listValues'
   *
   * @param MatchedRuleList! $results the list of matching rules
   *
   * @param 'longestPattern'|'lastRule'|'firstRule'|'listValues' m
   *
   * @return MatchedRule? null in case of no match or a mergedRule
   */
  public function mergeMatchResultList($results,$mergeMethod='longestPattern') {
    switch (count($results)) {
      case 0:
        return null ;
        break ;

      case 1:
        // there is not multiple results, so nothing to merge
        return $results[0] ;
        break ;

      default:
        // there at least two results. So we should perform a merge.

        $mergedResult = array() ;
      $mergedResult['merging']['nbOfMatchingRules'] = count($results) ;
      foreach($results as $result) {
        foreach ($result as $resultKey => $resultValue) {
          $resultPatternLength = $result['patternLength'] ;

          // we do not care about patternKeys, i.e. regexpr
          // as they are certainly distincts
          if ($this->isUserDefinedKey($resultKey)) {

            if (!isset($mergedResult[$resultKey])) {
              // the key is not already defined, so no problem
              $mergedResult[$resultKey] = $resultValue ;
              // keep however the size of the match. This merging
              // fields for this key will be used to remember according
              // to whoch patternLengths the key has been selected in
              // the method longestPattern.
              if ($mergeMethod==='longestPattern') {
                $mergedResult['merging']['patternLength'][$resultKey]=$resultPatternLength ;
              }
            } else {
              // there is already a value for that key
              if ($mergedResult[$resultKey]===$resultValue) {
                // this is the the same value, excellent!
                // to nothing except to update the pattern length for that key as it may win
                if ($mergeMethod==='longestPattern'
                    && $mergedResult['merging']['patternLength'][$resultKey] < $resultPatternLength) {
                  $mergedResult['merging']['patternLength'][$resultKey]=$resultPatternLength ;
                }
              } else {
                // we just found a conflict, something already there
                if (is_array($mergedResult[$resultKey])) {
                  // this was a array of values
                  // it might be because we are in the listingValues mergingMode
                  // or because the value was an array. Don't care;
                  if (in_array($resultValue,$mergedResult[$resultKey]) ) {
                    // no problem,the value is already registerd
                    // it was not found in the test above because an
                    // array has been compared with the value
                  } else {
                    // ok, there was already a conflict on that key (or not)
                    // No additional conflict declaration for that key
                    // Just add this new value.
                    $mergedResult[$resultKey][] = $resultValue ;
                  }
                } else {
                  // so we have two scalar values, this is a conflict
                  @ $mergedResult['merging']['conflicts'][$resultKey][]=$resultValue ;

                  switch ($mergeMethod) {
                    case 'longestPattern' :
                      if ($mergedResult['merging']['patternLength'][$resultKey] <= $resultPatternLength) {
                        // the current rule has a longer pattern, so select this value
                        $mergedResult[$resultKey] = $resultValue ;
                        // update also the patternLength
                        $mergedResult['merging']['patternLength'][$resultKey] = $resultPatternLength ;
                      }
                      break ;

                    case 'lastRule' :
                      // forget the old value
                      $mergedResult[$resultKey] = $resultValue ;
                      break ;

                    case 'firstRule' :
                      // do nothing. The new value is thus ignored
                      break ;

                    case 'listValues':
                      // create an array with the old value and the new one.
                      $mergedResult[$resultKey] = array($mergedResult[$resultKey],$resultValue) ;
                      break ;

                    default:
                      die('mergeMatchResultList: mergeMethod "'.$mergeMethod."'") ;
                  }
                }
              }
            }
          }
        } // foreach
      } // foreach
      // unset($mergedResult['merging']['patternLength']) ;
      return $mergedResult ;
    }
  }




  /**
   * Match a path.
   * @param 'directory'|'file'! $type
   * @param Pathname! $path
   * @param Boolean? $cleanMergingInformation
   * @return MergedRule? The map of properties for this path
   * or null in case of no match.
   */
  public function matchPath($type,$path,$cleanMergingInformation=true) {
    $matchedRuleList = $this->matchingRulesForPath($type,$path) ;
    $mergedRule = $this->mergeMatchResultList($matchedRuleList) ;
    if ($cleanMergingInformation) {
      unset($mergedRule['merging']) ;
    }
    return $mergedRule ;
  }


  /**
   * @param Pathname! $rootDirectory an existing directory name to be explored.
   * All files recursively this directory will be matched agains the rules.
   * The same for subdirectories.
   */

  public function matchFileSystem($rootDirectory,$groupSpecifications=array()) {
    $artefactType = "file" ;
    $files = listAllFileNames($rootDirectory,$artefactType) ;

    $filesNotMatched=array() ;
    $basenamesOfFilesNotMatched=array() ;
    $extensionsOfFilesNotMatched=array() ;
    $nbOfMultipleMatches=0 ;

    foreach($files as $filename) {
      $shortfilename=basename($filename) ;
      $relativefilename=substr($filename,strlen($rootDirectory)) ;
      $matchResults = $this->matchingRulesForPath($artefactType,$shortfilename) ;
      if (count($matchResults)===0) {

        $filesNotMatched[]=$relativefilename ;
        @ $basenamesOfFilesNotMatched[$shortfilename]['nb'] += 1 ;
        @ $basenamesOfFilesNotMatched[$shortfilename]['occurrences'] .= "<li>".$relativefilename."</li>" ;

        $extension=fileExtension($shortfilename) ;
        @ $extensionsOfFilesNotMatched[$extension]['nb'] += 1 ;
        @ $extensionsOfFilesNotMatched[$extension]['occurrences'] .= "<li>".$relativefilename."</li>" ;

      } else {
        $filesMatched[$relativefilename] = $this->mergeMatchResultList($matchResults) ;
        if (count($matchResults)>=2) {
          $filesMatched[$relativefilename]['rulesMatched'] = $matchResults ;
          $nbOfMultipleMatches++ ;
        }
      }
    }
    $nbOfFiles = count($files) ;
    $nbOfFilesNotMatched = count($filesNotMatched) ;
    $nbOfFilesMatched = $nbOfFiles-count($filesNotMatched) ;
    $ratio = (($nbOfFilesMatched/$nbOfFiles)*100) ;
    $output =
    array(
        'rootDirectory' => $rootDirectory,
        'nbOfFiles' => $nbOfFiles,
        'nbOfFilesMatched' => $nbOfFilesMatched,
        'nbOfFilesNotMatched' => $nbOfFilesNotMatched,
        'matchRatio' => $ratio,
        'nbOfMultipleMatches'=> $nbOfMultipleMatches,
        'filesMatched' => $filesMatched,
        'filesNotMatched'=> $filesNotMatched,
        'extensionsOfFilesNotMatched' => $extensionsOfFilesNotMatched,
        'basenamesOfFilesNotMatched' => $basenamesOfFilesNotMatched
    ) ;
    if (isset($groupSpecifications)
        && is_array($groupSpecifications) && count($groupSpecifications)>=1) {
      $output = array_merge($output,groupAndProject($groupSpecifications,$filesMatched)) ;
    }
    return $output ;
  }

  public function generate(
      $rootDirectory,
      $outputbase=null,
      $matchedFilesGroupSpecifications=array(),
      $rulesGroups
  ) {
    $html =  "<h2>Rules</h2>" ;
    $html .= '<b>'.count($this->rules).'</b> rules defined<br/>' ;
    $html .= mapOfMapToHTMLTable($this->rules,'',true,true,null,2) ;
    $output['rules.html'] = $html ;

    $rulesSummary = $this->getRulesSummary($rulesGroups) ;
    $output['rulesSummary.json'] = jsonBeautifier(json_encode($rulesSummary)) ;


    $r = $this->matchFileSystem($rootDirectory,$matchedFilesGroupSpecifications) ;
    $html = '' ;
    foreach ($r['filesMatched'] as $fileMatched => $matchingDescription) {
      $html .= "<hr/><b>".$fileMatched."</b><br/>" ;

      $mergedResult = $matchingDescription ;
      if (isset($mergedResult['conflictingKeys'])) {
        $keys = $mergedResult['conflictingKeys'] ;
        foreach ($keys as $key) {
          $html .= "<li><span style='background:red;'>conflict on key $key </span></li>" ;
          $mergedResult[$key] = "<li>".implode("<li>",$mergedResult[$key]) ;
        }
      }
      $html .= "Merged result" ;
      $html .= mapOfMapToHTMLTable(array($mergedResult),'',true,true,null,2) ;

      if (isset($matchingDescription['rulesMatched'])) {
        $html .= mapOfMapToHTMLTable($matchingDescription['rulesMatched']) ;
      }
    }
    $output['filesMatches.html'] = $html ;


    $html =  "<h3>Basenames of files not matched</h3>" ;
    $html .=  mapOfMapToHTMLTable($r['basenamesOfFilesNotMatched'],'',true,true,null,2) ;
    $html .= "<h3>Extensions of files not matched</h3>" ;
    $html .=  mapOfMapToHTMLTable($r['extensionsOfFilesNotMatched'],'',true,true,null,2) ;
    $output['filesNotMatched.html'] = $html ;

    $output['matchSummary.json'] = json_encode($r) ;

    if (is_string($outputbase)) {
      $index = "" ;
      $index .= '<b>'.count($this->rules).'</b> rules defined</br>' ;
      $index .= $r['nbOfFilesMatched']." files matched over ".$r['nbOfFiles']." files : ".$r["matchRatio"]."%<br/>" ;
      foreach($output as $file => $content) {
        saveFile(addToPath($outputbase,$file),$content) ;
        $index .= '<li><a href="'.$file.'">'.$file.'</a></li>' ;
      }
      $output['index.html']=$index ;
      saveFile(addToPath($outputbase,'index.html'),$index) ;
    }
    return $r ;
  }

  /**
   * Create a rule based file system pattern matcher
   * @param RuleList|"*.csv"|"*.rules.json" $rulesOrFile either the list
   * of rules or a csv file or a .rules.json file.
   */
  public function __construct($rulesOrFile) {
    if (is_string($rulesOrFile) && endsWith($rulesOrFile,'.csv')) {
      // this is a csv file. Load it and convert it to rules
      $csv = new CSVFile() ;
      if (! $csv->load($rulesOrFile)) {
        die('Cannot read '.$rulesOrFile);
      }
      $this->rules = $csv->getListOfMaps() ;
    } elseif (is_string($rulesOrFile) && endsWith($rulesOrFile,'.rules.json')) {
      $json = file_get_contents($rulesOrFile) ;
      if ($json === false) {
        die('Cannot read '.$rulesOrFile);
      }
      $this->rules = jsonDecodeAsMap($json) ;
    } else {
      $this->rules = $rulesOrFile ;
    }
    if (!is_array($this->rules)) {
      die('incorrect set of rules') ;
    }
  }
}









