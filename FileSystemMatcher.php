<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'configs/SourceCode.config.php';
require_once 'SourceCode.php' ;
require_once 'CSV.php' ;

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
      $geshiLanguage = GeSHiExtended::getLanguageFromExtension($extension) ;
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
 *     'ruleId' => String!,    // not used so far TODO
 *     'ruleRestriction' => String!,
 *     'ruleCondition' => Pattern!,
 *     'ruleWeight' => String!,  // not used so far TODO
 *     'ruleOrigin' => String?,  // not used so far TODO
 *     'ruleKind' => String?     // not used so far TODO
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
        'ruleId',
        'ruleRestriction',
        'ruleCondition',
        'ruleWeight',
        'ruleOrigin',
        'ruleKind',
        'rulePatternLength',
        'ruleMerging') ;
  }
  
  public function clearPredefined($map) {
    foreach ($this->getPredefinedKeys() as $key) {
      unset($map[$key]) ;
    } 
    return $map ;
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
      if ($type===$rule['ruleRestriction']) {
        if (matchPattern($rule['ruleCondition'],$path,$matches)) {
          $result = array() ;
          foreach($rule as $key=>$value) {
            if (!$this->isUserDefinedKey($key)) {
              $result[$key]=$value ;
            } else {
              $result[$key]=doEvalTemplate($value,$matches) ;
            }
          }
          $result['rulePatternLength']=strlen($rule['ruleCondition']) ;
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
   *       'ruleMerging' => Map{
   *         'nbOfMatchingRules' => Integer>=2,
   *         'rulePatternLength' => Map+(String!,Integer>=0)?, // for a key, the maximum length of the matching rules
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
      $mergedResult['ruleMerging']['nbOfMatchingRules'] = count($results) ;
      foreach($results as $result) {
        foreach ($result as $resultKey => $resultValue) {
          $resultPatternLength = $result['rulePatternLength'] ;

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
                $mergedResult['ruleMerging']['rulePatternLength'][$resultKey]=$resultPatternLength ;
              }
            } else {
              // there is already a value for that key
              if ($mergedResult[$resultKey]===$resultValue) {
                // this is the the same value, excellent!
                // to nothing except to update the pattern length for that key as it may win
                if ($mergeMethod==='longestPattern'
                    && $mergedResult['ruleMerging']['rulePatternLength'][$resultKey] < $resultPatternLength) {
                  $mergedResult['ruleMerging']['rulePatternLength'][$resultKey]=$resultPatternLength ;
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
                  @ $mergedResult['ruleMerging']['conflicts'][$resultKey][]=$resultValue ;

                  switch ($mergeMethod) {
                    case 'longestPattern' :
                      if ($mergedResult['ruleMerging']['rulePatternLength'][$resultKey] <= $resultPatternLength) {
                        // the current rule has a longer pattern, so select this value
                        $mergedResult[$resultKey] = $resultValue ;
                        // update also the patternLength
                        $mergedResult['ruleMerging']['rulePatternLength'][$resultKey] = $resultPatternLength ;
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
      // unset($mergedResult['ruleMerging']['patternLength']) ;
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
      $mergedRule = $this->clearPredefined($mergedRule) ;
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
