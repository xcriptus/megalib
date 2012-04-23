<?php defined('_MEGALIB') or die("No direct access") ;
/**
 * Basic support for CSV files.
 * WARNING, table rows and columns start as 1, but the header is a 
 * sequence (starting at 0).
 * TODO. Currently all the file is readed. This is ok for the header, 
 * but this could be on demand when accessing a particular line.
 */

class CSVFile {
  protected /*Boolean!*/ $valid ;
  protected /*(Filename|URL)?*/ $urlFile ;
  
  /* a two-dimensional array where rows are indexed by String starting with 1 not 0!!! */
  protected /*Map*<String,Map<(String|Integer)!,String!>!>?*/ $table ;  // loaded on demand
  protected /*List+<(String)!*/ $header ;  // valid if the csvfile is valid
  
  public function /*(Filename|URL)?*/ getUrlFile() {
    assert('$this->valid') ;
    return $this->urlFile ;
  }
  
  public function /*String!*/ getCSVBaseName() {
    assert('$this->valid') ;
    return basename($this->urlFile,'.csv') ;
  }
  
  public function isValid() {
    return $this->valid ;
  }
  
  public function getRowNumber() {
    assert('$this->valid') ;
    return count($this->table) ;
  }
  
  public function getColumnNumber() {
    assert('$this->valid') ;
    return count($this->header) ;
  }
  
  public function /*<List+<String|Integer)!*/ getHeader() {
    assert('$this->valid') ;
    return $this->header ;
  }

  public function /*Map<(String|Integer)!,String!>?*/ getRow(/*String!*/ $rowid) {
    assert('$this->valid') ;
    assert('strlen($rowid)>=1') ;
    $row = $this->table[$rowid] ;
    return $row ;
  }
  
  public function /*List*<(Integer|String)!>!*/ getAllRowKeys() {
    assert('$this->valid') ;
    return array_keys($this->table) ;
  }
  
  public function /*Boolean*/ load($url,$hasHeader=TRUE,$keyfield="id|soid",$separator=",") {
    $this->urlFile = $url ;
    $this->table = array() ;
    $this->header = array() ;
    $file = fopen($url, "r") ;
    if ($file === FALSE) {
      $this->valid = false ;
      return false ;
    }  
    // the header is either explicitly given in the first row or computed as column number
    // that is if there are more columns than in the header line, or there is no header line
    // then it the header will be completed with column number starting with 1
    if ($hasHeader) {
      $this->header = fgetcsv($file, 10000, $separator) ; 
      if ($this->header === FALSE) {
        $this->valid = false ;
        return false ;      
      }
    } else {
      $this->header = array() ;
    }
    $rownb = 1 ;
    while (($row = fgetcsv($file, 100000, $separator)) !== FALSE) { 
        $columnnb=1 ;
        foreach($row as $cell) {
          // if there is no such header so far for such column then define it with the column number
          if (! isset($this->header[$columnnb-1])) {
            $this->header[$columnnb-1] = $columnnb ;
          } 
          $this->table[$rownb+""][$this->header[$columnnb-1]]=$cell ;
          $columnnb++ ;
        }
        $rownb++ ; 
    }
    fclose($file);
    $this->valid = true ;
    return true ;
  }
  
  public function __construct() {
    $this->valid = false ;
  }
}