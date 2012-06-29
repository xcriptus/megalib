<?php defined('_MEGALIB') or die("No direct access") ;


/**
 * type Error == String
 *
 */
interface IErrorProducer {
  /**
   * Add an error to a list of errors.
   * If $doProcessNow is set to true, the errors will be process immediately
   * according to some implementation rules. In this case the result of the
   * last operation should be provided and it will be returned by this function
   * (if the function do not die for instance).
   * @param Error! $anErrorMessage a error message
   * @param Any1? $result the result of the operation that just lead to an error.
   * It may be used by some error manager when processing errors.
   * @param Any2? $description any object describing the operations that have just
   * been executed. This parameter will be used in some implementation specific 
   * way by ErrorHandler.
   * @return Any? depends on the implementation. This is expected to be the
   * $result parameter if the errors are just recorded for instance.
   */
  public function addError($error,$doProcessNow=false,$result=null,$description=null) ;
  
  /**
   * Reset the error list. This should typically called before performing an
   * operation.
   */
  public function resetErrors() ;
}


/**
 * Basic error management.
 * 
 * When errors are processed it is possible to specify different kind of ErrorHandler.
 * The most sophisticated way is to provide a function that will do something with the
 * list of errors 
 * type ErrorHandler == 
 *     null                    // errors are ignored silently
 *   |'die'                    // the program will die when errors are processed
 *   |'echo'                   // errors are reported on the output
 *   | Fun(Any1,Any2 -> Any2)  // a function that will be called. First parameter is
 *                             // a description of what happen (the $description of
 *                             // the IErrorProducer::addError() function). 
 *                             // The second parameter is the $result of the operations 
 *                             // (parameter $result of IErrorProducer::addError() function)
 *                             // This function can do something and could return the result
 *                             // (transformed or as is).
 */
interface IErrorManager {
  /**
   * Return the list of errors produced by the last operation.
   * @return List*(Error!) the list of errors or an empty array in case of success
   */
  public function getErrors() ;
}

interface IErrorManagerWithHandler extends IErrorManager {
  /**
   * Set the error handler. It will be used for next operations until a next
   * application of this function again.
   * @param ErrorHandler $onErrors
   * @return void
   */
  public function setErrorHandler($onErrors) ;
}


/**
 *
 */
class BasicErrorManager implements IErrorManager, IErrorProducer {
  /**
   * @var List*(Error!)! the list of errors corresponding to the last operation
   */
  protected $errors ;
  
  /**
   * @var ErrorHandler! the current error manager. 
   * It is be initialized to 'echo'.
   */
  protected $errorHandler ;
  
  /**
   * @see IErrorManager::getErrors()
   */
  public function getErrors() {
    return $this->errors ;
  }

  /**
   * @see IErrorManager::setErrorHandler()
   */
  public function setErrorHandler($onErrors) {
    $this->errorHandler = $onError ;
  }
  
  /**
   * @see IErrorProducer::addError()
   */
  public function addError($error,$doProcessNow=true,$result=null,$description=null) {
    $this->errors[] = $error ;
    if ($doProcessNow===true) {
      $this->processErrors($description,$result) ;
    }
  }

  /**
   * @see IErrorProducer::resetErrors()
   */
  public function resetErrors() {
    $this->errors = array() ;
  }
  
  public function mergeErrors(IErrorProducer $errorProducer, $prefix) {
    $producerErrors = $errorProducer->getErrors() ;
    $producerErrorsCount = count($producerErrors) ;
    if ($producerErrorsCount>0) {
      foreach ($producerErrors as $error) {
        $this->errors[]=$prefix.':'.$error ;
      }
      $this->errors[]=
        $prefix.':'.$producerErrorsCount.' error'
        .(($producerErrorsCount===1)?'':'s').' found.' ;
    }
  }
  /**
   * @param unknown_type $result
   * @return unknown
   */
  protected function processErrors($description,$result) {
    if ($this->errorHandler===null) {
      // errors are ignored
      return $result ;
      
    } elseif ($this->errorHandler==='echo') {
      // errors are echoed
      foreach($this->errors as $error) {
        echo('<b><pre>ERROR: '.$error.'</pre>') ;
      }
      return $result ;
      
    } elseif ($this->errorHandler==='die') {
      //
      var_dump($this->errors) ;
      die(count($this->errors).' errors found') ;
      
    } else if (is_string($this->errorHandler)) {
      $fun = $this->errorHandler ;
      return $fun($this->errors,$description,$result) ;
      
    } else {
      die(__FUNCTION__.': Invalid errorHandler $onErrors='.$onErrors) ;
    }
  }

  public function __construct() {
    $this->errors = array() ;
    $this->errorHandler='echo' ;
  }
}