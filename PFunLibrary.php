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
      'basename'       => array('basename',0,true,'map'),
      'dirname'        => array('dirname',0,true,'map'),
      'corename'       => array('fileCoreName',0,true,'map'),
      'extension'      => array('fileExtension',0,true,'map'),
      'lower'          => array('strtolower',0,true,'map'),
      'upper'          => array('strtoupper',0,true,'map'),
      'trim'           => array('trim',0,true,'map'),
      'ftrim'          => array('ftrim',0,true,'map'),
      'nop'            => array('_nop',0,true,'map'),
      'content'        => array('_content',0,false,'map'),
      'jsonContent'    => array('_jsonContent',0,false,'map'),
      'saves'          => array('_saves',1,false,null),
      'savesAsJson'    => array('_savesAsJson',1,false,null),
      'head'           => array('_head',1,false,'map'),
      'isFile'         => array('_isFile',0,true,'map'),
      'isDir'          => array('_isDir',0,true,'map'),
      'files'          => array('_files','*',false,'map'),
      'equals'         => array('_equals',1,false,'filter'),
      'equalsOne'      => array('_equalsOne',1,false,'filter'),
      'startsWith'     => array('_startsWith',1,false,'filter'),
      'startsWithOne'  => array('_startsWithOne','+',false,'filter'),
      'endsWith'       => array('_endsWith',1,false,'filter'),
      'endsWithOne'    => array('_endsWithOne','+',false,'filter'),
      'contains'       => array('scontains',1,true,'filter'),
      'containsOne'    => array('_containsOne','+',false,'filter'),
      'containsAll'    => array('_containsAll','+',false,'filter'),
      'matches'        => array('_matches',1,false,'filter'),
      'matchesOne'     => array('_matchesOne','+',false,'filter'),
      'matchesAll'     => array('_matchesAll','+',false,'filter'),
      'set'            => array('_set',1,false,'map'),
      'eval'           => array('_eval',1,false,'map'),
      'exec'           => array('_exec','+',false,null),
      'count'          => array('count',0,true,'apply'),
      'sum'            => array('array_sum',0,true,'apply'),
      '->'             => array('_navigate','+',false,'map')
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
  
  public function isFunctionName($funName) {
    return array_key_exists($funName,$this->function_definitions) ;
  }
  
  
  /**
   * @param String! 
   * @param String! $expression an expression that could start with a pFunApplication
   * @return PFunApplication|String! a PFunApplication object in case of success or an error message otherwise. 
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
      $funApplication->collectionMode = $spec[3] ; 
      if (is_integer($funApplication->argsMode) 
          && count($parameters)!==$funApplication->argsMode) {
        return  'function '.$funName.' requires '.$funApplication->argsMode.' arguments. '
               .count($parameters).' provided: '.implode(' , ',$parameters) ;
      } elseif ($funApplication->argsMode==='+' && count($parameters)===0) {
        return  'function '.$funName.' requires at least one parameter' ;
      } else {
        return $funApplication ;
      }
    }  
  }
  
  
  //-------------------------------------------------------------------------------------
  //    predefined non native functions
  //-------------------------------------------------------------------------------------
  
  
  function _nop($value,$parameters,&$environment=array()) {
    return $value ;
  }
  
  // the "content" fun
  function _content($value,$parameters,&$environment=array()) {
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
  
  function _jsonContent($value,$parameters,&$environment=array()) {
    $json = $this->_content($value,$parameters,$environment) ;
    if ($json===null) {
      return null ;
    } 
    @ $result = json_decode($json,true);  // return null in case of errors
    return $result ; 
  }
  
  function _saves($value,$parameters,&$environment=array()) {
    $filename=$paraleters[0] ;
    if (is_string($value)) {
      if (!isset($environment['_savedFiles'])) {
        $environment['_savedFiles']=array() ;
      }
      if (saveFile($filename,$value,$environment['_savedFiles'])) {
        return $value ;
      } else {
        return null ;
      }
    } else {
      return null ;
    }
  }
  
  function _savesAsJson($value,$parameters,&$environment=array()) {
    $filename=$parameters[0] ;
    if (!isset($environment['_savedFiles'])) {
      $environment['_savedFiles']=array() ;
    }
    if (saveAsJsonFile($filename,$value,$environment['_savedFiles'],true)) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _head($value,$parameters,&$environment=array()) {
    if (!is_string($value)) {
      return null ;
    } 
    $lines = explode("\n",$value) ;
    $headlines = array_slice($lines,0,intval($parameters[0])) ;
    return implode("\n",$headlines) ;
  }
  
  function _isFile($value,$parameters,&$environment=array()) {
    if (!is_string($value)) {
      return null ;
    } 
    if (@ is_file($value)) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _isDir($value,$parameters,&$environment=array()) {
    if (!is_string($value)) {
      return null ;
    } 
    if (@ is_dir($value)) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _files($value,$parameters,&$environment=array()) {
    if (@ !is_dir($value)) {
      return null ;
    }
    if (isset($parameters[0])) {
      $params = $parameters[0] ;
    } else {
      $params = array() ;
    }
    return findDirectFiles($value,$params) ;
  }
  
  function _equals($value,$parameters,&$environment=array()) {
    if ($value === $parameters[0]) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _equalsOne($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      if ($value === $parameter) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _startsWith($value,$parameters,&$environment=array()) {
    if (startsWith($value,$parameters[0])) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _startsWithOne($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      if (startsWith($value,$parameter)) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _endsWith($value,$parameters,&$environment=array()) {
    if (endsWith($value,$parameters[0])) {
      return $value ;
    } else {
      return null ;
    }
  }
  
  function _endsWithOne($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      if (endsWith($value,$parameter)) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _containsOne($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      if (contains($value,$parameter)) {
        return $value ;
      }
    }
    return null ;
  }
  
  function _containsAll($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      if (!contains($value,$parameter)) {
        return null ;
      }
    }
    return $value ;
  }
    
  function _matches($value,$parameters,&$environment=array()) {
    if (preg_match($parameters[0],$value,$matches)) {
      $environment += $matches ;
      $environment['_match'] = $matches[0] ;
      $environment['_matchExpr'] = $parameters[0] ;
      return $matches[0] ;
    } else {
      return null ;
    }
  }
  
  function _matchesOne($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      $result = self::_matches($value,$parameters,$environment) ;
      if ($result !== null) {
        return $result ;
      }
    }
    return null ;
  }
  
  function _matchesAll($value,$parameters,&$environment=array()) {
    foreach($parameters as $parameter) {
      $result = self::_matches($value,$parameters,$environment) ;
      if ($result === null) {
        return null ;
      }
    }
    return $value ;
  }
  
  function _exec($value,$parameters,&$environment=array()) {
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
  
  function _set($value,$parameters,&$environment=array()) {
    $environment[$parameters[0]] = $value ;
    return $value ;
  }
    
  // evaluation of TExpression 
  protected $tExpressionEvaluator ;
  
  function _eval($value,$parameters,&$environment=array()) {
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
  
  function _navigate($value,$parameters,&$environment=array()) {
    //var_dump($value) ;
    foreach ($parameters as $index) {
      if (!is_array($value)) {
        return null ;
      } else {
        $value = $value[$index] ;
      }
    }
    return $value ;
  }

}
