<?php

require_once 'Errors.php' ;
require_once 'PFunLibrary.php' ;
/*----
 * PExpression stands for (p)ipe expression or (p)attern expression as they actually support
 * both patterns and pipe-like model of computation. This kind of expression if both
 * inspired by unix and by template languages. The main benefit is that expression are quite
 * concise and therefore useful for some scripting situations.
 * 
 * Simply put a pipe sequence corresponds to a series of function applications to one implicit
 * parameter. Just like in unix pipe model the input is implicit.
 * For instance the PExpression
 *     f | g | h
 * is the concrete syntax for the function 
 *     \lamda x . h(g(f(x))  
 * Note that there is not need to declare a variable name.
 * 
 * Functions can have one or more constant parameters.
 * For instance  if the input is a filename the expression
 *   content                     returns its content
 *   content | head  3           returns the 3rd first lines
 *   content | head 3 | lower    returns these lines in lower cases
 *   
 * The last meaning of the last expression is
 *   \lambda x . lower(head(content(x),3))
 * 
 * The interest of PExpression directly resides in the library of functions
 * that it provides.
 * 
 * PExpression are typically useful to deal with String values but other
 * type of values can be considered (using appropriate operators).
 * 
 * In particular, it provides useful function for pattern matching. 
 * The pattern expressions below allow to use different pattern notations depending
 * on the problem at hand. While "patterns functions" are intended to be used for 
 * arbitrary kind of strings (plain text, programming language identifiers, file names), 
 * some special features are handy to deal with specific domains. This is the case for
 * instance when dealing with path names (e.g. directory, file names or their content).
 *
 * Pattern expression can be more or less complex. A pattern can either be a simple string
 * (in which case it is assumed to be a regexpr), or a string with some modifiers 
 * written as prefixes and separated with ":". 

 * == Examples  ==
 *   /elem/                                is a standard (perl)regexpr and will match "element" or "selem"  
 *   lower | matches /elem/                will match "ELemenT" or "SELEM" as the string is first converted to lower cases
 *                                         "lower" is a PatternFunction. 
 *   basename | lower | matches /elem/     will match "ElemeT" but not ".../elementHere/butNotThere" 
 *   endsWith .java                        will match all strings ending with .java with no special characters being taken into
 *                                         account. Not that suffix is a PatternType, not a PatternFunction: it does not change
 *                                         the value to be matched but the interpretation of the pattern itself. In this example
 *                                         this is handy because "." is interpreted as any char in a regexpr while the user certainly
 *                                         want to express that the string ends with ".java"
 * 
 */

/** 
 * == Definition ==
 * 
 * 
 * PExpression    ::= POrExpression
 * POrExpression  ::= PAndExpression (" || " PAndExpression)*
 * PAndExpression ::= PAtom (" && " PAtom)*
 * PAtom ::= 
 *     "( " PExpression " )" 
 *   | PipeSequence
 *   
 * PipeSequence ::=
 *   PFunApplication (" | " PFunApplication)*
 *  
 * PFunApplication ::=             // function to be combined in a pipe mode
 * 
 *       'basename'                // unix basename, returns the short file name without directory (could be used for any string)
 *     | 'dirname'                 // unix dirname, remove the last component (could be used for any string)
 *     | 'corename'                // remove the extension of the basename. See fileExtension in megalib for documentation.
 *     | 'extension'               // just get the extension, that is the string after the leftmost '.'
 *     | 'lower'                   // convert to lowercase
 *     | 'upper'                   // convert to uppercase
 *     | 'trim'                    // remove starting and trailing spaces
 *     | 'ftrim'                   // full trim: remove starting and trailing spaces and duplicated spaces 
 *     | 'content'                 // the content of a given file name or null if the file name cannot be read
 *     | 'nop'                     // no operation. Usefull mostly when code is generated for instance.
 *     | 'head' <n>                // the n first lines (or less)
 *     | 'equals' <w>              // null if not equals to the word <w>. No special characters.
 *                                 // Example: "equals the" will match "the" but not "then".
 *     | 'equalsOne' <w>+          // null if not equals to any word in the list.
 *     | 'startsWith' <w>          // null if the argument does not start with the <w>
 *                                 // Example: "startsWith README" will match "README.txt" but not "abc/README.php" or "readme.md"
 *     | 'startsWithOne' <w>+      // null if the argument does not start with any of the words in the list
 *     | 'endsWith' <w>            // null if the argument does not ends with the <w>. No special characters.
 *                                 // Example: "endsWith .java" will match "abc/yes.java" and ".java"  but not ".java.something"
 *     | 'endsWithOne' <w>+        // null if the argument does not ends with any of the words in the list
 *     | 'matches' <regexpr>       // null if the argument does not match the php-perl regexpr 
 *                                 // See http://www.php.net/manual/en/reference.pcre.pattern.syntax.php
 *                                 // Examples: "matches .*" will match everything string
 *                                 //           "rmatches /^readme/i  will match all strings starting with readme ignoring case (i modifer)
 *                                 //           "regexpr #.java$# will match "ajava" as "." means any characters except newlines
 *                                 //           "regexpr:#get([a-z]+)Listener# will match "getCompanyListener" and set the match 1 to "Company"
 *                                 // See the url above for the documentation
 *     | 'matchesOne' <regexpr>+   // null if the argument does not match any of the php-perl regexpr
 *     | 'umatches' <regexpr>      // TO BE IMPLEMENTED
 *                                 // TO BE IMPLEMENTED true if the string match the unix file regular expression
 *                                 // Examples: "match:*.java" will match all strings that ends with ".java", "." being a regular character
 *                                 //                          and * meaning any characters (.* in regexpr).
 *     | 'eval' <w>+               // 
 *     | 'exec' <cmd> <w>+         //  
 *     
 */


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




//--------------------------------------------------------------------------
//   AbstractPExpression
//--------------------------------------------------------------------------
//
// Defines the Abstract Syntax and the interpreter
//


/**
 *
 */
abstract class AbstractPExpression extends BasicErrorManager implements PExpression {
  
  protected abstract function apply($value,&$environment) ;
  
  
  public function doEval($value,&$environment) {
    $this->resetErrors() ;
    $result = $this->apply($value,$environment) ;
    return $result ;  
  }
    
  public function __construct() {
    $this->resetErrors() ;
  }
}



class POperatorExpression extends AbstractPExpression {
  /**
   * @var String!
   */
  public $operator ;
  /**
   * @var List*(PExpression!)!
   */
  public $operands ;
  
  protected function apply($value,&$environment) {
    switch ($this->operator) {
      case '|':
        foreach($this->operands as $operand) {
          $value = $operand->apply($value,$environment) ;
        }
        return $value ;
        break ;
      case '&&';
        foreach($this->operands as $operand) {
          $result = $operand->apply($value,$environment) ;
          if ($result===null) {
            return null ;
          }
        }
        return $result ;
        break ;
      case '||';
        foreach($this->operands as $operand) {
          $result = $operand->apply($value,$environment) ;
          if ($result!==null) {
            return $result ;
          }
        }
        return null ;
        break ;
      default:
        die(__FUNCTION__.": operator ".$this->operator." not supported") ;
    }
  }
  public function __construct() {
    parent::__construct() ;
  }
  
}

/*
* A actual function application parsed.
*/

class PFunApplication extends AbstractPExpression {
  /**
   * @var String! the name of the PFun
   */
  public $name ;
  
  /**
   * @var List+(String!)! the list of actual parameters
   */
  public $parameters ;
    
  /**
   * @var 0|1|2|3|'*'|'+' The number of parameters, * means it takes an array.
   * Note that this exclude the $value parameter for all function
   * and that additional the environment non native function
   */
  public $argsMode ;
    
  /**
   * @var $String! the corresponding php function
   */
  public $phpName ;
  
  /**
   * @var Boolean! if true this is a native php function, with not support for environment
   */
  public $native ;
  
  
  /**
   * Evaluate a PFunApplication for a given value and within a given environment.
   * Modify the environment accordingly.
   * @param Any1! $value the value to transform
   * @param inout>Env environment
   * @return Any2! the result of the application of the pfun
   * 
   *  native  && argsMode==0|1|2|3        => $phpName($value [,$param11 [,$param2 [,$param3] )
   *  native  && argsMode='*'             => $phpName($value, $parameters) 
   *  !native                             => $phpName($value, $parameters, &$environment)
   */
  
  protected function apply($value,&$environment) {
    if ($value === null) {
      $result = null ;     // TODO this test should be removed for the "default" function
    } else {
      $environment['_last'] = $value ;
      $phpfun = $this->phpName ;
      $parameters = $this->parameters ;
      if ($this->native) {
        switch ($this->argsMode) {
          case 0:
            $result = $phpfun($value) ;
            break ;
        
          case 1:
            $result = $phpfun($value,$parameters[0]) ;
            break ;
        
          case 2:
            $result = $phpfun($value,$parameters[0],$parameters[1]) ;
            break ;
        
          case 3:
            $result = $phpfun($value,$parameters[0],$parameters[1],$parameters[2]) ;
            break ;
        
          case '*':
          case '+':
            $result = $phpfun($value,$parameters) ;
            break ;
        
          default:
            die(__FUNCTION__.": invalid arguments specification for pfun ".$this->name) ;
        }
      } else {
        $library=PFunLibrary::getInstance() ;
        $result = $library->$phpfun($value,$parameters,$environment) ;
      }
    }
    return $result ;
  }
  public function __construct() {
    parent::__construct() ;
  }

}




/**
 * An evaluation of PExpression. This class provides methods for dealing
 * explicitelywith parsing, errors, evaluation of pexpression, etc.
 * Using this class is therefore recommended for intensive computing,
 * tracking errors, etc. Otherwise evalPExpr provides a simple yet
 * non optimized short cut.
 */
class PExpressionEvaluator {
  protected $stringExpression ;
  protected $parsedPExpression ;
  protected $parser ;
  protected $environment ;

  /**
   * @param PExpressionString! $expr
   * @return PExpression? $pexpr the parsed expression or null in case of error
   */
  public function compile($expr) {
    $this->errors=array() ;
    $this->stringExpression = $expr ;
    $this->parser->setExpression($expr) ;
    $this->parsedPExpression = $this->parser->parse() ;
    $this->errors=$this->parser->getErrors() ;
    return $this->parsedPExpression ;
  }

  public function getParsedExpression() {
    return $this->parsedPExpression ;
  }
  public function doEval($value,&$environment=array()) {
    $this->errors=array() ;
    if ($this->parsedPExpression===null) {
      $this->error[] = "invalid expression. Cannot run." ;
      return null ;
    }
    return $this->parsedPExpression->doEval($value,$environment) ;
  }

  public function exec($expr,$value,&$environment=array(),$onErrors="echo") {
    $this->errors=array() ;
    $this->compile($expr) ;
    $result = $this->doEval($value,$environment) ;
    $errors = $this->compile($expr) ;
    if (count($this->errors)!==0) {
    } else {
      return $result ;
    }
  }

  public function __construct() {
    $this->parser = new PExpressionParser() ;
    $this->environment = array() ;
  }
}

interface PExpression {
  public function doEval($value,&$environment) ;
}


