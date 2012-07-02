<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'CSV.php' ;
require_once 'Json.php' ;
require_once 'PExpressionConcrete.php' ;
require_once 'TExpression.php' ;



/**
 * A rule with a condition on the Left Hand Side (LHS), 
 * a template on the Right Hand Side (RHS) and some metainformation
 * about the rule.
 * type RuleMeta == Map*(String!,String!)!
 * type RuleRHS == Map*(String!,TExpression!)!
 *
 */
class Rule {
   /**
    * @var String! the id of the rule
    */
   protected $id ; 
   /**
    * Restriction in which the rule apply. Currently the restriction is just a 
    * string, but the idea is that it should be kept simple because it allows,
    * when there are many rules, to prefilter which rules are to be applied for
    * a given object. 
    * @var String! 
    */
   protected $restriction ;
   /**
    * @var ConcretePExpression
    */
   protected $condition ;
   /**
    * @var RuleMeta
    */
   protected $lhsMetaAttributes ;   
   /**
    * @var RuleRHS
    */
   protected $rhs ;
   /**
    * @var Integer>=0
    */
   protected $conditionLength ;
   
   public function getId() {
     return $this->id ;
   }
   
   public function toString() {
     $rhsjson = jsonEncode($this->getRules(),true) ;
     return
         'rule '.$this->id.' on '.$this->restriction.' : '
       . $this->condition->getStringExpression()
       . ' => '. rhsjson ;     
   }
   
   public function toMap() {
     $map = $this->lhsMetaAttributes ;
     $map['rule.id'] = $this->id ;
     $map['rule.restriction'] = $this->restriction ;
     $map['rule.condition'] = $this->condition->getStringExpression() ;
     $map = array_merge($map,$this->rhs) ;
     return $map ;
   }
   
   public function toJson($beautify=true) {
     return jsonEncode($this->toMap(),$beautify) ;
   }
   
   public function fromMap($map) {
     $this->id = $map['rule.id'] ; unset($map['rule.id']) ;
     $this->restriction = $map['rule.restriction'] ; unset($map['rule.restriction']) ;
     $conditionText = $map['rule.condition'] ; unset($map['rule.condition']) ;
     $this->conditionLength = strlen($conditionText) ;
     $this->condition = new ConcretePExpression($conditionText) ;
     foreach ($map as $key=>$value) {
       if(startsWith($key,'rule.')) {
         $this->lhsMetaAttributes[$key]=$value ;
       } else {
         $this->rhs[$key]=$value ;
       }
     }
   }
      
   public function matchesRestriction($itemType) {
     return $itemType === $this->restriction ;
   }
   
   public function evalCondition($item,&$environment,&$errors=array()) {
     $result = $this->condition->doEval($item,$environment) ;
     return $result ;
   }
   
   public function evalRHS(&$environment) {
     if (is_string($this->rhs)) {
       $result = evalTExpression($this->rhs,$environment) ;
     } else {
       die('NOT IMPLEMENTED YET') ;
     }
     $result = array() ;
     foreach ($this->rhs as $key=>$texpr) {
       $result[$key] = evalTExpression($texpr,$environment) ;
     }
     return $result ;
   }
   
   public function getConditionLength() {
     return $this->conditionLength ;
   }
   
   public function __construct($map) {
     $this->fromMap($map) ;
   }   
 }

 
 
 
 /**
  * Result of the evaluation of a rule on a particular item in a given environment. 
  * In this implementation the RHS is evaluated systematically after the condition.
  * It might be useful to evaluate it only on demand in the case where merging
  * rules discard this rule and RHS provoke side effects.
  * type RuleEvaluationStatus = 'OK'|'KO'|'discarded'|'error'
  */
class RuleEvaluation implements IErrorManager {
   /**
    * @var Rule!
    */
   public $rule ;
   public $item ;
   public $itemType ;
   /**
    * @var RuleEvaluationStatus
    */
   protected $status ;
   public $errors ;
   public $conditionResult ;
   /**
    * @var List*(Key,Value)! The RHS evaluated or an empty array if the status is not OK
    */
   public $rhsResult ;
   
   public function getRule() {
     return $this->rule ;
   }
   
   public function getRuleId() {
     return $this->rule->getId() ;
   }
   /**
    * @return RuleEvaluationStatus!
    */
   public function getStatus() {
     return $this->status ;
   }
   
   public function getErrors() {
     return $this->errors ;
   }
   
   public function toString() {
     return $this->rule->getId().':'.$this->status ;
   }
   
   public function toMap($withItemInfo=true) {
     $map = array() ;
     $map['rule.id']=$this->rule->getId() ;
     if($withItemInfo) {
       $map['rule.eval.item']=$this->item ;
       $map['rule.eval.itemType']=$this->itemType ;
     }
     $map['rule.eval.status']=$this->status ;
     switch ($this->status) {
       case 'error':
         $map['rule.eval.errors']=$this->errors ;
         break ;
       case 'OK':
         $map['rule.eval.conditionResult']=$this->conditionResult ;
         $map['rule.eval.rhsResult']=$this->rhsResult ;
         break ;
       case 'discarded':
       case 'KO':
       default:
     }
     return $map ; 
   }
   
   public function toJson($withItemInfo=true,$beautify=true) {
     return jsonEncode($this->toMap(),$beautify) ;
   }
      
   public function getRHSResult() {
     return $this->rhsResult ;
   }
   
   protected function _evalRule(&$environment=array()) {
     $this->conditionResult = null ;
     $this->rhsResult = array() ;
     $this->errors = array() ;
     // check if the rule has to be evaluated
     if ($this->rule->matchesRestriction($this->itemType)) {
       $this->conditionResult = $this->rule->evalCondition($this->item,$environment,$this->errors) ;
       if (count($this->errors)>0) {
         $this->status = 'error' ;
       } elseif ($this->conditionResult === null) {
         $this->status = 'KO' ;
       } else {
         $this->status = 'OK' ;
         // evaluate the RHS within the environment computed
         $this->rhsResult = $this->rule->evalRHS($this->item,$environment) ;
       }
     } else {
       $this->status = 'discarded' ;
     }
   }
   
   /**
    * Evaluate a rule
    * @param Rule! $rule the rule to evaluate
    * @param Item! $item the item on which the rule has to be evaluated
    * @param ItemType! $itemType the type of the item to match against rule restriction
    * @param InOut>Environment? $environment the environment
    */
   public function __construct(Rule $rule,$item,$itemType,&$environment=array()) {
     $this->rule = $rule ;
     $this->item = $item ;
     $this->itemType = $itemType ;
     $this->_evalRule($environment) ;
   }
 }

 
 
 

/**
 * List of rules in a given order (the order may or may be not important for
 * the evaluation dependending on the characteristics of the rule).
 */
class RuleList {
  /**
   * @var RuleList! the ordered list of rules 
   */
  protected $rules ;

  /**
   * The list of rules
   * @return RuleList! 
   */
  public function getRules() {
    return $this->rules ;
  }

  /**
   * Number of rules in this list
   * @return Integer>=0 number of rules
   */
  public function getRulesNumber() {
    return count($this->getRules()) ;
  }

  public function toListOfMaps() {
    $r= array() ;
    foreach($this->rules as $rule) {
      $r[] = $rule->toMap() ;
    }
    return $r ;
  }
  /**
   * Json representation of the RuleList
   * @param Boolean? $beautify indicates if json should be indented
   * @return Json!
   */
  public function toJSON($beautify=true) {
    $json=json_encode($this->toListOfMaps()) ;
    if ($beautify) {
      $json=jsonBeautifier($json) ;
    }
    return $json ;
  }
  
  public function toString() {
  }
  
  
  protected function _getListOfMapsFromCsvFile($csvFilename) {
    $csv = new CSVFile() ;
    if (! $csv->load($csvFilename)) {
      die('Cannot read rules from csv file '.$csvFilename);
    }
    return $csv->getListOfMaps() ;
  }
  
  protected function _getListOfMapsFromJsonFile($jsonFilename) {
    $json = file_get_contents($jsonFilename) ;
    if ($json === false) {
      die('Cannot read rules from json file '.$jsonFilename);
    }
    return jsonDecodeAsMap($json) ;
  }
  
  
  /**
   * Create a rule list
   * @param RuleList|"*.csv"|"*.rules.json" $rulesOrFile either the list
   * of rules or a csv file or a .rules.json file.
   */
  public function __construct($rulesOrFile) {
    
    // read the list of maps, each map corresponding to a rule
    if (is_string($rulesOrFile) && endsWith($rulesOrFile,'.csv')) {
      $listOfMaps = $this->_getListOfMapsFromCsvFile($rulesOrFile) ;
    } elseif (is_string($rulesOrFile) && endsWith($rulesOrFile,'.rules.json')) {
      $listOfMaps = $this->_getListOfMapsFromJsonFile($rulesOrFile) ;
    } else {
      $listOfMaps = $rulesOrMap ;
    }
    if (!is_array($listOfMaps)) {
      die('incorrect set of rules') ;
    }
    
    // convert each map to a rule object
    $this->rules = array() ;
    foreach($listOfMaps as $map) {
      $this->rules[] = new Rule($map) ;
    }
  }
}





/**
 * Evaluation of a rule list for a given item in a given environment
 * type RuleEvaluationList == List*(RuleEvaluation!)!
 * type RuleEvaluationListPerStatus = Map*(RuleEvaluationStatus!,RuleEvaluationList!)!
 */
class RuleListEvaluation {
  /**
   * @var RuleList!
   */
  protected $ruleList ;
  /**
   * @var Any!
   */
  protected $item ;
  /**
   * @var String!
   */
  protected $itemType ;
  /**
   * The list of all rule evaluation whatever their status
   * and independently from the merging process.
   * @var Map(RuleEvaluationStatus!,List*(RuleEvaluation!)!)  
   */
  protected $ruleEvaluationListPerStatus ;
  
  /**
   * The final results after potential merging.
   * @var Map(Key,value)
   */
  protected $finalRHSResult ;
  
  /**
   * For traceability indicates which rules have contributed to
   * the rhs result for a given key
   * @var Map(Key,Rule)
   */
  protected $finalMapKeyToRules ;
  
  public function toMap() {
    $map=array() ;
    $map['rules.eval.item']=$this->item ;
    $map['rules.eval.itemType']=$this->itemType ;
    foreach($this->ruleEvaluationListPerStatus as $status=>$ruleEvaluations) {
      switch ($status) {
        case 'error':
        case 'KO':
        case 'discarded':
          foreach($ruleEvaluations as $ruleEvaluation) {
            $map['rules.eval.rules'][$status][] = $ruleEvaluation->rule->getId() ;
          }
          break ;
        case 'OK':
          foreach($ruleEvaluations as $ruleEvaluation) {
            $ruleid = $ruleEvaluation->rule->getId() ;
            $map['rules.eval.rules'][$status][$ruleid] = $ruleEvaluation->toMap(false) ;
          }
          break ;
        default:
          die(__FUNCTION__.': unexpected status '.$status) ;
      }
    }
    return $map ;
  }
  
  public function toJson($beautify=true) {
    return jsonEncode($this->toMap(),$beautify) ;
  }
   
  /**
   * RuleEvaluationListPer
   * @param unknown_type $status
   * @return Map(RuleEvaluationStatus,List*(RuleEvaluation!)!)|List
   */
  public function getRuleEvaluationListPerStatus($status=null) {
    if ($status===null) {
      return $this->ruleEvaluationListPerStatus ;
    } else {
      if (isset($this->ruleEvaluationListPerStatus[$status])) {
        return $this->ruleEvaluationListPerStatus[$status] ;
      } else {
        return array() ;
      }
    }
  }
  
  public function getRuleEvaluationCountPerStatus() {
    $result =array() ;
    foreach($this->ruleEvaluationListPerStatus as $status => $list) {
      $result[$status]=count($list) ;
    }
    return $result ;
  }
  
  /**
   * @return List*(RuleEvaluation!)!
   */
  public function getMatches() {
    if (isset($this->ruleEvaluationListPerStatus['OK'])) {
      $this->ruleEvaluationListPerStatus['OK'] ;
    }
  }
  
  public function getNbOfRulesMatched() {
    return  count($this->getMatches()) ;
  } 
  
  public function _mergeMatches() {
    $results=$this->ruleEvaluationListPerStatus['OK'] ;
    $mergeMethod='longestPattern' ;
        $mergedResult = array() ;
      $mergedResult['rule.merging']['nbOfMatchingRules'] = count($results) ;
      foreach($results as $result) {
        var_dump($result) ;
        foreach ($result as $resultKey => $resultValue) {
          $resultPatternLength = $result['rule.patternLength'] ;
  
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
                $mergedResult['rule.merging']['rule.patternLength'][$resultKey]=$resultPatternLength ;
              }
            } else {
              // there is already a value for that key
              if ($mergedResult[$resultKey]===$resultValue) {
                // this is the the same value, excellent!
                // to nothing except to update the pattern length for that key as it may win
                if ($mergeMethod==='longestPattern'
                    && $mergedResult['rule.merging']['rule.patternLength'][$resultKey] < $resultPatternLength) {
                  $mergedResult['rule.merging']['rule.patternLength'][$resultKey]=$resultPatternLength ;
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
                  @ $mergedResult['rule.merging']['conflicts'][$resultKey][]=$resultValue ;
  
                  switch ($mergeMethod) {
                    case 'longestPattern' :
                      if ($mergedResult['rule.merging']['rule.patternLength'][$resultKey] <= $resultPatternLength) {
                        // the current rule has a longer pattern, so select this value
                        $mergedResult[$resultKey] = $resultValue ;
                        // update also the patternLength
                        $mergedResult['rule.merging']['rule.patternLength'][$resultKey] = $resultPatternLength ;
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
      // unset($mergedResult['rule.merging']['rule.patternLength']) ;
      return $mergedResult ;
  }
  
  /**
   * @param unknown_type $environment
   */
  public function _evalRuleList(&$environment=array()) {
    $this->ruleEvaluationListPerStatus = array() ;
    // evaluate all rules
    foreach ($this->ruleList->getRules() as $rule) {
      $ruleEvaluation = new RuleEvaluation($rule,$this->item,$this->itemType,$environment) ;
      $status = $ruleEvaluation->getStatus() ;
      $this->ruleEvaluationListPerStatus[$status][] = $ruleEvaluation ; 
    }
    // merge the RHS of the matching rules if any
    switch(count($this->ruleEvaluationListPerStatus['OK'])) {
      case 0:
        $this->finalRHSResult = array() ;
        $this->finalMapKeyToRules = array() ;
        break ;
      case 1:
        $theOKRuleEvaluation = $this->ruleEvaluationListPerStatus['OK'][0] ;
        // TODO xxxx
        // TODO $this->finalMapKeyToRules = array($theOKRuleEvaluation->getRule()) ;
        break ;
      default:
        // TODO $ruleEvaluations = $this->ruleEvaluationListPerStatus['OK'] ;
        // xxxx
       
    }
  }
  
  
  public function __construct(RuleList $ruleList,$item,$itemType,&$environment=array()) {
    $this->ruleList = $ruleList ;
    $this->item = $item ;
    $this->itemType = $itemType ;
    $this->_evalRuleList($environement) ;  
  }
  
}
  
//   /**
//    * @param unknown_type $groups
//    * @return Ambigous <List*(Rule!)!, Map*(Scalar!,Map*(Scalar!,Map*(Scalar!,Any!))!), multitype:unknown , unknown>
//    */
//   public function getRulesSummary($groups=array()) {
//     $summary['rules']=$this->rules ;
//     foreach($groups as $group) {
//       $summary[$group] = groupedBy($group,$this->rules) ;
//     }
//     return $summary ;
//   }
  

  
  
class RuleListEvaluationWithMerger {
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


  
}
  


/**
 * A rule list with a function to match a itemSet, and some ways to parameterize
 * the way items are matched (id of items, type of items, part to match).
 * This class does not store anything about the itemSet collection itself.
 * It just provide some transient result.
 * 
 * type Item == Any!
 *
 * type ItemSet = Set*(Item!)!
 *
 * type ItemPartToMatchFun = Fun(Item! -> Any!) | null  // represented as string
 *
 * type ItemTypeFun = Fun(Item! -> String!) | null
 *
 * type ItemIdFun = Fun(Item! -> Scalar!) | null
 *
 * type ItemSetMatchResult == Map {
 *         'nbOfItems'           => Integer>0!,
 *         'nbOfMatchedItems'    => Integer>=0!,
 *         'nbOfUnmatchedItems'  => Integer>=0!,
 *         'matchRatio'          => Real in [0..100]!,
 *         'nbOfMultipleMatches' => Integer>=0!
 *         'matchedItemInfos'    => Set*(MatchedItemInfo!)!,
 *         'unmatchedItems'      => Set*(ItemId!)
 *       } +
 *       ... groups ... (To describe)
 *
 * type MatchedItemInfo == Map {
 *         'item'                => ItemId!
 *         'mergedResult'          => MergedRule!
 *         'allMatchResults'     => MatchedRuleList ?       // only if more than one rules has matched
 *       }
 */
class RuleListTransientMatcher extends RuleList {
  
      
  /**
   * @var ItemTypeFun
   */
  protected $itemTypeFun ;
  
  /**
   * @var ItemIdFun
   */
  protected $itemIdFun ;
  
  /**
   * @var (AnyItem! -> Any!)? If not null, a function that yieds for an item,
   * the information to be used to match. For instance if items are objects,
   * a particular field can be returned. If null the $item value will be used
   * for the match.
   * If items are scalar value this function is most likely to be set to null.
   */
  protected $itemPartToMatchFun ;
  
  /**
   * @param ItemPartToMatchFun? $fun This function return for an item, a value
   * that is used for the matching.
   */
  public function setItemPartToMatch($fun) {
    $this->itemPartToMatchFun = $fun ;
  }
  
  /**
   * Match the item set given in parameter and return the result.
   * This method does not change the state of the object and do not
   * store items, etc.
   * @param $itemSet
   * @param $groupSpecifications 
   * @return ItemSetMatchResult!
   */
  public function matchTransientItemSet($itemSet,$groupSpecifications=null) {
    $unmatchedItems=array() ;
    $matchedItemInfos=array() ;
    $nbOfMultipleMatches=0 ;
    foreach($itemSet as $item) {
      // get the item id
      $itemId = applyFun($this->itemIdFun,$item) ;
      // get the item part to match
      $partToMatch = applyFun($this->itemPartToMatchFun,$item) ;
      // get the item type
      $itemType = applyFun($this->itemTypeFun,$item) ;
  
      $matchResults = $this->matchingRules($itemType,$partToMatch) ;
  
      if (count($matchResults)===0) {
  
        // NO MATCH
        $unmatchedItems[]=$itemId ;
      } else {
  
        // MATCH
        $matchInfo = array() ;
        $matchInfo['item'] = $itemId ;
        $matchInfo['mergedResult'] = $this->mergeMatchResultList($matchResults) ;
        if (count($matchResults)>=2) {
          $matchInfo['allMatchResults'] = $matchResults ;
          $nbOfMultipleMatches++ ;
        }
        $matchedItemInfos[] = $matchInfo ;
      }
    }
  
    // SUMMARY
    $nbOfItems = count($itemSet) ;
    $nbOfUnmatchedItems = count($unmatchedItems) ;
    $nbOfMatchedItems = count($matchedItemInfos) ;
    $matchRatio = $nbOfItems===0 ? 0 : (($nbOfMatchedItems/$nbOfItems)*100) ;
  
    $output =
    array(
        'nbOfItems'           => $nbOfItems,
        'nbOfMatchedItems'    => $nbOfMatchedItems,
        'nbOfUnmatchedItems'  => $nbOfUnmatchedItems,
        'matchRatio'          => $matchRatio,
        'nbOfMultipleMatches' => $nbOfMultipleMatches,
        'matchedItemInfos'    => $matchedItemInfos,
        'unmatchedItems'      => $unmatchedItems,
    ) ;
    if (isset($groupSpecifications)
        && is_array($groupSpecifications) && count($groupSpecifications)>=1) {
      $output = array_merge($output,groupAndProject($groupSpecifications,$filesMatched)) ;
    }
    
    return $output ;
  }
  
  /**
   * Create a rule based file system pattern matcher
   * @param RuleList|"*.csv"|"*.rules.json" $rulesOrFile either the list
   * of rules or a csv file or a .rules.json file.
   */
  public function __construct($rulesOrFile,$itemTypeFun=null,$itemIdFun=null,$itemPartToMatchFun=null) {
    parent::__construct($rulesOrFile) ;
    $this->itemTypeFun = $itemTypeFun ;
    $this->itemIdFun = $itemIdFun ;
    $this->itemPartToMatchFun = $itemPartToMatchFun;
  }
}





/**
 * A matcher that store both rules and a set of items.
 */
class RuleListIncrementalMatcher extends RuleListTransientMatcher {

  /**
   * @var ItemSet! The current list of items
   */
  protected $currentItemSet ;
  
  /**
   * @var ItemSetMatchResult | null  the result of the application of rules
   * on the current item set. Computed on demand and reset when the set of
   * rules change or the item set change.
   */
  protected $currentItemSetMatchResult ;
  
  public function setCurrentItemSet($itemSet) {
    $this->currentItemSet = $itemSet ;
    $this->currentItemSetMatchResult = null ;
  }
  
  public function matchCurrentItemSet()  {
    if (!isset($this->currentItemSetMatchResult)) {
      $this->currentItemSetMatchResult = $this->matchTransientItemSet($this->currentItemSet) ;
    }
    return $this->currentItemSetMatchResult ;
  }

  public function generate(
       $outputbase=null,
       $matchedFilesGroupSpecifications=array(),
       $rulesGroups
    ) {

    // TODO this code was the 
    //if (isset($groupSpecifications)
    //    && is_array($groupSpecifications) && count($groupSpecifications)>=1) {
    //  $result = array_merge($result,groupAndProject($groupSpecifications,$result["matchedItems"])) ;
    // }
    
    //----- rules.html --------------------------------------------------
    $html =  "<h2>Rules</h2>" ;
    $html .= '<b>'.count($this->rules).'</b> rules defined<br/>' ;
    $html .= mapOfMapToHTMLTable($this->rules,'',true,true,null,2) ;
    $files['rules.html'] = $html ;



    //----- rulesSummary.json --------------------------------------------------
    $rulesSummary = $this->getRulesSummary($rulesGroups) ;
    $files['rulesSummary.json'] = jsonBeautifier(json_encode($rulesSummary)) ;
    
    
    
    $r = $this->matchCurrentItemSet() ;
    
    
    //----- matchedItems.html --------------------------------------------------        
    $html = '' ;
    foreach ($r['matchedItemInfos'] as $matchedItemInfo ) {
      $item = $matchedItemInfo['item'] ;
      $mergedResult = $matchedItemInfo['mergedResult'] ;

      $html .= "<hr/><b>".$item."</b><br/>" ;

      if (isset($mergedResult['conflictingKeys'])) {
        $keys = $mergedResult['conflictingKeys'] ;
        foreach ($keys as $key) {
          $html .= "<li><span style='background:red;'>conflict on key $key </span></li>" ;
          $mergedResult[$key] = "<li>".implode("<li>",$mergedResult[$key]) ;
        }
      }
      $html .= "Merged result" ;
      $html .= mapOfMapToHTMLTable(array($mergedResult),'',true,true,null,2) ;

      if (isset($matchedItemInfo['allMatchResults'])) {
        $html .= mapOfMapToHTMLTable($matchedItemInfo['allMatchResults']) ;
      }
    }
    $files['matchedItems.html'] = $html ;


    //----- unmatchedItems.html --------------------------------------------------
    $html = '' ;
    // TODO $html =  "<h3>Basenames of files not matched</h3>" ;
    // TODO $html .=  mapOfMapToHTMLTable($r['basenamesOfFilesNotMatched'],'',true,true,null,2) ;
    // TODO $html .= "<h3>Extensions of files not matched</h3>" ;
    //// TODO $html .=  mapOfMapToHTMLTable($r['extensionsOfFilesNotMatched'],'',true,true,null,2) ;
    $files['unmatchedItems.html'] = $html ;


    //----- matchSummary.json --------------------------------------------------
    $files['matchSummary.json'] = json_encode($r) ;



    //=========== save the file in $output =============================
    if (is_string($outputbase)) {
      $index = "" ;
      $index .= '<b>'.count($this->rules).'</b> rules defined</br>' ;
      //TODO $index .= $r['nbOfFilesMatched']." files matched over ".$r['nbOfFiles']." files : ".$r["matchRatio"]."%<br/>" ;
      foreach($files as $file => $content) {
        saveFile(addToPath($outputbase,$file),$content) ;
        $index .= '<li><a href="'.$file.'">'.$file.'</a></li>' ;
      }
      $output['index.html']=$index ;
      saveFile(addToPath($outputbase,'index.html'),$index) ;
    }
    return $r ;
  }

  public function ___construct($rulesOrFile,$itemTypeFun=null,$itemIdFun=null,$itemPartToMatchFun=null) {
    parent::__construct($rulesOrFile,$itemTypeFun,$itemIdFun,$itemPartToMatchFun) ;
    $this->currentItemSet = array() ;
    $this->currentItemSetMatchResult = null ;
    
  }
}
