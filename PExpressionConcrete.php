<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Strings.php' ;
require_once 'Parsing.php' ;
require_once 'PExpressionAbstract.php' ;
require_once 'PFunLibrary.php' ;

/**
 * Parser of PExpression. 
 */
class PExpressionParser extends AbstractParser {    
      
  //------------------------------------------------------
  //    Parsing Rules
  //------------------------------------------------------
   
  /**
   * Parameterized parsing function for rules like
   *   part (op part)* 
   * which are translated into a POperatorExpression.
   * Note that if there is only one operator, then only the result of
   * the parsing of part is returned.
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
      $parameters = array() ;
      while ( $this->lexer->hasMoreTokens() 
              && ! $this->lexer->isNextTokenIn(array('|','&&','||',')')) ) {
         $parameters[] = $this->lexer->pullToken() ;
      }
      
      //----- get the function from the library --------
      $funLibrary = PFunLibrary::getInstance() ;
      $funApplication = $funLibrary->getPFunApplication($pFunName,$parameters) ;
      if ($funApplication===null) {
        $this->addError("invalid function application $pFunName") ;
        return null ;
      } else {
        return $funApplication ;
      }
    }
  }
    
  //   TAtom ::= '(' PExpression ')'
  //              |  PFunApplication ('|' PFunApplication)*   
  //
  protected function parseAtom() {
    if (DEBUG>10) echo "parseAtom " ;
    $token = $this->lexer->lookNextToken() ;
    if ($token===null) { 
      return null ;
    } else {
       
      if ($token==='(') {
        $this->lexer->pullToken('(') ;
        //=====   TSequence ::= '(' PExpression ')'
        $texpr = $this->parsePExpression() ;
        if ($texpr===null) {
          $this->errors[] = 'invalid expression after ( ' ;
          return null ;
        }
        $token = $this->lexer->pullToken(')') ;
        if ($token!==')') {
          return null ;
        }
        return $texpr ;
      } else {
        
        //=====   TSequence ::= PFunApplication ('|' PFunApplication)*
        return $this->parsePipeSequence() ;
      }
    }
  }
  
  protected function parsePipeSequence() {
    if (DEBUG>10) echo "parsePipeSequence " ;
    return $this->parseOperatorExpression('|','parsePFunApplication') ;
  }
  
  protected function parseAndExpression() {
    if (DEBUG>10) echo "parseAndExpression " ;
    return $this->parseOperatorExpression('&&','parseAtom') ;
  }
  
  protected function parseOrExpression() {
    if (DEBUG>10) echo "parseOrExpression " ;
    return $this->parseOperatorExpression('||','parseAndExpression') ;
  }
  
  protected function parsePExpression() {
    return $this->parseOrExpression() ;
  }
  
  public function parseTopLevelRule() {
    if (DEBUG>10) echo "parseOrExpression " ;
    return $this->parseOrExpression() ;
  }

  public function __construct() {
    parent::__construct(new FunBasedLexer('words')) ;
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
      $this->addError('The expression is invalid') ;
      return null ;
    } else {
      $result = $pexpr->doEval($value,$enviroment) ;
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
