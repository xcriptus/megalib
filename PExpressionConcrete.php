<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Strings.php' ;
require_once 'Parsing.php' ;
require_once 'PExpressionAbstract.php' ;
require_once 'PFunLibrary.php' ;

/**
 * Parser of PExpression. 
 */
class PExpressionParser extends AbstractParser {    
  protected $functionLibrary  ;
  
  
      
  //------------------------------------------------------
  //    Parsing Rules
  //------------------------------------------------------
   
  /**
   * Parameterized parsing function for rules like
   *   part (op part)* 
   * which are translated into a POperatorExpression.
   * Note that if there is only one operator, then only the result of
   * the parsing of part is returned with no operator.
   * @param String! $operator
   * @param String! $methodName name of the parse method for sub rules
   * @return POperatorExpression|PExpression
   */
  protected function parseOperatorExpression($operator,$methodName) {
    $list = $this->parseNonEmptyList($operator, $methodName) ;
    if ($list===null) {
      return null ;
    }
    if (count($list)===1) {
      return $list[0] ;
    } else {
      $opExpr = new POperatorExpression() ;
      $opExpr->expression=null;
      $opExpr->operator=$operator ;
      $opExpr->operands=$list ;
      return $opExpr ;
    }
  }
  
  
  protected function parseRegularToken() {
    $token = $this->lexer->pullToken() ;
    if (TExpressionEvaluator::isConstant($token)) {
      return new PConstant($token) ;
    } else {
      return new PTExpression($token) ;
    }
  }
  
  //   PFunApplication ::= <funname> <parameters> *
  //
  protected function parsePFunApplication() {
    if (DEBUG>10) echo "parsePFunApplication " ;
    $token = $this->lexer->pullToken() ;
    if ($token===null) { 
      return null ;
    } else {
      
      //---- get the tokens ------------------
      // the first token is the function name
      $pFunName = $token ;
      // reads the parameters
      $parametersExpression = array() ;
      while ( $this->lexer->hasMoreTokens() 
              && ! $this->lexer->isNextTokenIn(array('|','&&','||',')',']','}',',')) ) {
        $expr = $this->parseAtom() ;
        if ($expr==null) {
          $this->addError("parameter of function $pFunName cannot be parsed" ) ;
          $expr = new PInvalidExpression($this->getErrors()) ;
        }
        $parametersExpression[] = $expr ;
      }
      //----- get the function from the library --------
      $funApplication = $this->functionLibrary->getPFunApplication($pFunName,$parametersExpression) ;
      if (is_string($funApplication)) {
        $this->addError($funApplication) ;
        return null ;
      } else {
        return $funApplication ;
      }
    }
  }
  
//   protected function parseTemplateExpression() {
//     $tokens = array() ;
//     while ( $this->lexer->hasMoreTokens()
//         && ! $this->lexer->isNextTokenIn(array('|','&&','||',')',']','}',',')) ) {
//       $tokens[] = $this->lexer->pullToken() ;
//     }
//     $template = implode(' ',$tokens) ;
//     return new PTExpression($template) ;
//   }
  
//   protected function parseBasicExpression() {
//     if (DEBUG>10) echo "parseBasicExpression " ;
//     $nextToken = $this->lexer->lookNextToken() ;
//     if ($this->functionLibrary->isFunctionName($nextToken)) {
//       return $this->parsePFunApplication() ;
//     } else {
//       return $this->parseTemplateExpression() ;
//     }
//   }
  
//   protected function parseTokenOrPParentherizedExpression() {
//     $nextToken = $this->lexer->lookNextToken() ;
//     if ($nextToken==='(') {
//       $result = $this->parseParentherizedExpression() ;
//     } else {
//       $token = $this->lexer->pullToken() ;
//       $result = new PTExpression($token) ;
//     }
//     if ($result===null) {
//       $this->addError('Token or parenthesis expected') ;
//     }
//     return $result ;
//   }
  
//   protected function parseTokenOrPAtom() {
//     $nextToken = $this->lexer->lookNextToken() ;
//     if (in_array($nextToken,array('(','[','{'))) {
//       $result = $this->parseAtom() ;
//     } else {
//       $token = $this->lexer->pullToken() ;
//       $result = new PTExpression($token) ;
//     }
//     if ($result===null) {
//       $this->addError('Token, [, (, or { expected') ;
//     }
//     return $result ;
//   }

  protected function parseParentherizedExpression() {
    $this->lexer->pullToken('(') ;
    //=====   TSequence ::= '(' PExpression ')'
    $texpr = $this->parsePExpression() ;
    if ($texpr===null) {
      $this->addError('invalid expression after ( ') ;
      return null ;
    }
    $token = $this->lexer->pullToken(')') ;
    if ($token!==')') {
      return null ;
    }
    return $texpr ;
  }
  
  
  protected function parseListExpression() {
    $this->lexer->pullToken('[') ;
    $list = array() ;
    //=====   "[ " (PExpression)* " ]"
    $nextToken = $this->lexer->lookNextToken() ;
    while ($nextToken !== ']' && $nextToken !== null) {
      $element = $this->parseAtom() ;
      if ($element===null) {
        $this->addError('invalid expression in list expression [') ;
        $element = new PInvalidExpression($this->errors) ;
      }
      $list[]=$element ;
      $nextToken = $this->lexer->lookNextToken() ;
    }
    $token = $this->lexer->pullToken(']') ;
    if ($token!==']') {
      return null ;
    }
    $e=new POperatorExpression();
    $e->operator='[]' ;
    $e->operands=$list ;
    return $e ;
  }
  
  
  protected function parseMapExpression() {
    $this->lexer->pullToken('{') ;
    $list = array() ;
    //=====   "{ " ((PToken|PParentherizedExpression) PExpression)* " }"
    $nextToken = $this->lexer->lookNextToken() ;
    while ($nextToken !== null && $nextToken !== '}' ) {
   
      // get the key 
      $key=$this->parseAtom() ;
      if ($key===null) {
        $this->addError('Invalid key expression in map expression.') ;
        $key=new PInvalidExpression($this->errors) ;
      }
      
      // get the expression
      $expression = $this->parseAtom() ;
      if ($expression===null) {
        $this->addError('invalid value expression in map expression') ;
        $expression=new PInvalidExpression($this->errors) ;
      }
      
      $list[]=$key ;        // even elements are key
      $list[]=$expression ; // odd elements are expressions 
      $nextToken = $this->lexer->lookNextToken() ;
    }
    $token = $this->lexer->pullToken('}') ;
    if ($token!=='}') {
      return null ;
    }
    $e=new POperatorExpression();
    $e->operator='{}' ;
    $e->operands=$list ;
    return $e ;
    
    return $texpr ;
  }
  
  //   TAtom ::= '(' PExpression ')'
  //              |  PFunApplication ('|' PFunApplication)*   
  //
  protected function parseAtom() {
    if (DEBUG>10) echo "parseAtom " ;
    $nextToken = $this->lexer->lookNextToken() ;
    if ($nextToken===null) { 
      return null ;
    } else {

      switch ($nextToken) {
        case '(':
          return $this->parseParentherizedExpression() ;
          break ;
        case '[':
          return $this->parseListExpression() ;
          break ;
        case '{':
          return $this->parseMapExpression() ;
          break ;
        default:
          if ($this->functionLibrary->isFunctionName($nextToken)) {
            return $this->parsePFunApplication() ;
          } else {
            return $this->parseRegularToken() ;
          }
         
      }     
      return $this ;
    }
  }
    
  protected function parsePipeExpression() {
    if (DEBUG>10) echo "parseAndExpression " ;
    return $this->parseOperatorExpression('|','parseAtom') ;
  }
  
  protected function parseAndExpression() {
    if (DEBUG>10) echo "parseAndExpression " ;
    return $this->parseOperatorExpression('&&','parsePipeExpression') ;
  } 
  
  protected function parseOrExpression() {
    if (DEBUG>10) echo "parseOrExpression " ;
    return $this->parseOperatorExpression('||','parseAndExpression') ;
  }
  
  protected function parsePExpression() {
    return $this->parseOrExpression() ;
  }
  
  public function parseTopLevelRule() {
    $result = $this->parsePExpression() ;
    if ($this->lexer->hasMoreTokens()) {
      $tokens = array() ;
      while ($this->lexer->hasMoreTokens  ()) {
        $tokens[] =  $this->lexer->pullToken() ;
      }
      $this->addError('Remaining tokens ignored :'.implode(' ',$tokens)) ;
    } 
    return $result ;
  }

  public function __construct() {
    parent::__construct(new FunBasedLexer('words')) ;
    $this->functionLibrary = PFunLibrary::getInstance() ;
  }
}



/**
 * Concrete PExpression
 */
class ConcretePExpression extends BasicErrorManager implements PExpression {
  /**
   * @var String! the expression represented as a string
   */
  protected $stringExpression ;
  
  protected $parser ;

  /**
   * @var Boolean|null true indicates that the expression has been successfully parsed
   * (the result is in abstractPExpression) ; false indicates an error during parsing ;
   * null indicates that the parse has not been used yet.
   */
  protected $isValid ;

  /**
   * @var abstractPExpression?  the parsed expression if already parsed.
   * Null otherwise. Use getAbstractPExpression() instead of accessing this field.
   */
  protected $abstractExpression ;

  public function getStringExpression() {
    return $this->stringExpression ;
  }
  
  public function toString() {
    $status = 
      ($this->isValid === null)
        ? ' [notValidatedYet]'
        : (($this->isValid === false)
             ? ' [INVALID]'
             : ' [Validated]') ;        
    return $this->stringExpression.$status ;
  }
  public function getAbstractExpression() {
    // if not already parsed, parse the expression
    if ($this->isValid===null) {
      $this->abstractExpression = $this->parser->parse($this->stringExpression) ;
      $errors = $this->parser->getErrors() ;
      if (count($errors)!==0) {
        $this->abstractExpression = null ;
        $this->mergeErrors($this->parser,'parser') ;
        $this->isValid = false ; 
      } else {
        $this->isValid = true ;
      }      
    }
    // results depends on the validity of the parse expression
    return
      $this->isValid
        ? $this->abstractExpression
        : null ;
  }
  
  

  public function doEval($value,&$environment) {
    $pexpr = $this->getAbstractExpression() ;
    if ($pexpr===null) {
      $this->addError('PExpression '.$this->stringExpression.' is invalid') ;
      return null ;
    } else {
      $result = $pexpr->doEval($value,$environment) ;
      $errors = $this->mergeErrors($pexpr,'eval') ;
      return $result ;
    }
  }

  public function __construct($stringExpression,Parser $parser=null) {
    parent::__construct() ;
    $this->resetErrors() ;
    $this->stringExpression = $stringExpression ;
    $this->isValid = null ;
    if (isset($parser)) {
      $this->parser = $parser ;
    } else {
      $this->parser = new PExpressionParser() ;
    }
  }
}


//--------------------------------------------------------------------------
//   top level functions or helpers
//--------------------------------------------------------------------------

/**
 * Simplest way to evaluate PExpression. Prefer PExpressionEvaluator
 * for non trivial cases.
 */
function evalPExpression($expr,$value,&$environment=array(),$onErrors="die") {
  $e = new ConcretePExpression($expr) ;
  $result = $e->doEval($value,$environment) ;
  return $result ;
}

/**
 * The objective of this function is to replace the preg_match
 * used in particular in files.
 * @param unknown_type $expr
 * @param unknown_type $value
 * @param unknown_type $matches
 * @return number
 */
function matchPattern($expr,$value,&$matches) {
  $environment = array() ;
  $result = evalPExpression($expr,$value,$environment) ;
  if ($result===null) {
    return 0 ;
  } else {
    return 1 ;
  }
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
    return evalTExpression($template,$matches) ;
  } else {
    return null ;
  }
}