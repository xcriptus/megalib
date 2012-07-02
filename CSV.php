<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Files.php' ;
require_once 'Json.php' ;


/**
 * Basic support for CSV files.
 * WARNING, table rows and columns start as 1, but the header is a 
 * sequence (starting at 0).
 * TODO. Currently all the file is readed. This is ok for the header, 
 * but this could be on demand when accessing a particular line.
 */

class CSVFile {
  /**
   * @var Boolean! indicates whether the current csv file has a valid
   * content or not.
   */
  protected $valid ;
  
  /**
   * @var Filename|URL? the filename or the url of the csv file
   */
  protected $urlFile ;
  
  /**
   * @var Map*<String,Map<(String|Integer)!,String!>!>? A two-dimensional array 
   * where rows are indexed by String starting with 1 not 0!!!
   */
  protected /**/ $table ;  // loaded on demand
  /**
   * @var List+(String!)! the list of column header. Valid if the csvfile is valid
   */
  protected $header ;  
  
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
  
  /**
   * The list of column names.
   * @return List+(String|Integer)!
   */
  public function  getHeader() {
    assert('$this->valid') ;
    return $this->header ;
  }
  
  /**
   * Generate a JSON string in the form of an array of map.
   * That is the rows are here not indexed by a string, but just
   * part of the json array.
   * @param Boolean? $removeBlankCells if true the blank cells
   * (that is those with en empty string as value) will not lead
   * to an entry in the JSON. 
   * @param $beautify Boolean! if true the resulting json is intended.
   * @return JSON
   */
  public function getJSON($removeBlankCells=true,$beautify=true) {
    $json = json_encode($this->getListOfMaps()) ;
    if ($beautify) {
      $json = jsonBeautifier($json) ;
    }
    return $json ;
  }
  
  public function saveAsJSON($filename,&$results,$removeBlankCells=true,$beautify=true) {
    $json = $this->getJSON($removeBlankCells,$beautify) ;
    return saveFile($filename,$content,$results) ;
  }
  
  /**
   * @return multitype:
   */
  public function getListOfMaps($removeBlankCells=true) {
    $listOfMaps = array() ;
    foreach($this->table as $map) {
      $newMap = array();
      foreach($map as $key => $value) {
        if (!$removeBlankCells || (isset($value) && $value!=="")) {
          $newMap[$key] = $value ;
        }        
      }
      $listOfMaps[] = $newMap ;
    }
    return $listOfMaps ;
    
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

  
  /**
   * 
   * The header is either explicitly given in the first row or computed as column number.
   * That is, if there are more columns than in the header line, or there is no header line
   * then the header will be completed with column number starting with 1
   *
   * @param URL! $url file name or url of the csv file
   * 
   * @param Boolean? $hasHeader indicates if the first line is the header. Default to true.
   * If there is no header lines then number starting with 1 will be used as column names.
   * 
   * @param String|Integer? $keyfield the field that will be used as the key. 
   * 
   * @param String? $separator the separator of the CSV file. Default to ','.
   * 
   * @return Boolean! true in case of success, false otherwise.
   */
  public function load($url,$hasHeader=TRUE,$keyfield=null,$separator=",") {
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