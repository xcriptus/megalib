<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Lexing.php' ;


/**
 * Interface of a parser. A parser converts ConcreteExpression (strings) into
 * AbstractExpression (arbitrary structure) and generate errors if the concrete
 * expression is not valid.
 * type ConcreteExpression == String
 * type AbstractExpression == Any
 */
interface Parser {
  /**
   * Parse a concrete expression (a string) and return an abstrast expression or null in case of errors
   * @param ConcreteExpression! $expression
   * @return AbstractExpression|null the parsed expression or null if case of errors
   */
  public function parse($expression) ;
}




/**
 * An abstract parser dealing with token stream operations and basic
 * parsing management. Also contains error management.
 */
abstract class AbstractParser extends BasicErrorManager implements Parser {
  /**
   * This function should be implemented in parser.
   * The token stream has been initialised, so the
   * function car start with statment like pullToken etc.
   */
  protected abstract function parseTopLevelRule() ;
  
  /**
   * @var String? The string to be parsed. Use setExpression 
   */
  protected $expression ;
  
  /**
   * @var IHelperLexer The lexer used to get tokens
   */
  protected $lexer ;

  /**
   * A parameterized parsing function for rules like
   *   part (separator part) *
   * where separator is an token acting and sub a parsing function name
   * @param String! $separator the token serving as separator. e.g. "&&"
   * @param String! $parsingPartMethodName the method that parse the different
   * @return List+(Any!)! the list of part results.
   */
  protected function parseNonEmptyList($separator,$parsingPartMethodName) {
    $parts = array() ;
    $part = $this->$parsingPartMethodName() ;
    if ($part===null) {
      return null ;
    }
    $parts[] = $part ;
    while ($this->lexer->hasMoreTokens() && $this->lexer->lookNextToken()===$separator) {
      $this->lexer->pullToken($separator) ;
      $part = $this->$parsingPartMethodName() ;
      if ($part===null) {
        return null ;
      }
      $parts[] = $part ;
    }
    return $parts ;
  }


  //------------------------------------------------------
  //    Parser function
  //------------------------------------------------------

  public function parse($expression) {
    $this->resetErrors() ;
    $this->lexer->setExpression($expression) ;
    $this->lexer->startTokenStream() ;
    $result = $this->parseTopLevelRule() ;
    $this->mergeErrors($this->lexer,'lexer') ;
    return $result ;
  }
  
  public function __construct(HelperLexer $lexer) {
    $this->lexer = $lexer ;
  }
  
  
}

