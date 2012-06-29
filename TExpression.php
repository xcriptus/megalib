<?php

/**
 *        ------------------------------------------------------------------------
 *                     TODO : complete this description with the various
 *                     elements implemented (CONCAT, MATCH, LIST, MAP,...
 *                     TODO : make a parser and lexical analysis explicit
 *                     TODO : change preg_match with the PExpression as define above
 *                     TODO : Support simple notation (sn) shown in the tests
 *                     TODO : Add the ${x|fun|fun...} notation 
 *        ------------------------------------------------------------------------
 *
 * Evaluate a template with some variables of the form ${xxx} or $xxx,
 * the value of these variables being replaced by their corresponding value in the
 * environment passed as parameter. Note that the values in the environment can 
 * themselves contains  variables occurrence. The replacement is done for the 
 * first occurence, and then it start again. To avoid infinite recursion, 
 * the maxium number of replacement can be specified.
 *
 * Return either a string when a map is given, an array of string when an array 
 * of maps is given, or a map of string if an map of map is given.
 *
 * type TVarName == [a-zA-Z0-9_]+
 * 
 * TVarExpr ::= 
 *     "$" TVarName 
 *   | "${" TVarName <rest> "}"    // TODO: currently rest is ignored. Could be used for more bash like expr
 * TODO: integrate this with patternMatch in Strings.h
 * 
 * type Template == string // containing potentially some TVarExpr
 * 
 * type TVarMap = Map(String,Template)
 * 
 * type TMapping =
 *          TVarMap
 *        | List*(TVarMap)
 *        | Map*(String!,TVarMap!)
 * 
 * type TResult =
 *          String
 *        | List*(String!)
 *        | Map*(String!, String)
 * 
 * type TReplacement = Map{
 *        'expr' => TVarExpr!,
 *        'result' => Template!  }
 *        
 *
 */


/**
 * Evaluate a template according to a given map.
 * This is a shorthand to the use of the class PExprEvaluator.
 *
 * @param Template $template
 * @param TMapping $map
 * @param inout>PExprEvaluator? $evaluator if no evaluator is provided then
 * the function create one and return it. Otherwise use the evalutor provided
 * to eval the template.
 * @return TResult!
 */
function evalTExpression($texpression,&$environement,&$evaluator=null) {
  // fast track if the template is indeed a constant
  if (TExpressionEvaluator::isConstant($texpression)) {
    return $texpression ;
  } else {
    if (! isset($evaluator)) {
      $evaluator = new TExpressionEvaluator() ;
    }
    return $evaluator->doEval($texpression,$environement) ;
  }
}



class TExpressionEvaluator {
  
  /**
   * @param Template $expr indicates if the template is in fact a constant
   * in which case there is no need to make some complex computation.
   * @return Boolean! true if the template is a constant, false otherwise
   */
  public static function isConstant($expr) {
    return
         is_string($expr) && (strpos($expr,'$')===false) 
      || is_bool($expr)
      || is_numeric($expr) 
      || is_array($expr) && count($expr)===0 ;
  }
  
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
    $this->valueOfUndefinedVariables=$value ;
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
   * Extract the first left-moft variable occurrence that occur
   * in a string starting first with ${xxx} format and only then
   * to $xxx format 
   * @param Template $template the template to use.
   */
  
  protected function getFirstVarExpression($text) {
    $simpleFormat = '/\$(\w+)/' ;
    $fullFormat   = '/\$\{(\w+)([^}]*)\}/' ;
    if (preg_match($fullFormat,$text,$matches)) {
      $result=array() ;
      $result['whole'] = $matches[0] ;
      $result['name']  = $matches[1] ;
      $result['trail'] = $matches[2] ;
      return $result ;
    } elseif (preg_match($simpleFormat,$text,$matches)) {
      $result=array() ;
      $result['whole'] = $matches[0] ;
      $result['name']  = $matches[1] ;
      return $result ;
    } else {
      return null ;
    }
  }
  
  protected function evalString($template,$map,$level) {
    $this->t($level,"BEGIN evalString: '$template'") ;
    
    $text = $template ;
    $nbReplacement = 0 ;
    while (($varExpr=$this->getFirstVarExpression($text)) !== null) {
      $variable = $varExpr['name'] ;
      $wholeExpression = $varExpr['whole'] ;
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
          // TODO This should be changed by a call to PExpression
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
  
  
  /**
   * Eval an expression for a given map
   * 
   * @param String|Numeric|Map*(Scalar,Any)|TMatchExpr|TConcatExpr|TListExpr| $expr
   * 
   * @param unknown_type $map
   * 
   * @param unknown_type $level Do not use this parameter. It is just defined to
   * indicates the level in case of recursive expression.
   * 
   * @return TResult!
   */
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
   * @param $expr
   * 
   * @param TMapping $tMapping the mapping(s) containing variables. It could
   * be either a single map, or a list or a map of map. In this later case
   * a list or map of string will be returned instead of a string.
   * Note that the variable $_ will be replaced by "_", the integer or
   * string of the map for each successive string production.
   * in each step. Default to 10000. Die if this limit is reached.
   * 
   * @return TResult! either string, a list of string or a map of string depending
   * the type of the parameter $tMapping
   */
  public function doEval($expr,$tMapping) {
    $this->undefinedVariables = array() ;
    $this->replacements = array() ;
    $this->trace = "" ;
    
    // fast track if this is a constant.
    if (TExpressionEvaluator::isConstant($expr)) {
      return $expr ;
    }  
    
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
  
  
  /**
   * @param unknown_type $json
   * 
   * @param TMapping $tMapping the mapping(s) containing variables. It could
   * be either a single map, or a list or a map of map. In this later case
   * a list or map of string will be returned instead of a string.
   * Note that the variable $_ will be replaced by "_", the integer or
   * string of the map for each successive string production.
   * in each step. Default to 10000. Die if this limit is reached.
   * 
   * @return TResult! either string, a list of string or a map of string depending
   * the type of the parameter $tMapping
   */
  public function doEvalJson($json,$tMapping) {
    $expr=json_decode($json,true);
    return $this->doEval($expr,$tMapping) ;
  }
  

  public function __construct() {
    
  }
    
}