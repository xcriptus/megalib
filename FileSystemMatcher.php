<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'configs/SourceCode.config.php';
require_once 'SourceCode.php' ;
require_once 'CSV.php' ;
require_once 'PExpressionConcrete.php' ;
require_once 'TExpression.php' ;
require_once 'Rules.php' ;

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
 * Only files with know extensions are matched. In this case, "extension" and "GeSHi.Language"
 * are returned.
 */
class GeSHiExtensionPatternMatcher implements FileSystemPatternMatcher {
  public function matchPath($type,$path) {
    if ($type==="file") {
      $extension = fileExtension($path) ;
      $geshiLanguage = GeSHiExtended::getLanguageFromExtension($extension) ;
      if ($geshiLanguage==="") {
        return null ;
      } else {
        return array(
            "extension"=>$extension,
            "GeSHi.language"=>$geshiLanguage) ;
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

function fileConstant($x) {
  return "file" ;
}

class RuleBasedFileSystemPatternMatcher extends RuleListIncrementalMatcher implements FileSystemPatternMatcher {
  
  public function matchPath($type,$path) {
    return $this->doEvalRules($type,$path) ;
  }
  
  /**
   * @param Pathname! $rootDirectory an existing directory name to be explored.
   * All files recursively this directory will be matched agains the rules.
   * The same for subdirectories.
   */
  
 
  public function matchFileSystem($rootDirectory) {
    $files = listAllFileNames($rootDirectory,'file') ;
    $this->setCurrentItemSet($files) ;
    $result = $this->matchCurrentItemSet() ;
    return $result ;
  }
  
  
  /**
   * Create a rule based file system pattern matcher
   * @param RuleList|"*.csv"|"*.rules.json" $rulesOrFile either the list
   * of rules or a csv file or a .rules.json file.
   */
  public function __construct($rulesOrFile) {
    parent::__construct($rulesOrFile,'fileConstant',null,'basename') ;
  }
  
}




// class RuleBasedFileSystemPatternMatcher extends RuleList implements FileSystemPatternMatcher {

//   public function matchPath($type,$path) {
//     return $this->doEvalRules($type,$path) ;
//   }

//   /**
//    * @param Pathname! $rootDirectory an existing directory name to be explored.
//    * All files recursively this directory will be matched agains the rules.
//    * The same for subdirectories.
//    */

//   // TODO generate this by externalizing the list of files names and the infor the gather

//   public function matchFileSystem($rootDirectory,$groupSpecifications=array()) {
//     $artefactType = "file" ;

//     // GET THE LIST OF ITEM TO MATCH
//     $files = listAllFileNames($rootDirectory,$artefactType) ;

//     $filesNotMatched=array() ;
//     $filesMatched=array() ;
//     $basenamesOfFilesNotMatched=array() ;
//     $extensionsOfFilesNotMatched=array() ;
//     $nbOfMultipleMatches=0 ;

//     foreach($files as $filename) {
//       $shortfilename=basename($filename) ;
//       $relativefilename=substr($filename,strlen($rootDirectory)) ;

//       // perform the match
//       $matchResults = $this->matchingRules($artefactType,$shortfilename) ;

//       if (count($matchResults)===0) {

//         // NO MATCH
//         // gather information
//         $filesNotMatched[]=$relativefilename ;
//         @ $basenamesOfFilesNotMatched[$shortfilename]['nb'] += 1 ;
//         @ $basenamesOfFilesNotMatched[$shortfilename]['occurrences'] .= "<li>".$relativefilename."</li>" ;

//         $extension=fileExtension($shortfilename) ;
//         @ $extensionsOfFilesNotMatched[$extension]['nb'] += 1 ;
//         @ $extensionsOfFilesNotMatched[$extension]['occurrences'] .= "<li>".$relativefilename."</li>" ;

//       } else {

//         // MATCH
//         $filesMatched[$relativefilename] = $this->mergeMatchResultList($matchResults) ;
//         if (count($matchResults)>=2) {
//           $filesMatched[$relativefilename]['rulesMatched'] = $matchResults ;
//           $nbOfMultipleMatches++ ;
//         }
//       }
//     }

//     // SUMMARY
//     $nbOfFiles = count($files) ;
//     $nbOfFilesNotMatched = count($filesNotMatched) ;
//     $nbOfFilesMatched = $nbOfFiles-count($filesNotMatched) ;
//     $ratio = (($nbOfFilesMatched/$nbOfFiles)*100) ;
//     $output =
//     array(
//         'rootDirectory' => $rootDirectory,
//         'nbOfFiles' => $nbOfFiles,
//         'nbOfFilesMatched' => $nbOfFilesMatched,
//         'nbOfFilesNotMatched' => $nbOfFilesNotMatched,
//         'matchRatio' => $ratio,
//         'nbOfMultipleMatches'=> $nbOfMultipleMatches,
//         'filesMatched' => $filesMatched,
//         'filesNotMatched'=> $filesNotMatched,
//         'extensionsOfFilesNotMatched' => $extensionsOfFilesNotMatched,
//         'basenamesOfFilesNotMatched' => $basenamesOfFilesNotMatched
//     ) ;
//     if (isset($groupSpecifications)
//         && is_array($groupSpecifications) && count($groupSpecifications)>=1) {
//       $output = array_merge($output,groupAndProject($groupSpecifications,$filesMatched)) ;
//     }
//     return $output ;
//   }

//   public function generate(
//       $rootDirectory,
//       $outputbase=null,
//       $matchedFilesGroupSpecifications=array(),
//       $rulesGroups
//   ) {
//     $html =  "<h2>Rules</h2>" ;
//     $html .= '<b>'.count($this->rules).'</b> rules defined<br/>' ;
//     $html .= mapOfMapToHTMLTable($this->rules,'',true,true,null,2) ;
//     $output['rules.html'] = $html ;

//     $rulesSummary = $this->getRulesSummary($rulesGroups) ;
//     $output['rulesSummary.json'] = jsonBeautifier(json_encode($rulesSummary)) ;


//     $r = $this->matchFileSystem($rootDirectory,$matchedFilesGroupSpecifications) ;
//     $html = '' ;
//     foreach ($r['filesMatched'] as $fileMatched => $matchingDescription) {
//       $html .= "<hr/><b>".$fileMatched."</b><br/>" ;

//       $mergedResult = $matchingDescription ;
//       if (isset($mergedResult['conflictingKeys'])) {
//         $keys = $mergedResult['conflictingKeys'] ;
//         foreach ($keys as $key) {
//           $html .= "<li><span style='background:red;'>conflict on key $key </span></li>" ;
//           $mergedResult[$key] = "<li>".implode("<li>",$mergedResult[$key]) ;
//         }
//       }
//       $html .= "Merged result" ;
//       $html .= mapOfMapToHTMLTable(array($mergedResult),'',true,true,null,2) ;

//       if (isset($matchingDescription['rulesMatched'])) {
//         $html .= mapOfMapToHTMLTable($matchingDescription['rulesMatched']) ;
//       }
//     }
//     $output['filesMatches.html'] = $html ;


//     $html =  "<h3>Basenames of files not matched</h3>" ;
//     $html .=  mapOfMapToHTMLTable($r['basenamesOfFilesNotMatched'],'',true,true,null,2) ;
//     $html .= "<h3>Extensions of files not matched</h3>" ;
//     $html .=  mapOfMapToHTMLTable($r['extensionsOfFilesNotMatched'],'',true,true,null,2) ;
//     $output['filesNotMatched.html'] = $html ;

//     $output['matchSummary.json'] = json_encode($r) ;

//     if (is_string($outputbase)) {
//       $index = "" ;
//       $index .= '<b>'.count($this->rules).'</b> rules defined</br>' ;
//       $index .= $r['nbOfFilesMatched']." files matched over ".$r['nbOfFiles']." files : ".$r["matchRatio"]."%<br/>" ;
//       foreach($output as $file => $content) {
//         saveFile(addToPath($outputbase,$file),$content) ;
//         $index .= '<li><a href="'.$file.'">'.$file.'</a></li>' ;
//       }
//       $output['index.html']=$index ;
//       saveFile(addToPath($outputbase,'index.html'),$index) ;
//     }
//     return $r ;
//   }

//   /**
//    * Create a rule based file system pattern matcher
//    * @param RuleList|"*.csv"|"*.rules.json" $rulesOrFile either the list
//    * of rules or a csv file or a .rules.json file.
//    */
//   public function __construct($rulesOrFile) {
//     parent::__construct($rulesOrFile) ;
//   }

// }
