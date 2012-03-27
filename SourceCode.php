<?php
require_once 'config/configSourceCode.php';
require_once 'HTML.php' ;
require_once 'Strings.php' ;


/**
 * This class provides support for source code analysis, manipulation, display, etc.
 */
class SourceCode {
  

  /**
   * @var String! a unique code that is used in particular as a prefix od html ids
   * when various source code are to be displayed in the same page. If this code is
   * not explicitely provided then is is automatically generated.
   */
  protected $sourceId ;
  
  /**
   * @var an integer used for the automatic generation of sourceIds.
   * This variable is global and represents the next available number.
   */
  private static $nextIdAvailable = 0 ;
  
  /**
   * Return a new generated source id
   * @return String! a new source id 
   */
  private static function getNewSourceId() {
    $id = SourceCode::$nextIdAvailable ;
    SourceCode::$nextIdAvailable = $id+1 ;
    return "s$id" ;
  }
  
  /**
   * @var String! language string used by the geshi package for highlighting
   */
  protected $language ;
  
  /**
   * @var String! the source code
   */
  protected $source ;
  
  /**
   * @var HTML? the html version of the highlighted source code. Computed on demand.
   */
  protected $highlighted ;
  /**
   * @var SimpleXMLElement? The XML representation of highlighted source code, computed on demand. 
   */
  protected $highlightedAsSimpleXML ;
  
  /**
   * @var GeSHI?
   */
  protected $geshi;
  
  
  /**
   * @return GeSHI!
   */
  protected function getGeSHI() {
    if (!isset($this->geshi)) {
      $geshi = new GeSHi($this->source, $this->language);
      $geshi->set_overall_id($this->sourceId) ;
      $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS) ;
      $geshi->enable_classes();
      $geshi->enable_ids(true);
      $this->geshi = $geshi ;
    }
    return $this->geshi ;
  }

  public function getRawHTML() {
    return htmlAsIs($this->source) ;
  }
  
  /**
   *
   */
  public function getHighlightingStyle($lines='',$style='background:#FFAAEE;') {
    $css = $this->getGeSHI()->get_stylesheet();
    $idprefix='#'.$this->sourceId.'-' ;
    $ids = $idprefix.implode(','.$idprefix,rangesExpression($lines)) ;
    if ($ids!='') {
      $css .= $ids. ' { '.$style.' }' ;      
    }
    return '<html><head><title>Code</title><style type="text/css"><!--'.$css.'--></style></head>' ;
  }
    
  /**
   * @return HTML?
   */
  public function getHighlightedHTML() {
    if (!isset($this->highlighted)) {
      $geshi = $this->getGeSHI() ;
      $html = $geshi->parse_code();      
      $this->highlighted = str_replace('&nbsp;',' ',$html) ;
    }
    return $this->highlighted ;    
  }
  

  
  /**
   * @return SimpleXMLElement
   */
  protected function getHighlightedAsSimpleXML() {
    if (!isset($this->highlightedAsSimpleXML)) {
      $simpleXML = simplexml_load_string($this->getHighlightedHTML()) ;
      if ($simpleXML===false) {
        die('error: HMTL is not valid XML') ;
      } else {
        $this->highlightedAsSimpleXML = $simpleXML ;
      }
    }
    return $this->highlightedAsSimpleXML ;
  }
  
  /**
   * @param String $classname
   * @return List*(SimpleXMLElement) the list of nodes corresponding to the given class
   */
  public function getTokensAsElements($classname) {
    $simpleXML = $this->getHighlightedAsSimpleXML() ;
    return $simpleXML->xpath('//span[@class="'.$classname.'"]') ;  
  }
  
  /**
   * @param unknown_type $classname
   * @return List*(String!)! 
   */
  public function getTokensAsTexts($classname) {
    $elements = $this->getTokensAsElements($classname) ;
    $texts = array() ;
    foreach ($elements as $element) {
      $texts[] = (string) $element ;
    }
    return $texts ;
  }
  
  public function __construct($text,$language,$sourceid=null) {
    $this->source = $text ;
    $this->language = $language ;
    if (isset($sourceid)) {
      $this->sourceId=$sourcedid ;
    } else {
      $this->sourceId = SourceCode::getNewSourceId() ;
    } 
  }
    
}