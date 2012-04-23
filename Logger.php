<?php defined('_MEGALIB') or die("No direct access") ;
/*
 * Very simple logger
 */

/*Logger!*/ $LOGGER_NOPE=new Logger(null) ;

/**
 * Return a logger. If the parameter is a logger do nothing.
 * If the parameter is null then return an inactive logger.
 * If a string is passed then is is to be a file name and a logger is constructed.
 * @param Logger!|String!|null $param Either an existing logger, a filename or null.
 * @result Logger! A logger.
 */
function toLogger($param=null) {
  global $LOGGER_NOPE ;
  if ($param==null) {
    return $LOGGER_NOPE ;
  } elseif (is_string($param)) {
    return new Logger($param) ;
  } elseif ($param instanceof Logger) {
    return $param ;
  } else {
    die('wrong type') ;
  }
}

class Logger {
  protected /*?*/ $filename ;
  protected $file ;
  protected $activated ;
  
  public function log($message) {
    if ($this->activated) {
      fwrite($this->file,$message."\n") ;
    }
  }
  
  /**
   * Turn log on. This method have no effect if the filename has no be specified.
   */
  public function on() {
    if (isset($this->filename)) {
      $this->activated = TRUE ;
    }
  }
  
  /**
   * Turn log off.
   */
  public function off() {
    $this->activated = FALSE ;
  }
  
  private function _openforappend() {
    if (isset($this->filename)) {
      $this->file = fopen($this->filename,"a") ;
      if ($this->file === FALSE) {
        die('Logger: '.$this->filename.' cannot be opened for writing') ; 
      }
    }
  }
  
  /**
   * Clear the logfile.
   */
  public function clear() {
    if ($this->filename) {
      fclose($this->file) ;
      unlink($this->filename) ;
      $this->_openforappend() ;
    }
  }
  
  /**
   * Close the file at the end of the process.
   */
  public function __destruct() {
    if ($this->filename) {
      fclose($this->file) ;
    }
  }  
  
  /**
   * @param String? $filename A filename where the log will be produce
   * or null if the log is inactive.
   */
  public function __construct($filename=NULL) {
    if (isset($filename)) {
      $this->filename = $filename ;
      $this->_openforappend() ;
      $this->activated = TRUE ;
    } else {
      $this->activated = FALSE ;
      $this->filename = NULL ;
    }
  }
}