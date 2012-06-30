<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Strings.php' ;
require_once 'Errors.php' ;



interface BasicLexer {
  
  /**
   * @param Defines the expression to analyze
   */
  public function setExpression($expression) ;
  
  /**
   * Do whatever is necessary to initialize the token stream.
   * The next pullToken operation will return the first token if any.
   * Calling hasMoreTokens will indicates whether the token streem is empty
   * or not.
   * @return void
   */
  public function startTokenStream() ;
  
  /**
   * Get the next token
   * @return String? The token read or null in case of error.
   */
  public function pullToken() ;

  /**
   * Indicate if there is more tokens/
   * @return Boolean! true if there is at least one token to read.
   */
  public function hasMoreTokens() ;

  /**
   * Look ahead the value of the next token WITHOUT reading it.
   * The function pullToken should be used instead if the goal is
   * to consume the next token.
   * @return String! the next token
   */
  public function lookNextToken() ;

}




interface HelperLexer extends BasicLexer {
  /**
   * Look ahead the value of the next token WITHOUT reading it
   * and indicates if it is in the list specificed.
   *
   * Note that the function pullToken should be used instead if the goal is
   * to consume the next token.
   * @param List+(String!)! $tokens expected
   * @return Boolean! true if the next token is in the tokens list, false otherwise
   */
  public function isNextTokenIn($tokens) ;

  /**
   * Get the next token check that it is what expected
   * @param String!|List+(String!)? $expected if specified it will be checked that the token
   * read is the one expected (if a string is given), or one of the expected (if a array
   * is given). In this is not the case some error will be added and null will be return.
   * @return String? The token read or null in case of error.
   */
  public function pullTokenAndEnsureIsIn($expected) ;
}


/**
 * A once shot lexer returning at once all tokens for an expression.
 * This interface should be used in conjuction with some other interface
 * and/or methods to first set the expression to tokenize and then store
 * and get errors.
 * type Token
 * type TokenList = List*(Token)
 */
interface AtOnceLexer extends BasicLexer {
  /**
   * Return the list of tokens or null in case of errors.
   */
  public function getTokenList() ;
}




abstract class AbstractHelperLexer extends BasicErrorManager implements HelperLexer {

  /**
   * @see Interface HelperLexer
   */
  public function isNextTokenIn($tokens) {
    $token = $this->lookNextToken() ;
    if ($token===null) {
      return false ;
    } else {
      return in_array($token,$tokens) ;
    }
  }

  /**
   * @see Interface HelperLexer
   */
  public function pullTokenAndEnsureIsIn($expected) {
    $token = $this->pullToken()  ;
    if ($token===null) {
      $this->addError('unexpected end of expression. Was expecting '.implode(' or ',$expected)) ;
      return null ;
    }
    if (is_string($expected) && $token!==$expected) {
      $this->addError('token found: "'.$token.'", but was expecting "'.$expected.'"') ;
      return null ;
    } 
    if (is_array($expected) && !in_array($token,$expected)) {
      $this->addError('token found: "'.$token.'", but was expecting '.implode(' or ',$expected)) ;
      return null ;
    }
    return $token ;
  }
}



/**
 * Abstract implementation of a AtOnceLexer where all tokens are stored in a list.
 * Provides BasicLexer and HelperLexer operations based on this list.
 * Only the getTokenList() remains to be implemented
 */
abstract class AbstractAtOnceWithHelperLexer extends AbstractHelperLexer implements AtOnceLexer,HelperLexer {
    
  /**
   * @var List*(String!)? The list of tokens.
   * Computed on demand and once per string.
   * This variable is initialized by startTokenStream
   */
  protected $tokens ;
  
  /**
   * @var Integer>=0 the index of the next token in the list of tokens.
   * (Starts at 0 according to php arrays).
   * This variable is initialized by startTokenStream
   */
  protected $nextToken ;
  
  protected $expression ;
  
  public function setExpression($expression) {
    if (!is_string($expression)) {
      var_dump($expression) ;
      die(__FUNCTION__.': the expression dumped above cannot be used as an expression') ;
    }
    $this->expression = $expression ;
  } 
  
  /**
   * Start the token stream at the beginning
   * The next token will be the first one (if any)
   */
  public function startTokenStream() {
    $this->resetErrors() ;
    $this->tokens=$this->getTokenList() ;
    if ($this->tokens===null) {
      $this->addError('An error occurred: cannot get the token list') ;
      $this->tokens = array('ERROR') ;
    }
    $this->nextToken = 0 ;
  }
  
  /**
   * Indicate if there is more tokens/
   * @return Boolean! true if there is at least one token to read.
   */
  public function hasMoreTokens() {
    $lastTokenIndex = count($this->tokens)-1 ;
    return $this->nextToken <= $lastTokenIndex ;
  }
  
  public function pullToken() {
    if (isset($this->tokens[$this->nextToken])) {
      $token = $this->tokens[$this->nextToken] ;
      $this->nextToken++ ;
      return $token ;
    } else {
      return null ;
    }
  }
  
  
  /**
   * Look ahead the value of the next token WITHOUT reading it.
   * The function pullToken should be used instead if the goal is
   * to consume the next token.
   * @return String! the next token
   */
  public function lookNextToken() {
    if (isset($this->tokens[$this->nextToken])) {
      if (DEBUG>10) echo 'nextToken #'.$this->nextToken.'='.$this->tokens[$this->nextToken].' ' ;
      return $this->tokens[$this->nextToken] ;
    } else {
      return null ;
    }
  }
  
  
  public function __construct() {
    $this->tokens=null ;
    $this->nextToken=null;
    $this->expression=null ;
  } 
}



class FunBasedLexer extends AbstractAtOnceWithHelperLexer {
  protected $tokenizer ;
  
  public function getTokenList() {
    $expression = $this->expression ;
    if (!is_string($expression)) {
      die(__FUNCTION__.'Error: input expression is not a string') ;
    }
    $fun = $this->tokenizer ;
    $tokens = $fun($expression) ;
    if ($tokens===null) {
      $this->addError("Error: cannot separate tokens (function ".$fun.')') ; 
    }
    return $tokens ;
  }
  
  public function __construct($tokenizerFun) {
    $this->tokenizer=$tokenizerFun ;
  }
}


