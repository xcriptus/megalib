<?php

/**
 * Evaluate a template with some variables of the form ${xxx}.
 * Return either a string when a map is given, an array of string
 * when an array of maps is given, or a map of string if an
 * map of map is given.
 * Note that the values in the mapping can themselves contains variables.
 * The replacement is done for the first occurence, and then it start
 * again. To avoid infinite recursion, the maxium number of replacement
 * can be specified.
 *
 * type TVarName == [a-zA-Z0-9_]+
 * type TVarExpr == "${" TVarName <rest> "}"
 * 
 * TODO: currently rest is ignore. Could be used for more bash like expr
 * TODO: add simple $xxx syntax
 * 
 * type Template == string // containing potentially some TVarExpr
 * type TVarMap = Map(String,Template)
 * type TMapping =
 *          TVarMap
 *        | List*(TVarMap)
 *        | Map*(String!,TVarMap!)
 * type TResult =
 *          String
 *        | List*(String!)
 *        | Map*(String!, String)
 * type TReplacement = Map{
 *        'expr' => TVarExpr!,
 *        'result' => Template!  }
 *
 */

class TExprEvaluator {
  /**
   * @var String Value used to replace undefined variable. 
   * It should not contains a variable that is not defined otherwise 
   * this will cause a infinite loop.
   */
  protected $valueOfUndefinedVariables="UNDEFINED" ;
  
  /**
   * @var List*(TVarName) lists of undefined variables during the last evaluation
   */
  protected $undefinedVariables=array() ;
  
  /**
   * @var List*(TReplacement!)? The list of all successive replacements
   */
  protected $replacements=array() ;
  
  /**
   * @var 
   */
  protected $trace="" ;
  /**
   * @var Integer $maxReplacement The maximum number of replacement in a given
   * evaluation of 
   */
  protected $maxReplacement=10000 ;
  
  protected function t($level,$x) {
    $this->trace .= str_repeat("  ",$level).$x."\n" ;
  }
  
  /**
   * @param $value List*(TReplacement!)? Set the value used to replace undefined variable. 
   */
  public function setValueOfUndefinedVariables($value) {
    $this->$valueOfUndefinedVariables=$value ;
  }
  
  /**
   * Return the list of undefined variables during the last evaluation
   * @return List*(TVarName) list of variable names.
   */
  public function getUndefinedVariables() {
    return $this->undefinedVariables ;
  }

  public function getTrace() {
    return $this->trace ;
  }
  /** 
   * 
   * @param Template $template the template to use.
   */
  protected function evalString($template,$map,$level) {
    $this->t($level,"BEGIN evalString: '$template'") ;
    $regexpr='/\$\{([a-zA-Z0-9_]+)([^}]*)\}/' ;
    $text = $template ;
    $nbReplacement = 0 ;
    while (preg_match($regexpr,$text,$matches)!==0) {
      $wholeExpression=$matches[0] ;
      $variable=$matches[1] ;
      $rest=$matches[2] ;
      if(isset($map[$variable])) {
        $replacement = $map[$variable]  ;
        if (DEBUG>10) echo "evalTemplate; $wholeExpression => $replacement<br/>" ;
      } else {
        $this->undefinedVariables[]=$variable ;
        $replacement = $this->valueOfUndefinedVariables ;
        if (DEBUG>10) echo "evalTemplate; unknown $wholeExpression => $replacement<br/>" ;
      }
      $text = str_replace($wholeExpression,$replacement,$text);
      $this->replacements[] = array('expr'=>$wholeExpression,'result'=>$replacement) ;
      $nbReplacement++ ;
      if ($nbReplacement > $this->maxReplacement) {
        die("evalTemplate: too many substitution in template/variable map (limit is $maxReplacement)") ;
      }
    }
    $this->t($level,"END evalString: '$text'") ;
    return $text ;
  }
  
  protected function evalMatch($matchExpr,$map,$level) {
    $this->t($level,"BEGIN evalMatch:") ;    
    assert('$matchExpr[0]==="MATCH"') ;
    assert('(count($matchExpr) % 2) === 0') ;
    assert('count($matchExpr) >= 4') ;
    $switcherExpr = $matchExpr[1] ;
    $this->t($level+1,"switcher") ;    
    $switcherValue = $this->evalExpr($switcherExpr,$map,$level+2) ;
    assert('is_string($switcherExpr)') ;
    $case = 0 ;
    $nbCases = (count($matchExpr)-2)/2 ;
    do {
      $case++ ;
      $filterExpr = $matchExpr[$case*2] ;
      if ($filterExpr==="*") {
        $this->t($level+1,"*") ;
        $matched = true ;
      } else {
        $this->t($level+1,"case #$case") ;
        $filterValue = $this->evalExpr($filterExpr,$map,$level+2);
        assert('is_string($filterValue)') ;
        if (substr($filterValue,0,1)==='/') {
          $this->t($level+1,"regexpr $filterValue") ;         
          $matched = preg_match($filterValue, $switcherValue, $matches) ;
        } else {
          $this->t($level+1,"=== $filterValue") ;
          $matched = ($switcherValue === $filterValue) ;
        }
      }
    } while (!$matched && $case<$nbCases) ;
    if ($matched) {
      $resultExpr = $matchExpr[$case*2+1] ;
      $mapWithMatchParams = $map ;
      if (isset($matches)) {
        $mapWithMatchParams=array_merge($matches,$map) ;
      }
      $this->t($level+1,"value #$case") ;
      $result=$this->evalExpr($resultExpr,$mapWithMatchParams,$level+2) ;
    } else {
      $this->t($level+1,"NOMATCH => null") ;
      $result=null ;
    }
    return $result ;
  }
  
  protected function evalConcat($concatExpr,$map,$level) {
    assert('startsWith("CONCAT",$concatExpr[0])') ;
    $nbExprs=count($concatExpr) ;
    $this->t($level,"BEGIN evalConcat $nbExprs elements") ;
    $result=null ;
    for($n=1; $n<$nbExprs; $n++) {
      $this->t($level+1,"element #$n") ;
      $exprValue = $this->evalExpr($concatExpr[$n],$map,$level+2) ;
      if ($exprValue===null) {
        $this->t($level+1,"element #$n=null =>(ignored)") ;
        // ignore totally null value
      } elseif ($result === null) {
        // there was no value before, so just take this one
        $result = $exprValue ;
        $this->t($level+1,"element #$n is first") ;
      } elseif (is_string($result) && is_string($exprValue)) {
        $result .= $exprValue ;
        $this->t($level+1,"element #$n string concat") ;
      } elseif (is_int_map($result) && is_int_map($exprValue)) {
        $result = array_merge($result,$exprValue) ;
        $this->t($level+1,"element #$n list concat") ;
      } elseif (is_string_map($result) && is_string_map($exprValue)) {
        $this->t($level+1,"element #$n map concat") ;        
        $result = array_merge($result,$exprValue) ;
      } else {
        die("error: CONCAT: attempt to concatenate incompatible type") ;
      }
    }
    $this->t($level,"END evalConcat") ;
    return $result ;
  }
  
  protected function evalList($listExpr,$map,$level) {
    $this->t($level,"BEGIN evalList ".count($listExpr)." elements") ;
    $result = array() ;
    $n=0 ;
    foreach($listExpr as $itemExpr) {
      $n++ ;
      $this->t($level+1,"element #".$n) ;     
      $itemValue = $this->evalExpr($itemExpr,$map,$level+2) ;
      if ($itemValue === null) {
        // ignore totally null value
      } else {
        $result[] = $itemValue ;
      }
    }
    $this->t($level,"END evalList") ;    
    return $result ;
  }
  
  protected function evalMap($mapExpr,$map,$level) {
    $this->t($level,"BEGIN evalMap ".count($mapExpr)." elements") ;
    $result = array() ;
    $n=0 ;
    foreach($mapExpr as $keyExpr => $itemExpr) {
      $n++ ;
      $this->t($level+1,"element key #$n") ;      
      $itemValue = $this->evalExpr($itemExpr,$map,$level+2) ;
      if ($itemValue === null) {
        // ignore totally null value
      } else {
        $this->t($level+1,"element value #$n") ;
        $keyValue = $this->evalExpr($keyExpr,$map,$level+2) ;
        if ($keyValue === null) {
          // ignore totally null value
        } else {
          $result[$keyValue] = $itemValue ;
        }
      }
    }
    $this->t($level,"END evalList") ;    
    return $result ;
  }
  
  protected function evalExpr($expr,$map,$level=0) {
    if (is_string($expr)) {
      return $this->evalString($expr,$map,$level) ;
    } elseif (is_numeric($expr)) {
      return $expr ;
    } elseif ($expr===null) {
      return null ;
    } elseif (is_array($expr) && isset($expr[0])) {
      $isstr0 = is_string($expr[0]) ;
      if ($isstr0 && $expr[0] === "MATCH") {
        // MATCH expression
        return $this->evalMatch($expr,$map,$level) ;
      } elseif ($isstr0 && startsWith("CONCAT",$expr[0])) {
        // CONCAT expression
        return $this->evalConcat($expr,$map,$level) ;
      } else {
        // regular array
        return $this->evalList($expr,$map,$level) ;
      }
    } elseif (is_array($expr) && count($expr)===0) {
      // an empty array. Returns an empty array
      return array() ;
    } elseif (is_array($expr)) {
      // this is a non empty array, with no first ([0]) element
      // treat it as a map
      return $this->evalMap($expr,$map,$level) ;
    }
  }

  /**
   * 
   * @param TMapping $tMapping the mapping(s) containing variables. It could
   * be either a single map, or a list or a map of map. In this later case
   * a list or map of string will be returned instead of a string.
   * Note that the variable $_ will be replaced by "_", the integer or
   * string of the map for each successive string production.
   * in each step. Default to 10000. Die if this limit is reached.
   * @return TResult! either string, a list of string or a map of string depending
   * the type of the parameter $tMapping
   */
  public function doEval($expr,$tMapping) {
    $this->undefinedVariables = array() ;
    $this->replacements = array() ;
    $this->trace = "" ;
    
    // first convert the tMapping parameter in an array to simplify
    $isSimpleMap = !is_map_of_map($tMapping) ;
    if ($isSimpleMap) {
      // Convert the simpme to a singleton indexed by _
      $mapping = array("_"=>$tMapping) ;
    } else {
      // don't care whether indexes are string or integer
      $mapping = $tMapping ;
    }
    $results = array() ;    
    
    foreach($mapping as $mapId => $map) {
      $map['_'] = $mapId ;
      $results[$mapId] = $this->evalExpr($expr,$map) ;
    }
      
    $this->undefinedVariables=array_unique($this->undefinedVariables) ;
      
    // convert back the results
    if ($isSimpleMap) {
      return $results["_"] ;
    } else {
      return $results ;
    }
  }
  
  public function doEvalJson($json,$tMapping) {
    $expr=json_decode($json,true);
    if ($expr===null) {
      die("doEvalJson: error in json: $json") ;
    }
    return $this->doEval($expr,$tMapping) ;
  }
  
  public function __construct() {
    
  }
    
}