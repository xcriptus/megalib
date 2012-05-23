<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'HTML.php' ;
require_once 'TExpr.php' ;
require_once 'Structures.php' ;
require_once 'FileSystemMatcher.php' ;


/**
 * A set of tagged fragments. An abstract data structure gathering all information
 * on file fragments.
 *
 * // Abstract syntax
 *
 * type Tag == String
 *
 * type FragmentSpecification ==
 *   Map+(String!,String!)!
 *
 * type FragmentLocation ==
 *   Map+(String!,String!)!
 *
 * type FragmentLocator ==
 *   FilePath!
 *
 * type FragmentId ==
 *   String!
 *
 * type TaggedFragmentInfo == Map{
 *     'tags' => Set*(Tag!)?,
 *          // corresponds to the 'tag' attribute in the definition
 *          // sysetmatically converted to a set.
 *          // empty set if no tags are provided.
 *     'specification' => FragmentSpecification?,
 *         // corresponds to the the 'fragment' attribute in the definition
 *         // either the location or the specification should be defined
 *     'locator' => FragmentLocator?,
 *         // this property can be defined in an original fragment definition
 *         // or otherwise it is computed from the filename
 *     'location' => FragmentLocation?
 *         // result of the application of the locator to the specification
 *   }
 *   
 * type TagToFragmentsMap == 
 *   Map+(Tag!,Set*(FragmentId!))! ;
 */

class TaggedFragmentSet {


  /**
   * type FragmentedFileInfo =
   *   Map*(FragmentId!,TaggedFragmentInfo!) ;
   *
   * type FileToTaggedFragmentsMapping =
   *   Map*(FilePath!, Map{
   *     'fragments' => FragmentedFileInfo!,
   *     'tags' => Map(Tag=>Set*(FragmentId))!
   *
   * For each path of a file, the list of TagFragment
   * @var FileToTagFragmentsMapping
   */
  protected $fileToTaggedFragmentsMapping ;
  

  /*------------------------------------------------------------------------
   *   Getters
  *------------------------------------------------------------------------
  */
  public function isFragmentedFile($file) {
    return isset($this->fileToTaggedFragmentsMapping[$file]) ;
  }

  public function getFragmentedFiles() {
    return array_keys($this->fileToTaggedFragmentsMapping) ;
  }

  public function getFragmentedFileIds($file) {
    return array_keys($this->fileToTaggedFragmentsMapping[$file]['fragments']) ;
  }

  //  Not necessary to expose this structure
  //  public function getTaggedFragmentInfo($file,$fragmentId) {
  //    return $this->fileToTaggedFragmentsMapping[$file][$fragmentId] ;
  //  }

    public function issetProperty($file,$fragmentId,$property) {
      return isset($this->fileToTaggedFragmentsMapping[$file]['fragments'][$fragmentId][$property]) ;
    }
    public function getProperty($file,$fragmentId,$property) {
      return $this->fileToTaggedFragmentsMapping[$file]['fragments'][$fragmentId][$property] ;
    }


    /*------------------------------------------------------------------------
     *   Setters
    *------------------------------------------------------------------------
    */


    public function clear() {
      $this->fileToTaggedFragmentsMapping = array() ;
    }

    /**
     * Create an new fragment for the given file and with optional id.
     * If no id is provided then a new one is created.
     * @param unknown_type $fragmentedFilePath
     * @param FragmentId? $id an optional id for this fragment. If not
     * provided then a new id will be created.
     * @return FragmentId! the id of the created fragment.
     */
    public function createFragment($fragmentedFilePath,$id=null) {
      // if no id is provided otherwise create a new one
      if (!isset($id)) {
        // check if the file has been already registered
        if ($this->isFragmentedFile($fragmentedFilePath)) {
          $n = count($this->getFragmentedFileIds($fragmentedFilePath))+1 ;
        } else {
          $n = 1 ;
        }
        $id = "_".((string)$n) ;
      }
      $this->fileToTaggedFragmentsMapping[$fragmentedFilePath]['fragments'][$id]=array() ;
      return $id ;
    }

    /**
     * Set one property of TaggedFragmentInfo
     * @param FilePath! $file
     * @param FragmentId! $fragmentId
     * @param String! $property a property name from the TaggedFragmentInfo
     * @param Any $value
     * @return Any the value is returned
     */
    public function setProperty($file,$fragmentId,$property,$value) {
      $this->fileToTaggedFragmentsMapping[$file]['fragments'][$fragmentId][$property] = $value ;
      return $value ;
    }
    
    
    /**
     * Add to the structure derived information. Should be call after the structure
     * is filled. 
     * Currently add the TagToFragmentsMap into the 'tags' property of each file.
     */
    public function computeDerivedInformation() {
      // create the TagToFragmentsMap
      foreach($this->getFragmentedFiles() as $file) {
        $this->fileToTaggedFragmentsMapping[$file]['tags']=array() ;
        foreach($this->getFragmentedFileIds($file) as $id) {
          foreach($this->getProperty($file,$id,'tags') as $tag) {
            @ $this->fileToTaggedFragmentsMapping[$file]['tags'][$tag][] = $id ;
          }
        }
      }      
    }

    /*------------------------------------------------------------------------
     *   JSON
    *------------------------------------------------------------------------
    */

    public function asJson($beautify=false) {
      return jsonEncode($this->fileToTaggedFragmentsMapping,$beautify) ;
    }

    //
    public function saveInJsonSummaryFiles($oldBase,$newBase,$beautify=false) {
      
      foreach($this->getFragmentedFiles() as $file) {
        $targetFile = rebasePath($file,$newBase,$oldBase).'.fragments.json' ;        
        // FIXME currently save in a fragments files instead of summary files as there is a bug in array_merge
        // FIXME the file before as it is 
        @ unlink($targetFile) ;
        saveOrMergeJsonFile($targetFile, $this->fileToTaggedFragmentsMapping[$file],'array_merge_recursive',$results,$beautify) ;
      }
      var_dump($results) ;
    }


    public function __construct() {
      $this->errors = array() ;
      $this->fileToTaggedFragmentsMapping = null ;
    }
}

/**
 * Read fragment definiton files and create a taggedFragmentSet
 * with 'locator' property computed from file system matching or
 * if not indicated. This class deals with the concrete syntax
 * and representation details of the fragment definition files.
 *
 * // Concrete syntax
 *
 * // corresponds to a fragment definition file
 * @see http://101companies.org/index.php/Language:FraTaLa
 *
 *
 * type TaggedFragmentDefinitionSet ==
 *   List*(TaggedFragmentDefinition)
 *
 * type TaggedFragmentDefinition == Map {
 *     'tag'        // the tag to apply to a given selection.
 *     'file'       // the file in which to select the fragment.
 *     'fragment'   // the language-specific fragment description.
 *     'id'         // ADDED. An optional id of the fragment.
 *     'locator'    // ADDED. An optional locator.
 *
 */



class TaggedFragmentSetReader {
  /**
   * List of errors produced.
   * @var List*(String!,Any!)!
   */
  protected $errors ;


  /**
   * @var TaggedFragmentSet the set to be produced by the reader
   */
  protected $taggedFragmentSet ;


  /**
   * The fileSystemPatternMatcher used to find fragment locator
   * associated with file extension for instance.
   * @var FileSystemPatternMatcher!
   */
  protected $fileSystemPatternMatcher ;



  public function getErrors() {
    return $this->errors;
  }

  public function getErrorsAsJson($beautify=false) {
    return jsonEncode($this->getErrors(),$beautify) ;
  }

  /*---------------------------------------------------------------------------------------
   * Parser of the Fragment DSL
  *---------------------------------------------------------------------------------------
  */

  /**
   * Compute the full file path. (see comments)
   * @param TaggedFragmentDefinition! $definition
   * @param Path! $definitionSetPath the path of the fragment file definition
   * without its extension. It ends with / if this is a directory based file.
   * @return FilePaht? the path of the file to be fragmented.
   */
  protected function _getFragmentedFilePath($definitionSetPath,&$definition) {
    //---- compute the fullFilePath to which the fragment definition is associated
    if (endsWith($definitionSetPath,'/')) {
      // => this is a directory-based definition set
      // the file attribute must be defined
      if (!isset($definition['file'])) {
        $this->error[$definitionSetPath] = "no file specified. Tagged fragment definition ignored." ;
        $fullFilePath = null ;
      } else {
        $fullFilePath = addToPath($definitionSetPath,$definition['file']) ;
        unset($definition['file']) ;
      }
    } else {
      // => this is a file-based definition set
      // the name of the file is just the path
      $fullFilePath = $definitionSetPath ;
    }
    return $fullFilePath ;
  }


  /**
   * Set the 'id' property of a fragment. If no id is provided in the
   * definition then a new id local to the file will be assigned.
   *
   * This is method will create the element in the mapping for this id.
   *
   * @param FullFilePath $fragmentedFilePath
   * @param inOut>TaggedFragmentDefinition! $definition
   * @return FragmentId! the id from the definition or a generated id
   */
  protected function _setIdProperty($fragmentedFilePath,&$definition) {
    // check if an explict id is provided in the definition
    if (isset($definition['id'])) {
      $id = $definition['id'] ;
      unset($definition['id']) ;
    } else {
      $id = null ;
    }
    $id = $this->taggedFragmentSet->createFragment($fragmentedFilePath,$id) ;
    return $id ;
  }


  /**
   * Set the 'locator' property. To get its value search
   * - first in the fragment definition with the field 'locator'
   * - then via the file extensions. In this case search for the field 'fragmentLocator'
   * in the RHS of rules.
   * @param FullFilePath! $fragmentedFilePath
   * @param FragmentId! $fragmentId the id of the fragment within the file
   * @param inOut>TaggedFragmentDefinition! $definition
   * @return Filename? path of the fragment locator or null if not found
   */
  protected function _setLocatorProperty($fragmentedFilePath,$fragmentId,&$definition) {
    // search for the locator property first in the definition
    if (isset($definition['locator'])) {
      $locator = $definition['locator'] ;
      unset($definition['locator']) ;
    } else {
      // then search according to the FileSystemPatternMatcher
      $properties = $this->fileSystemPatternMatcher->matchPath('file',$fragmentedFilePath) ;
      if (isset($properties['fragmentLocator'])) {
        $locator = $properties['fragmentLocator'] ;
      } else {
        // not found
        $this->errors[$fragmentedFilePath] = "no locator found" ;
        $locator = null ;
      }
    }
    if (isset($locator)) {
      $this->taggedFragmentSet->setProperty($fragmentedFilePath,$fragmentId,'locator',$locator) ;
    }
    return $locator ;
  }

  /**
   * Set the 'tags' property as an array (that could be empty)
   * @param FullFilePath $fragmentedFilePath
   * @param FragmentId! $fragmentId the id of the fragment within the file
   * @param inOut>TaggedFragmentDefinition! $definition
   * @return Set*(String!)!
   */
  protected function _setTagsProperty($fragmentedFilePath,$fragmentId,&$definition) {
    if (isset($definition['tag'])) {
      if (is_string($definition['tag'])) {
        $tags = array($definition['tag']) ;
      } else {
        $tags = $definition['tag'] ;
      }
      unset($definition['tag']) ;
    } else {
      $tags = array() ;
    }
    $this->taggedFragmentSet->setProperty($fragmentedFilePath,$fragmentId,'tags',$tags) ;
    return $tags ;
  }

  /**
   * Set the 'specification' property if it exists.
   * @param FullFilePath $fragmentedFilePath
   * @param FragmentId! $fragmentId the id of the fragment within the file
   * @param inOut>TaggedFragmentDefinition! $definition
   * @return FragmentSpecification?
   */
  protected function _setSpecificationProperty($fragmentedFilePath,$fragmentId,&$definition) {
    if (isset($definition['fragment'])) {
      $fragment = $definition['fragment'] ;
      $this->taggedFragmentSet->setProperty($fragmentedFilePath,$fragmentId,'specification',$fragment) ;
      unset($definition['fragment']) ;
      return $fragment ;
    } else {
      return null ;
    }
  }


  public function read($baseDirectory) {
    $this->taggedFragmentSet->clear() ;

    // read all json files and convert them in a list of definitionSet
    // only the path of the file or directory is kept. The extension is deleted.
    $taggedFragmentDefinitionSets =
    mapFromJsonDirectory(
        $baseDirectory,
        array(
            'excludeDotFiles'=>false,
            'pattern'=>'#(.*)\.fratala#'),
        '${1}') ;

    // for each definitionSet
    foreach($taggedFragmentDefinitionSets as $definitionSetPath => $taggedFragmentDefinitionSet) {
      foreach ($taggedFragmentDefinitionSet as $definition) {
        // check if this is an array
        if (is_string_map($definition)) {

          $fragmentedFilePath = $this->_getFragmentedFilePath($definitionSetPath,$definition) ;
          if (isset($fragmentedFilePath)) {
            // we know which file this definition set is about. Hourra.
            // set the entity of the fragment and create the structure for it
            $fragmentId = $this->_setIdProperty($fragmentedFilePath,$definition) ;
            // set all other properties and remove them
            $tags = $this->_setTagsProperty($fragmentedFilePath,$fragmentId, $definition) ;
            $specification = $this->_setSpecificationProperty($fragmentedFilePath,$fragmentId,$definition) ;
            $locator = $this->_setLocatorProperty($fragmentedFilePath,$fragmentId,$definition) ;
            if (count($definition)>=1) {
              // some properties remain but as they were not used issue an error ;
              $this->errors[$definitionSetPath][] = "properties not recognized: ".jsonEncode($definition) ;
            }
          } else {
            $this->errors[$definitionSetPath][] = "cannot compute the full file path. Ignored." ;
          }
        } else {
          // the definition is not a string map. This is incorrect
          $this->errors[$definitionSetPath][] = "not a map: ".jsonEncode($definition) ;
        }
      } //foreach
    } //foreach
    return $this->taggedFragmentSet ;
  }

  public function __construct($fileSystemMatcherRules) {
    $this->taggedFragmentSet = new TaggedFragmentSet() ;
    $this->fileSystemPatternMatcher =
    new RuleBasedFileSystemPatternMatcher($fileSystemMatcherRules) ;
  }
}








/**
 * FragmentLocatorIterator iterates over all fragments and locate them using the
 * 'locator' and 'specification' properties.
 */
class FragmentLocatorIterator {
  protected $commandsBaseDirectory ;
  protected $tmpDir ;
  protected $tmpSpecificationFile ;
  protected $tmpLocationFile ;
  protected function getTmpFile($file) {
    return addToPath($this->tmpDir,$file) ;
  }

  /**
   * Compute the location of the fragment
   * @param FilePath! $filePath the file to be fragmented
   * @param FragmentLocator! $locator
   * @param FragmentSpecification! $fragmentSpecification the specification
   * @return FragmentLocation? the location of the fragment
   */
  protected function _getLocateFragmentCommand($filePath,$locator,$fragmentSpecification) {
    $command =
    escapeshellcmd(addToPath($this->commandsBaseDirectory,$locator))
    .' '.escapeshellarg($filePath)
    .' '.$this->tmpSpecificationFile
    .' '.$this->tmpLocationFile ;
    return $command ;
  }

  protected function _computeLocation($filePath,$locator,$fragmentSpecification,$dummyExecution) {
    // save the input file and remove the output one if any
    saveAsJsonFile($this->tmpSpecificationFile,$fragmentSpecification) ;
    @ unlink($this->tmpLocationFile) ;
    $command = $this->_getLocateFragmentCommand($filePath,$locator,$fragmentSpecification) ;
    if ($dummyExecution) {
      echo "# executing ".$command."\n<br/>" ;
      $location = array('from'=>1,'to'=>1) ;
    } else {
      // execute the command and try to read the output file. If no such file then there was an error
      echo "executing ".$command."\n<br/>" ;
      system($command) ;
      if (file_exists($this->tmpLocationFile)) {
        $location = jsonLoadFileAsMap($this->tmpLocationFile,false) ;
      } else {
        $location = null ;
      }
    }
    return $location ;
  }

  public function addLocationToAllFragments(TaggedFragmentSet $set, $dummyExecution=false) {
    // for each fragmentedFile
    foreach($set->getFragmentedFiles() as $file) {
      // for each fragment in this file
      foreach($set->getFragmentedFileIds($file) as $id) {
        // if there 'locator' and 'specification' are set execute apply the locatorit
        if ( $set->issetProperty($file,$id,'locator')
            && $set->issetProperty($file,$id,'specification')) {
          $locator = $set->getProperty($file,$id,'locator') ;
          $specification = $set->getProperty($file,$id,'specification') ;
          $location = $this->_computeLocation($file,$locator,$specification,$dummyExecution) ;
          if ($location!==null) {
            $set->setProperty($file,$id,'location',$location) ;
          }
        }
      }
    }
  }

  public function __construct($tmpDir,$commandsBaseDirectory='.') {
    $this->tmpDir = $tmpDir ;
    $this->tmpSpecificationFile = addToPath($tmpDir,'specification.json') ;
    $this->tmpLocationFile = addToPath($tmpDir,'location.json') ;
    $this->commandsBaseDirectory = $commandsBaseDirectory ;
  }
}  