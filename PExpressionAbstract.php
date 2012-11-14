<?php

require_once 'Errors.php' ;
require_once 'Json.php' ;
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
 * TopLevelExpression  ::= PExpression EOF
 * PExpression         ::= POrExpression 
 * POrExpression       ::= PAndExpression (" || " PAndExpression)*
 * PAndExpression      ::= PPipeExpression (" && " PPipeExpression)*
 * PPipeExpression     ::= PAtom (" | " PAtom)*
 * 
 * PAtom ::= 
 *   | PParentherizedExpression 
 *   | PMapExpression 
 *   | PListExpression    
 *   | PFunApplication
 *   | PRegularToken
 *   
 * PMapExpression ::= 
 *   "{ " (PAtom PAtom)* " }"
 *   
 * PListExpression ::=
 *   "[ " PAtom* " ]"
 * 
 * PBasicExpression ::=
 *     PFunApplication
 *   | PTemplateExpression
 *   
 * PFunApplication ::=
 *     PFunName PAtom*
 *     
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
 *     | 'content'                 // the content of a given file name as a string or null if the file name cannot be read
 *     | 'jsonContent'             // the content of a given json file as a structure or value or null if the file cannot be read or not json
 *     | 'nop'                     // no operation. Usefull mostly when code is generated for instance.
 *     | 'head' <n>                // the n first lines (or less)
 *     | 'isFile'                  // null if the file does not exist
 *     | 'isDir'                   // null if the directory does not exist
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
 *     | 'count'                   // nb of elements in an collection
 *     | 'sum'                     // sum of the elements in the array
 */





interface PExpression {
  public function doEval($value,&$environment) ;
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
  
  public abstract function toMap() ;
  
  public function toJson($beautify=true) {
    return jsonEncode($this->toMap(),$beautify) ;
  }
  
  public function doEval($value,&$environment) {
    $this->resetErrors() ;
    $environment['_last'] = $value ;
    $result = $this->apply($value,$environment) ;
    return $result ;  
  }
  
  public function __toString() {
    return $this->toJson() ; 
  }
    
  public function __construct() {
    $this->resetErrors() ;
  }
}

class PInvalidExpression extends AbstractPExpression {
  public $errors ;
  public function toMap() {
    return array("ERROR" => $errors) ;
  }
  protected function apply($value,&$environment) {
    return null ;
  }
  public function PInvalidExpression($errors) {
    parent::__construct() ;
    $this->errors = $errors ;
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
  
  public function toMap() {
    $operandsMaps = array() ;
    foreach ($this->operands as $operands) {
      $operandsMaps[]=$operands->toMap() ;
    }
    return array($this->operator => $operandsMaps) ;
  }
  
  protected function apply($value,&$environment) {
    switch ($this->operator) {
      case '|':
        foreach($this->operands as $operand) {
          $value = $operand->apply($value,$environment) ;
        }
        return $value ;
        break ;
      case '&&':
        foreach($this->operands as $operand) {
          $result = $operand->apply($value,$environment) ;
          if ($result===null) {
            return null ;
          }
        }
        return $result ;
        break ;
      case '||':
        foreach($this->operands as $operand) {
          $result = $operand->apply($value,$environment) ;
          if ($result!==null) {
            return $result ;
          }
        }
        return null ;
        break ;
      case '[]':
        $resultingList = array() ;
        foreach($this->operands as $operand) {
          $result = $operand->apply($value,$environment) ;
          if ($result!==null) {
            $resultingList[] = $result ;
          }
        }
        return $resultingList ;
        break ;
      case '{}':
        $resultingMap = array() ;
        $nbOfPairs = count($this->operands) / 2 ;
        for($i=1;$i<=$nbOfPairs;$i++) {
          $keyExpression = $this->operands[($i-1)*2] ;
          $key = $keyExpression->apply($value,$environment) ;
          $valueForKeyExpression = $this->operands[($i-1)*2+1] ;
          $valueForKey = $valueForKeyExpression->apply($value,$environment) ;
          if ($key!==null && $valueForKey!==null) {
            $resultingMap[$key] = $valueForKey ;
          }
        }
        return $resultingMap ;
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
   * type CollectionMode ==
   *     'null'      // the function cannot be applied on collection
   *     'apply'     // the function takes a collection as parameter, so apply it
   *     'map'       // apply the function for each element of the collection
   *     'filter'    // select only elements that do not return null 
   * @var CollectionMode
   */
  public $collectionMode ;
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
  
  
  /*
   * @see AbstractPExpression::toMap()
   */
  public function toMap() {
    $parametersMaps = array() ;
    foreach ($this->parameters as $parameter) {
      $parametersMaps[]= $parameter->toMap() ;  // $parameter->toMap() ;
    }  
    return array($this->name=>$parametersMaps) ;
  }
  
  protected function _basicApply($value,&$environment=array()) {
    $phpfun = $this->phpName ;
    // parameter evaluation should not be done for lambda expression parameters
    $parameterValues = array() ;
    foreach ($this->parameters as $parameter) {
      $r = $parameter->apply($value,$environment) ;
      $parameterValues[]=$r ;
    }
    if ($this->native) {
      switch ($this->argsMode) {
        case 0:
          $result = $phpfun($value) ;
          break ;
    
        case 1:
          $result = $phpfun($value,$parameterValues[0]) ;
          break ;
    
        case 2:
          $result = $phpfun($value,$parameterValues[0],$parameterValues[1]) ;
          break ;
    
        case 3:
          $result = $phpfun($value,$parameterValues[0],$parameterValues[1],$parameterValues[2]) ;
          break ;
    
        case '*':
        case '+':
          $result = $phpfun($value,$parameterValues) ;
          break ;
    
        default:
          die(__FUNCTION__.": invalid arguments specification for pfun ".$this->name) ;
      }
    } else {
      $library=PFunLibrary::getInstance() ;
      $result = $library->$phpfun($value,$parameterValues,$environment) ;
    }
    return $result ;
  }
  
  protected function _collectionApply($value,&$environment=array()) {
    switch ($this->collectionMode) {
      case null:
        $this->addError('cannot apply '.$this->name.' function to a collection' ) ;
        $result = null ;
        break ; 
      
      case 'apply':
        $result = $this->_basicApply($value,$environment) ;
        break ;
        
      case 'map':
      case 'filter':
        $result = array() ;
        $index=0 ;
        foreach($value as $key => $elem) {
          $environment["key"]=$key ;
          $environment["index"]=$index ;
          $index++ ;
          $resultElem = $this->apply($elem,$environment) ;
          if ($resultElem===null && $this->collectionMode==='map') {
            $this->addError("Error on the element #$index with key $key when evaluating a map");
          }
          if ($resultElem!=null) {
            $result[$key] = $resultElem ;
          }
        }
    }
    return $result ;
  }
  
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
  
  protected function apply($value,&$environment=array()) {
    if ($value === null) {
      $result = null ;     // TODO this test should be removed for the "default" function
    } else {
      if (is_array($value)) {
        $result = $this->_collectionApply($value,$environment) ;
      } else {
        $result = $this->_basicApply($value,$environment) ;
      }
    }
    return $result ;
  }
  public function __construct() {
    parent::__construct() ;
  }

}

/**
 * A template expression
 */
class PTExpression extends AbstractPExpression {
  protected $tExpression ;
  public function toMap() {
    return array("template"=>$this->tExpression) ;
  }
  public function apply($value,&$environment=array()) {
    $evaluator = new TExpressionEvaluator() ;
    return $evaluator->doEval($this->tExpression,$environment) ; ;
  }
  public function __construct($texpression) {
    parent::__construct() ;
    $this->tExpression = $texpression ;
  }
}


/**
 * A constant expression. The value can be any php value: scale, array, etc.
 */
class PConstant extends AbstractPExpression {
  protected $constantValue ; 
  public function toMap() {
    return $this->constantValue ;
  }
  
  protected function apply($value,&$environment=array()) {
    $environment['_last'] = $value ;
    return $this->constantValue ;
  }
  public function __construct($constantValue) {
    parent::__construct() ;
    $this->constantValue = $constantValue ;
  }
}

// /**
//  * 
//  * --- still used ????
//  * An evaluation of PExpression. This class provides methods for dealing
//  * explicitelywith parsing, errors, evaluation of pexpression, etc.
//  * Using this class is therefore recommended for intensive computing,
//  * tracking errors, etc. Otherwise evalPExpr provides a simple yet
//  * non optimized short cut.
//  */
// class PExpressionEvaluator {
//   protected $stringExpression ;
//   protected $parsedPExpression ;
//   protected $parser ;
//   protected $environment ;

//   /**
//    * @param PExpressionString! $expr
//    * @return PExpression? $pexpr the parsed expression or null in case of error
//    */
//   public function compile($expr) {
//     $this->resetErrors() ;
//     $this->stringExpression = $expr ;
//     $this->parser->setExpression($expr) ;
//     $this->parsedPExpression = $this->parser->parse() ;
//     $this->errors=$this->parser->getErrors() ;
//     return $this->parsedPExpression ;
//   }

//   public function getParsedExpression() {
//     return $this->parsedPExpression ;
//   }
//   public function doEval($value,&$environment=array()) {
//     $this->errors=array() ;
//     if ($this->parsedPExpression===null) {
//       $this->error[] = "invalid expression. Cannot run." ;
//       return null ;
//     }
//     return $this->parsedPExpression->doEval($value,$environment) ;
//   }

//   public function exec($expr,$value,&$environment=array(),$onErrors="echo") {
//     $this->errors=array() ;
//     $this->compile($expr) ;
//     $result = $this->doEval($value,$environment) ;
//     $errors = $this->compile($expr) ;
//     if (count($this->errors)!==0) {
//     } else {
//       return $result ;
//     }
//   }

//   public function __construct() {
//     $this->parser = new PExpressionParser() ;
//     $this->environment = array() ;
//   }
// }



