<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Strings.php' ;
require_once 'PExpressionAbstract.php' ;
require_once 'TExpression.php' ;  // used by eval

/**
 * A singleton class that register all functions that can be referenced
 * in a PFunctionApplication and therefore PExpression
 */

class PFunLibrary {
  private static $instance ;
    
  private static $predefined_function_definitions = array(
      'basename'       => array('basename',0,true),
      'dirname'        => array('dirname',0,true),
      'corename'       => array('fileCoreName',0,true),
      'extension'      => array('fileExtension',0,true),
      'lower'          => array('strtolower',0,true),
      'upper'          => array('strtoupper',0,true),
      'trim'           => array('trim',0,true),
      'ftrim'          => array('ftrim',0,true),
      'nop'            => array('_nop',0,true),
      'content'        => array('_content',0,false),
      'head'           => array('_head',1,false),
      'equals'         => array('_equals',1,false),
      'equalsOne'      => array('_equalsOne',1,false),
      'startsWith'     => array('_startsWith',1,false),
      'startsWithOne'  => array('_startsWithOne','+',false),
      'endsWith'       => array('_endsWith',1,false),
      'endsWithOne'    => array('_endsWithOne','+',false),
      'contains'       => array('scontains',1,true),
      'containsOne'    => array('_containsOne','+',false),
      'containsAll'    => array('_containsAll','+',false),
      'matches'        => array('_matches',1,false),
      'matchesOne'     => array('_matchesOne','+',false),
      'matchesAll'     => array('_matchesOne','+',false),
      'set'            => array('_set',1,false),
      'eval'           => array('_eval',1,false),
      'exec'            => array('_exec','+',false)
    ) ;
  
  /**
   * @var The list of function definitions. Initialized with some
   * predefined functions but new ones can be added.
   */
  private $function_definitions ;
    
  private function __construct() {
    $this->function_definitions = self::$predefined_function_definitions ;
  }
  
  /**
   * Singleton class. 
   * @return PFunEvaluator
   */
  public static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  /**
   * @param String! 
   * @param String! $expression an expression that could start with a pFunApplication
   * @return PFunApplication? a PFunApplication object in case of success or null otherwise. 
   */
  public function getPFunApplication($funName,$parameters) {
    if (isset($this->function_definitions[$funName])) {
      $spec = $this->function_definitions[$funName] ;
      $funApplication = new PFunApplication() ;
      $funApplication->expression    = null ;
      $funApplication->name          = $funName ;
      $funApplication->parameters    = $parameters ;
      $funApplication->phpName       = $spec[0] ;
      $funApplication->argsMode      = $spec[1] ;
      $funApplication->native        = $spec[2] ;
      if (is_integer($funApplication->argsMode) 
          && count($parameters)!==$funApplication->argsMode) {
        return null ;
      } elseif ($funApplication->argsMode==='+' && count($parameters)==0) {
        return null ;
      } else {
        return $funApplication ;
      }
    }  
  }
  
  
  //-------------------------------------------------------------------------------------
  //    predefined non native functions
  //-------------------------------------------------------------------------------------
  
  
  function _nop($value,$parameters,&$environment) {
    return $value ;
  }
  
  // the "content" fun
  function _content($value,$parameters,&$environment) {
    if (!is_string($value)) {
      return null ;
    }
    @ $content = file_get_contents($value) ;
    if ($content===false) {
      return null ;
    } else {
      return $content ;
    }
  }
  
  function _head($value,$parameters,&$environment) {
    if (!is_string($value)) {
      return null ;
    } ;
    $lines = explode("\n",$value) ;
    $headlines = array_slice($lines,0,intval($nb)) ;
    return implode("\n",$headlines) ;
  }
  
  function _equals($value,$parameters,&$environment) {
    if ($value === $parameters[0]) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _equalsOne($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      if ($value === $parameter) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _startsWith($value,$parameters,&$environment) {
    if (startsWith($value,$parameters[0])) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _startsWithOne($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      if (startsWith($value,$parameter)) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _endsWith($value,$parameters,&$environment) {
    if (endsWith($value,$parameters[0])) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _endsWithOne($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      if (endsWith($value,$parameter)) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _containsOne($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      if (contains($value,$parameter)) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _containsAll($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      if (!contains($value,$parameter)) {
        return null ;
      }
    }
    return $value ;
  }
    
  function _matches($value,$parameters,&$environment) {
    if (preg_match($parameters[0],$value,$matches)) {
      $environment += $matches ;
      $environment['_match'] = $matches[0] ;
      $environment['_matchExpr'] = $parameters[0] ;
      return $matches[0] ;
    } else {
      return null ;
    }
  }
  
  function _matchesOne($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      $result = self::_matches($value,$parameters,$environment) ;
      if ($result !== null) {
        return $result ;
      }
    }
    return null ;
  }
  
  function _matchesAll($value,$parameters,&$environment) {
    foreach($parameters as $parameter) {
      $result = self::_matches($value,$parameters,$environment) ;
      if ($result === null) {
        return null ;
      }
    }
    return $value ;
  }
  
  function _exec($value,$parameters,&$environment) {
    $commandName = array_shift($parameters) ;
    //if (!is_executable($commandName)) {
    //  return null ;
    //}
    
    $commandLine = escapeshellcmd($commandName) ;
    foreach($parameters as $parameter) {
      if ($parameter==='$$') {
        $cmdparam = $value ; 
      } else {
        $cmdparam = $parameter ;
      }
      $commandLine .= ' '.escapeshellarg($cmdparam) ;
    }
    $output=array() ;
    $exitcode=0 ;
    $environment['_cmd']=$commandLine ;
    exec($commandLine,$output,$exitcode) ;
    $result = implode("\n",$output) ;
    $environment['_cmdOutput']=$result ;
    $environment['_cmdExit']=$exitcode ;
    if ($exitcode!=0) {
      return null ;
    } else {
      return $result ;
    }
  }
  
  function _set($value,$parameters,&$environment) {
    $environment[$parameters[0]] = $value ;
    return $value ;
  }
  
  
  
  // evaluation of TExpression 
  protected $tExpressionEvaluator ;
  
  function _eval($value,$parameters,&$environment) {
    if (!isset($this->tExpressionEvaluator)) {
      $this->tExpressionEvaluator = new TExpressionEvaluator() ;
      $this->tExpressionEvaluator->setValueOfUndefinedVariables(null) ;
    }
    $results = array() ;
    foreach($parameters as $parameter) {
      // fast track for constants
      if (TExpressionEvaluator::isConstant($parameter)) {
        $results[] = $parameter;
      } else {
        $result = $this->tExpressionEvaluator->doEval($parameter,$environment) ;
        if ($this->tExpressionEvaluator)
        $undefinedVariables=$this->tExpressionEvaluator->getUndefinedVariables() ;
        if (count($undefinedVariables)>=1) {
          $environment['_undefinedVariables']=implode(',',$undefinedVariables) ;
          return null ;
        }
        $results[]=$result ; 
      }
    }
    return implode(' ',$results) ;
  }
  
}


