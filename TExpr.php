<?php

/**
 * The pattern expressions below allow to use different pattern notations depending
 * on the problem at hand. While patterns functions are intended to be used for 
 * arbitrary kind of strings (plain text, programming language identifiers, file names), 
 * some special features are handy to deal with specific domains. This is the case for
 * instance when dealing with path names (e.g. file names or directory names).
 *
 * A pattern can either be a simple string (in which case it is assumed to be a regexpr),
 * or a string with some modifiers written as prefixes and separated with ":".
 * 
 * First, various functions can be applied to the string to be matched, 
 * then the type of patterns to be used is indicated.
 * 
 * Examples:
 *   /elem/                  is a regular (perl)regexpr and will match "element" or "selem"  
 *   lower:/elem/            will match "ELemenT" or "SELEM" as the string is first converted to lower cases
 *                           "lower" is a PatternFunction. In this case it could also be written /elem/i
 *   basename:lower:/elem/   will match "ElemeT" but not ".../elementHere/butNotThere" as the "basename"
 *                           PatternFunction is first applied (see unix basename), leaving butNotThere string
 *                           for matched. Here : can be seen as a pipe. (Will be certainly changed by | in the future)
 *   suffix:.java            will match all strings ending with .java with no special characters being taken into
 *                           account. Not that suffix is a PatternType, not a PatternFunction: it does not change
 *                           the value to be matched but the interpretation of the pattern itself. In this example
 *                           this is handy because "." is interpreted as any char in a regexpr while the user certainly
 *                           want to express that the string ends with ".java"
 *                                               
 * type Pattern ==           // a pattern expression 
 *     ( <patternFunction> ':' )* [ PatternType ':' ] String
 *              
 * type 
 *   PatternFunction ==      // a string->string function to be applied to the string to be matched before matching
 *       'basename'          // unix basename, returns the short file name without directory (could be use for any string)
 *     | 'dirname'           // unix dirname, remove the last component.
 *     | 'corename'          // remove the extension of the basename. See fileExtension in megalib for documentation.
 *     | 'extension'         // just get the extension, that is the string after the leftmost '.'
 *     | 'lower'             // convert to lowercase
 *     | 'upper'             // convert to uppercase
 *     | 'trim'              // remove starting and trailing white spaces
 * 
 * type 
 *   PatternType ==
 *       'is'         // true if the whole string is equals to the pattern. No special characters.
 *                    // Examples: "is:the" will match "the" but not "then".
 *                    
 *     | 'suffix'     // true if the string ends with/is equal to the pattern. No special characters.
 *                    // Examples: "suffix:.java" will match "abc/yes.java" and ".java"  but not ".java.something"
 *                    
 *     | 'prefix'     // true if the sring starts with/is equal to the pattern. No special characters.
 *                    // Examples: "prefix:README" will match "README.txt" but not "abc/README.php" or "readme.md"
 *                    
 *     | 'regexpr'    // true if the string match the perl-php regular expression (almost standard perl)
 *                    // See http://www.php.net/manual/en/reference.pcre.pattern.syntax.php
 *                    // Examples: "regexpr:.*" will match everything string
 *                    //           "regexpr:/^readme/i  will match all strings starting with readme ignoring case (i modifer)
 *                    //           "regexpr:#.java$# will match "ajava" as "." means any characters except newlines
 *                    //           "regexpr:#get([a-z]+)Listener# will match "getCompanyListener" and set the match #1 to "Company"
 *                    // See the url above for the documentation
 *                    
 *     | 'match'      // TO BE IMPLEMENTED true if the string match the unix file regular expression  
 *                    // Examples: "match:*.java" will match all strings that ends with ".java", "." being a regular character
 *                    //                          and * meaning any characters (.* in regexpr).
 *     | 'path'       // deprecated
 *        
 * 
 */

$_PATTERN_FUNCTIONS = array(
    'basename'  => 'basename',
    'dirname'   => 'dirname',
    'corename'  => 'fileCoreName',
    'extension' => 'fileExtension',
    'lower'     => 'strtolower',
    'upper'     => 'strtoupper',
    'trim'      => 'trim' 
  ) ;
// the regexpr /^(basename|dirname|...):/ 
$_PATTERN_FUNCTIONS_PREFIX_REGEXPR = 
  '/^('.implode('|',array_keys($_PATTERN_FUNCTIONS)).'):(.*)$/' ;

/**
 * Match a string against a pattern. Different type of pattern
 * languages are supported. Matching string segments are returned.
 * 
 * @param Pattern! $pattern
 * 
 * @param String! $string string to match
 * 
 * @param out>List*(String!)? $matches the resulting matches if any.
 * $matches[0] is always the whole string matched. Then the next
 * elements are parts of match according to the expression type
 * @return Boolean! true if the string matches the pattern or
 * false otherwise.
 * 
 */
function matchPattern($pattern,$string,&$matches=null) { 
  global $_PATTERN_FUNCTIONS ;
  global $_PATTERN_FUNCTIONS_PREFIX_REGEXPR ;
  
  // apply all the functions (if any) to the string
  while (preg_match($_PATTERN_FUNCTIONS_PREFIX_REGEXPR,$pattern,$matches)) {
    $fun=$_PATTERN_FUNCTIONS[$matches[1]] ;
    $string = $fun($string) ;
    $pattern=$matches[2] ;
  }  
  
  $matches=array() ;
  // decompose the pattern if it is prefixed. otherwise regexpr is the default.
  if (preg_match('/^(is|suffix|prefix|regexpr|path):(.*)$/',$pattern,$r)) {
    $patternType = $r[1] ;
    $patternExpr = $r[2] ;
  } else {
    $patternType = 'regexpr' ;
    $patternExpr = $pattern ;
  }
  switch ($patternType) {
    case 'is':
      $result = ($string === $patternExpr) ;
      $matches[0]=$string ;
      $matches[1]=$string ;
      break ;
    case 'suffix':
      $result = endsWith($string,$patternExpr) ;
      $matches[0]=$string ;
      $matches[1]=substr($string,0,strlen($string)-strlen($patternExpr)) ;
      break ;
    case 'prefix':
      $result = startsWith($string,$patternExpr) ;
      $matches[0]=$string ;
      $matches[1]=substr($string,strlen($patternExpr)) ;
      break ;
    case 'regexpr':
      $result = preg_match($patternExpr,$string,$matches) ;
      break ;
    case 'match':
      // replace * by .* 
      // replace . by \.
      // plus do other stuff depending on the level of
      // conformance we want and on the dialect to be supported
    case 'path':
    default :
      die('matchPattern: unsupported pattern type '.$patternType) ;
  }
  return $result ;
}

/**
 * Match the given pattern and return the template where string
 * segments have replaced ${n} variables. Return null in case
 * of no match.
 * @param Pattern! $pattern
 * @param String! $string
 * @param Template! $template
 * @return TResult?
 */
function matchToTemplate($pattern,$string,$template) {
  if (matchPattern($pattern,$string,$matches)) {
    return doEvalTemplate($template,$matches) ;
  } else {
    return null ;
  }
}


/**
 * Evaluate a template according to a given map.
 * This is a shorthand to the use of the class TExprEvaluator.
 * 
 * @param Template $template
 * @param TMapping $map
 * @param inout>TExprEvaluator? $evaluator if no evaluator is provided then
 * the function create one and return it. Otherwise use the evalutor provided
 * to eval the template.
 * @return TResult!
 */
function doEvalTemplate($template,$map,&$evaluator=null) {
  // fast track if the template is indeed a constant
  if (TExprEvaluator::isConstant($template)) {
    return $template ;
  } else {
    if (! isset($evaluator)) {
      $evaluator = new TExprEvaluator() ;
    }
    return $evaluator->doEval($template,$map) ;
  }
}

/**
 * 
 * 
 * 
 *        ------------------------------------------------------------------------
 *                     TODO : complete this description with the various
 *                     elements implemented (CONCAT, MATCH, LIST, MAP,...
 *                     TODO : change preg_match with the matchMattern as define above
 *                     TODO : Support simple notation (sn) shown in the tests
 *                     TODO : Add the ${x|fun|fun...} notation base on function
 *                            define for pattern and change PatternFunction to
 *                            fun|fun|protocol:expr 
 *        ------------------------------------------------------------------------
 *
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

class TExprEvaluator {
  
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
    if (TExprEvaluator::isConstant($expr)) {
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
    $expr=jsonDecodeAsMap($json);
    return $this->doEval($expr,$tMapping) ;
  }
  

  public function __construct() {
    
  }
    
}