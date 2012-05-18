<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'configs/SourceCode.config.php';
require_once 'HTML.php' ;
require_once 'Strings.php' ;
require_once 'Structures.php' ;
require_once 'Files.php' ;



class GeSHiExtended extends GeSHi {  
  /**
   * @var This variable is just used to call function that should be static
   */
  private static $emptyGeshiSingleton ;
  
  /**
   * A empty geshi object to be used only for global function call
   * @return GeSHi
   */
  public static function getEmptyGeshiSingleton() {
    if (!isset(self::$emptyGeshiSingleton)) {
      self::$emptyGeshiSingleton = new GeSHi() ;
    }
    return self::$emptyGeshiSingleton ;
  }
  
  /*----------------------------------------------------------------------------------------
   *   Schema of a geshi language file
   *----------------------------------------------------------------------------------------
   *
   * Languages files contain information of different kind. There is no explicit definition
   * of the schema of GeSHi language files. The constants and functions below aims to cope with
   * this issue so that some rather generic treatments (such as analysis) can be done on the
   * overall schema. Note that the elements below are static as they describe GeSHi schema,
   * rather than a particular language expressed using this schema.
   */
  
  /**
   * @var Map(String,String) This constants defines the fields that could appear
   * in geshi language definition files as well as their type.
   * This constant value is not to be directly accessed. Use getFieldInfo() instead.
   */
  private static $languageDescriptionSchema = array(
    'LANG_NAME'        => "STR",
    'CASE_KEYWORDS'    => "BOOL",
    'CASE_SENSITIVE'   => "INT=>BOOL",
    'COMMENT_SINGLE'   => "INT=>REG",
    'COMMENT_MULTI'    => "REG=>REG",
    'COMMENT_REGEXP'   => "INT=>REG",
    'ESCAPE_CHAR'      => "STR",
    'ESCAPE_REGEXP'    => "INT=>REG",
    'HARDQUOTE'        => "INT=>REG",
    'HARDCHAR'         => "STR",
    'HARDESCAPE'       => "INT=>REG",
    'HIGHLIGHT_STRICT_BLOCK'=> 'INT=>',
    'KEYWORDS'         => "INT=>INT=>STR",
    'LANG_NAME'        => 'PHP',
    'NUMBERS'          => "ENUM",
    'OBJECT_SPLITTERS' => "INT=>REG",
    'OOLANG'           => "BOOL",
    // 'PARSER_CONTROL'
    'QUOTEMARKS'       => "INT=>REG",
    'REGEXPS'          => "INT=>REG",
    'SCRIPT_DELIMITERS'=> "INT=>INT=>REG",  
    'STRICT_MODE_APPLIES'=>"ENUM",
    'STYLES'           => "ENUM=>(INT|ENUM)=>STR",  
    'SYMBOLS'          => "INT=>INT=>STR",
    'TAB_WIDTH'        => "INT",
    'URLS'             => "INT=>STR" );  

  /**
   * @var Defines the relationships between the class prefix and the corresponding
   * information. This binding is hidden in the GeSHi code.
   * This constant is not expected to be accessed directly.
   */
  
  private static $classShortcuts = array(
      'kw'=>'KEYWORDS', 
      'co'=>'COMMENTS',
      'es'=>'ESCAPE_CHAR',
      'br'=>'BRACKETS',
      'st'=>'STRINGS',
      'nu'=>'NUMBERS',
      'me'=>'METHODS',
      'sy'=>'SYMBOLS',
      'sc'=>'SCRIPT',
      're'=>'REGEXPS' 
  ) ;
  
  /**
   * Return information for a language description field.
   * type LanguageDescriptionFieldInfo == map{
   *   'name' => String!,
   *   'kind' => FieldKind!,
   *   'type' => String  // see $languageDescriptionSchema for the format
   * }
   *   
   * type FieldKind == 'mapOfMap'|'map'|'scalar'
   * @return 
   */
  private static function getFieldSchema($fieldName) {
    if (isset(self::$languageDescriptionSchema[$fieldName])) {
      $r = array() ;
      $type=self::$languageDescriptionSchema[$fieldName] ;
      $nbfun = count(explode('=>',$type)) ;
      if ($nbfun===2) {
        $kind = "mapOfMap" ;
      } elseif ($nbfun===1) {
        $kind = "map" ;
      } else {
        $kind = "scalar" ;
      }
      $r['name'] = $fieldName ;
      $r['type'] = $type ;
      $r['kind'] = $kind ;
      return $r ;
    } else {
      die("GeSHiExtended.getFieldInfo: $fieldName is not a field") ;
    }  
  }
  
  /**
   * Return the schema of geshi files.
   * type LanguageDescriptionSchema == map+(String!,LanguageDescriptionFieldInfo)
   * @return LanguageDescriptionSchema
   */
  public static function getGeshiSchema() {
    $r=array() ;
    foreach(array_keys(self::$languageDescriptionSchema) as $field) {
      $r[$field] = self::getFieldSchema($field) ;
    }
    return $r ;
  }
  
  
  public static function getFullTokenClassName($classname) {
    $shortcut = substr($classname,0,2) ;
    if(isset(self::$classShortcuts[$shortcut])) {
      return self::$classShortcuts[$shortcut] ;
    } else {
      if ($shortcut==='de') {
       // this is a trick used by the tokens functions in SourceCode.
       // if a regular text is not in a span it returns 'de...'
       return 'OTHER' ;
      } else {
       return $classname ;
      }
    }
  }
  
  /*----------------------------------------------------------------------------------------
   *   Language descriptions
   *----------------------------------------------------------------------------------------
   * The elements above meant to deal with Geshi schema. Below the goal is to provides
   * function to deal with language description conforming to this schema and adding
   * additional information such as metrics.
   */
  
  /**
   * Return for a given language its description, that is the information in
   * the geshi language file plus a summary.
   * 
   * type FieldValueDescription == Map {
   *   'name'  : String!,       // The geshi language code
   *   'geshi' : NestedArray,   // The structure of this array conforming to LanguageDescriptionSchema
   *                            // that is, this is the content of the geshi language file
   *   'summary' : NestedArray? // A summary computed via the valueSummary() function. 
   *                            // This is an array. If nothing is interesting then this value is not defined. 
   * }
   * @param $language geshi language code
   * 
   * @return Map(String!,FieldValueDescription)! for each field its field value description.
   */
  public static function getLanguageDescription($language) {
    $geshi = new GeSHiExtended() ;
    $geshi->set_language($language,true) ;
    $languageData = $geshi->language_data ;
    $analysis=array() ;
    foreach (self::getGeshiSchema() as $field => $fieldInfo) {
      if (isset($languageData[$field])) {
        $analysis[$field]['name']=$field ;
        $analysis[$field]['geshi']=$languageData[$field] ;
        $summary= valueSummary($languageData[$field],"*empty*") ;
        if ($summary !== "*empty*" && is_array($summary)) {
          $analysis[$field]['summary']=$summary ;
        }
      }
    }
    return $analysis ;
  }
  
  /**
   * Return all language descriptions. Use var_dump to see the structure.
   * @return Map*(String,LanguageDescription!)
   */
  public static function getAllLanguageDescriptions() {
    $r = array() ;
    $geshi = new GeSHiExtended() ;
    $languages = $geshi->get_supported_languages() ;
    //echo count($languages). " languages descriptions founded" ;
    foreach($languages as $language) {
      // echo "Loading language $language..." ;
      $r[$language] = self::getLanguageDescription($language) ;
      // echo "done <br/>" ;
    }
    return $r ;
  }
  
  /**
   * Return a matrix that can be displayed via mapOfMapToHTMLTable
   * @return Map(LanguageCode,Map(LanguageProperty,Value))
   */
  public static function getLanguageProperties() {
    $allLanguageDescriptions=self::getAllLanguageDescriptions() ;
    $languageProperties=array() ;
    foreach($allLanguageDescriptions as $language => $languageDescription) {
      foreach($languageDescription as $field => $fieldValueDescription) {
        if (isset($fieldValueDescription['summary'])) {
          $arr = unnest_array($fieldValueDescription['summary'],' ') ;
          foreach($arr as $key => $value) {
            $languageProperties[$language][$field.' '.$key]=$value ;
          }
        } else {
          // this is an atomic value
          $languageProperties[$language][$field]=$fieldValueDescription['geshi'] ;
        }
      }
    }
    return $languageProperties ;
  }
  
  /**
   * Return a HTML matrix with one line for each language and one column for
   * each properties. The list of properties to be displayed can be specified.
   *  
   * @param false|true|RegExp|List*(String!*)? $propertySpec
   * If false there will be no header (no special first row) but all columns are included.
   * If true the first row is a header, and all columns are included.
   * If a string is provided then it is assumbed to be a regular expression. Only matching
   * column names will be added to the table.
   * If $displayFilter is a list, this list will constitute the list of columns headers.
   * Default is true.
   * 
   * @return HTMLTable The HTML of a table
   */
  public static function getLanguagePropertyHTMLMatrix($propertySpec=true) {
    return mapOfMapToHTMLTable(self::getLanguageProperties(),'',$propertySpec,true,null,$border="2") ;
  }
  
  
  /**
   * Return the language suggested by geshi for a given extension (without .)
   * or '' if nothing is suggested.
   */
  public static function getLanguageFromExtension($extension) {
    $g = self::getEmptyGeshiSingleton() ;
    return $g->get_language_name_from_extension($extension) ;
  }
  
  public function __construct() {
    parent::__construct() ;
  }
}


define('MAX_LINE_LENGTH',1000) ;
define('MAX_NB_OF_LINES',10000) ;

/**
 * Model a source code.
 * This class provides support for source code analysis, manipulation, display, etc.
 * It is based on the GeSHI libraries that allows both generation of html
 * code but also raw lexical analysis for many different languages thanks to the
 * geshi language definitions.
 * 
 * The class deals with 3 representations of a source code
 * - a plain textual version
 * - an html version generated by geshi
 * - an xml version of the html used for xpath queries 
 */
class SourceCode {
  

  /*-------------------------------------------------------------------------
   *   Fields
   * ------------------------------------------------------------------------
   */
  
  /**
   * @var String|false the last error generated by this class.
   * There is no need to change this state in case of a geshi error
   * (see getError).
   */
  protected $error = false ;
  
  
  /**
   * @var String! A unique code that is used in particular as a prefix of html ids
   * when various source code are to be displayed in the same page. This allow 
   * different css styling for different source code on the same page.
   * If this code is not explicitely provided then is is automatically generated.
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
   * @var String! the source code as a regular string
   */
  protected $plainSourceCode ;
  
  
  
  
  
  /**
   * @var String! language string used by the geshi package for highlighting
   */
  protected $geshiLanguageCode ;

  /**
   * @var GeSHI? the geshi object used for highlighting. Computed on demand.
   */
  protected $geshi = null;
  
  /**
   * @var HTML? the html version of the highlighted source code. Computed on demand.
   */
  protected $geshiHtmlSourceCode ;
  
  /**
   * @var SimpleXMLElement? The XML representation of htmlSourceCode source code, computed on demand. 
   */
  protected $geshiXMLSourcCode ;
  
  /**
   * Array of text node, that is a list of list of text node,
   * when the first level corresponds to source lines, and next level
   * to the line of text nodes. In fact, this structure helps in downgrading the
   * complexity of geshi which does not provide in fact a simple
   * list of tokens, but some nested span sometimes. Here only text nodes
   * are kept, along with the class in which they appear.
   * 
   * type TextNode == Map{
   *        'rawText' => String!,      // the raw value of text node
   *        'rawClass' => String!,     // the raw value of class embedding the text node
   *        'domNode' => DOMNode!     // Node of type XML_TEXT_NODE
   *   }
   * type TextNodeArray == List*(List*(TextNode!))  // one for each line in the source code
   * 
   * @Var TextNodeArray? The array of text nodes, computed on demand.
   */
  protected $textNodeArray = null ;
  
  
  /**
   * @var unknown_type
   */
  protected $inlineFragmentMap = array() ; 
    
  
  /*-------------------------------------------------------------------------
   *  Error 
   *-------------------------------------------------------------------------
   */
  
  /**
   * Returns an error message associated with the last GeSHi operation,
   * or false if no error has occured
   *
   * @return string|false An error message if there has been an error, else false
   * @since  1.0.0
   */
  public function error() {
    $geshiError = $this->getGeSHI()->error() ;
    if ($geshiError) {
      return $geshiError ;
    } else {
      return false ; $error ;
    }
  }
  
  /*-------------------------------------------------------------------------
   *   Plain textual view Views
   * ------------------------------------------------------------------------
   */
  
  
  /**
   * Return the plain source code as is.
   * @return String! The source code as a string.
   */
  public function getPlainSourceCode() {
    return $this->plainSourceCode ;
  }
  
  public function getLanguageCode() {
    return $this->geshiLanguageCode ;
  }
  
  /*-------------------------------------------------------------------------
   *   HTML Views
   * ------------------------------------------------------------------------
   */

  
  public function releaseMemory() {
    unset($this->geshi) ;
    unset($this->geshiHtmlSourceCode) ;
    unset($this->geshiXMLSourcCode) ;
    unset($this->textNodeArray) ;
  }
  
   
  
  public function getSourceId() {
    return $this->sourceId ;
  }

  
  /**
   * Get a geshi object for this source. This function is for internal use.
   * @return GeSHI! The geshi object associated with the source.
   */
  protected function getGeSHI() {
    if (!isset($this->geshi)) {
      
      // truncante long lines and limit the number of lines
      $text = "" ;
      $linesTruncated=array() ;
      $lines = explode("\n",$this->plainSourceCode) ;
      $nbOfLines = count($lines) ;
      // echo $nbOfLines ;
      $n = 0 ;
      while ($n < $nbOfLines && $n < MAX_NB_OF_LINES) {
        $line = $lines[$n];
        if (strlen($line)>MAX_LINE_LENGTH) {
          $msg =
            "line #".($n+1)." has been truncated to "
            .MAX_LINE_LENGTH." characters (out of ".strlen($line)." characters)\n" ;          
          $lines[$n] = substr($lines[$n],0,MAX_LINE_LENGTH)."... TRUNCATED" ;
          echo $msg ; 
        }
        $text .= $line."\n" ;
        $n++ ;
      }
      if (count($linesTruncated)) {
        $text = "WARNING: Some long lines have been truncated."
                 ."The file might not display correcly\n"
                 .$text ;
      }
      $text = implode("\n",$lines) ;
      if ($nbOfLines > MAX_NB_OF_LINES) {
        $msg = "\nFILE truncated to ".MAX_NB_OF_LINES." lines (out of ".$nbOfLines." lines)\n" ;
        $text .= $msg ;
        echo $msg ;
      }
      
      $geshi = new GeSHi() ;
      $geshi->set_source($text) ;
      $geshi->set_language($this->geshiLanguageCode) ; 
      $geshi->set_overall_id($this->sourceId) ;
      $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS) ;
      $geshi->enable_classes();
      $geshi->enable_ids(true);
      $this->geshi = $geshi ;
    }
    return $this->geshi ;
  }
  
  /**
   * Return the source code as a raw html string. 
   * (no line numbers, no higlighting, preformatted only).
   * @return HTML!
   */
  public function getRawHTML() {
    return htmlAsIs($this->source) ;
  }

  
  /**
   * An html version of the source code. This HTML code contains classes and ids
   * that could be used by the CSS. The HTML return here is not a document.
   * That is CSS should  be included before in the header of the document if it is to 
   * be displayed.
   * Otherwise it can be also be used as a HTML fragment and served as a service.
   * @return HTML!
   */
  public function getHTML() {
    if (!isset($this->geshiHtmlSourceCode)) {
      
      $geshi = $this->getGeSHI() ;
      $html = $geshi->parse_code();
      // for some reason we should remove the caracters below to avoid problem
      // when converting this html to xml.
      $this->geshiHtmlSourceCode = str_replace('&nbsp;',' ',$html) ;
    }
    return $this->geshiHtmlSourceCode ;
  }
  
  /*-------------------------------------------------------------------------
   *   CSS Styling
   * ------------------------------------------------------------------------
   */
  
  
  /**
   * Return the geshi standard CSS associated with the language.
   * @CSS! the CSS string 
   */
  public function getStandardCSS() {
    return $this->getGeSHI()->get_stylesheet();
  }
  
  /**
   * Return the CSS statements useful to emphasise a particular set of lines.
   * This CSS string could be used in conjuction with the standard CSS.
   * @param RangeString? $lines the set of lines to emphasize.
   * $lines is a string of the form '2-4,5,17-28'
   * (@see rangesExpression in Strings.php). Default to an empty string
   * in which case nothing is produced.
   * @param $style a list of CSS attribute assignement used to emphasize
   * the fragment.
   * @Return CSS! A CSS string
   */
  public function getFragmentCSS($lines='',$style='background:#FFAAEE;') {    
    $css='' ;
    $idprefix='#'.$this->sourceId.'-' ;
    $ids = $idprefix.implode(','.$idprefix,rangesExpression($lines)) ;
    if ($ids!='') {
      $css .= $ids. ' { '.$style.' }' ;
    }
    return $css ;
  }
    
  
  /**
   * Return the CSS that is necessary to style the html code with the
   * possibility the emphasize particular lines.
   * @param RangeString? if specified $lines represents the set of lines that
   * should be emphasized. This could be useful to show a code fragment.
   * $lines is a string of the form '2-4,5,17-28'
   * (@see rangesExpression in Strings.php). Default to an empty string,
   * that is by default no lines are emphasised.
   * @Return CSS! A CSS string that contains both regular geshi stylesheet
   * and
   */
  public function getCSS($lines='',$style='background:#FFAAEE;') {
    $css = $this->getStandardCSS() ;
    $css .= $this->getFragmentCSS($lines,$style) ;
    return $css ;
  }
  
  
  /**
   * Return a simple HTML header that include the CSS generated with getCSS.
   * This function could be used for simple HTML documents with one source.
   * @param RangeString? $lines
   * @param unknown_type $style
   * @return string
   */
  public function getHTMLHeader($lines='',$style='background:#FFAAEE;') {
    return '<html><head><title>Code</title><style type="text/css"><!--'
           .$this->getCSS($lines,$style)
           .'--></style></head>' ;
  }
    

  
  
  /*-------------------------------------------------------------------------
   *   XML view
   * ------------------------------------------------------------------------
   */
  
  
  /**
   * Return the source code as XML geshi representation.
   * This function is for internal usage. 
   * @return DOMDocument! The dom representation of the geshi representation
   */

  
  public function getXML() {
    if (!isset($this->geshiXMLSourceCode)) {
      $document=new DomDocument() ;
      if($document->loadHTML($this->getHTML())===false) {
        die('error: HMTL produced by GeSHi is not valid XML') ;
      } else {
        $this->geshiXMLSourceCode = $document ;
      }
    }
    return $this->geshiXMLSourceCode ;
  }
  
  
  /**
   * Returns elements of a given type and given html class
   * @param String $classname
   * @return List*(SimpleXMLElement) the list of nodes corresponding to the given class
   */

  public function getXMLElements($element='span',$classname="") {
      $doc = $this->getXML() ;
      $xpath = new DOMXPath($doc) ;
      if ($classname==="") {
        $xpathExpr = '' ;
      } else {
        $xpathExpr = '[@class="'.$classname.'"]' ;
      }
      return $xpath->query('//'.$element.$xpathExpr) ;
  }


  /*-------------------------------------------------------------------------
   *   Tokens view
   * ------------------------------------------------------------------------
   */
  
  /**
   * Return for a given node, the list of TextNode found at any level with 
   * corresponding decoration (@see TextNode).
   * This function is for internal use only, it deals with the fact that the line
   * is not a flat list of nodes, but instead a list of trees. 
   * @param DOMNode $node initially the node corresponding the line node. But since
   * the function it is recursive it will be called for arbitrary node.
   * @return List*(TextNode!)! the list of text nodes.
   */
  private function getTextNodes($node) {
    switch ($node->nodeType) {
      case XML_TEXT_NODE:
        // this is a regular text. No problem, we get it.
        $text  = $node->nodeValue ;
        $class = $node->parentNode->getAttributeNode('class')->nodeValue ;
        return 
          array(
            array(
              'rawText'=>$text,
              'rawClass'=>$class,
              'domNode'=>$node)) ;
        break ;
      case XML_ELEMENT_NODE:
        // this is a tree. Go into it and get tokens.
        $r = array() ;
        foreach($node->childNodes as $child) {
          $r = array_merge($r,$this->getTextNodes($child)) ;
        }
        return $r ;
        break ;
      default:
        // we don't care about other kind of nodes
    }
  }
  
  
  /**
   * Return the source code as a TextNodeArray.
   * @return TextNodeArray? 
   */
  public function getTextNodeArray() {
    if (!isset($this->textNodeArray)) {
      $nline=0 ;
      $this->textNodeArray=array();
      
      // for each lines
      foreach($this->getXMLElements('li','li1') as $lineNode) {
        $nline++ ;
        $this->textNodeArray[$nline] = array() ;
        
        // for each text node in this line 
        foreach($this->getTextNodes($lineNode) as $textNode) {
          $this->textNodeArray[$nline][]=$textNode ;    
        }
      }
    }
    return $this->textNodeArray ;
  }
  
  
  
  /**
   * Return the list of tokens corresponding to some criteria.
   * 
   * Token == Map{
   *   'text' => NonEmptyString,
   *   'class' => String,
   *   'line' => Integer >= 1,
   *   'i' => Integer >= 1    // index of the token on the line
   * }
   * 
   * @param RegExpr $classExclude the token whose class match this regexpr
   * will be ignored. Default to /co/ meaning that comments are ignored.
   * To ignore strings as well /co|st/ can be used. It might be also usefull
   * to remove escape char with /so|st|es/. In fact, the usage of the classes
   * depends on the language description.
   *
   * @param Boolean? $trimClassExclude indicates for which token class text should not
   * be trimmed. If '/-/' is specified then all token texts will be trimmed
   * as no classes are excluded. That is leading and trailing spaces will be
   * removed. If this results in an empty string, then the token will be removed. 
   * Default to /st|es/ that is all tokens that are not either a string or
   * a escaped character with be trimmed.
   *
   * @param RegExpr $shortClassName indicates if the class names should be
   * shorten to two letters. That is instead of having 'co1', 'co2', etc. the
   * class will be just 'co'. Note that the filters are still applied before
   * this truncation. Default is true.
   *
   * @return List*(Token)! The list of token selected.
   */
  
  public function getTokens($classExclude='/co/',$trimClassExclude='/st|es/',$shortClassName=true) {
    $tokens=array();
    $array=$this->getTextNodeArray() ;
    
    // for each lines
    foreach($array as $lineNo => $textNodeList) {
      // for each token in the line
      foreach($textNodeList as $textNodeNo => $textNode) {
        $token=array() ;
        // get the text
        if (!preg_match($trimClassExclude,$textNode['rawClass'])) {
          $token['text']=trim($textNode['rawText']) ;
        } else {
          $token['text']=$textNode['rawText'] ;
        }
        // get the class
        if ($shortClassName) {
          $token['class']=substr($textNode['rawClass'],0,2) ;
        } else {
          $token['class']=$textNode['rawClass'] ;
        }
        // get coordinates
        $token['line']=$lineNo ;
        $token['i']=$textNodeNo ;
        // add the token or node
        if ($token['text']!=='' && !preg_match($classExclude,$textNode['rawClass'])) {       
          $tokens[]=$token ;
        }
      }
    }
    return $tokens ;
  }
 
  /**
   * Generate a string representing a given token
   * @param Token! $token the token
   * @param String! $tokenPartSeparator if '' only the text of the token will be returned.
   * Otherwise each token attribute will be separated with this separator.
   * @return String! the string corresponding to the token
   */
  public static function tokenToString($token,$tokenPartSeparator=':') {
    if ($tokenPartSeparator==='') {
      return $token['text'] ;
    } else {
      return implode($tokenPartSeparator,array($token['text'],$token['class'],$token['line'],$token['i'])) ;
    }
  }
  
  /**
   * @param Tokens! $tokens the list of token
   * @param String! $tokenPartSeparator if '' only the text of the token will be returned.
   * Otherwise each token attribute will be separated with this separator.
   * @return String! the string corresponding to the token

   * @param unknown_type $tokens
   * @param unknown_type $tokenPartSeparator
   * @param unknown_type $tokenSeparator
   * @return string
   */
  public static function tokensToString($tokens,$tokenPartSeparator='',$tokenSeparator="\t") {
    $r='' ;
    foreach($tokens as $token) {
      $r[]=self::tokenToString($token,$tokenPartSeparator) ;
    }
    return implode($tokenSeparator,$r) ;
  }
  
  /*-------------------------------------------------------------------------
   *   Metrics
   * ------------------------------------------------------------------------
   */
      
  
  /**
   * Total number of lines (comments and blank lines included).
   * @return Integer > 0
   */
  public function getNLOC() {
    return count(explode("\n",$this->plainSourceCode)) ;
  }
  
  /**
   * Return a summary of the source analysis including token frequencies and lines number.
   * If a list of tokens is provided than the summary is based on this list, otherwise
   * compute the summary on the default list of tokens.
   * 
   * type ClassFrequencies == Map{
   *     'sum'      => Integer>=1,  // the total number of token occurences
   *     'distinct' => Integer>=1,  // the number of different tokens
   *     'min'      => Integer>=1,  // the minimum of token occurences
   *     'max'      => Integer>=1,
   *     'tokens'   => Map(String!,Integer>=1)  // the frequency of each token
   *   }
   *   
   * type SourceCodeSummary == Map{
   *     'sourceId' => String!,          // an id used to distinguish different source code in a same page.
   *     'languageCode' => String!,      // the geshi language code
   *     'frequencies' =>                // for each token class the corresponding frequencies of tokens
   *       Map(String!,ClassFrequencies), 
   *     'nloc' => Integer >= 0          // number of line of code 
   *     'ncloc' => Integer >= 0         // nb of lines that contains at least a non comment token.
   * @param unknown_type $tokens
   * @return multitype:number Ambigous <multitype:, mixed> 
   */
  public function getSummary($tokens=null) {
    if ($tokens===null) {
      $tokens=$this->getTokens() ;
    }
    $frequencies=array() ;
    $nonCommentedLines[]=array() ;
    foreach($tokens as $token) {
      $class=$token['class'] ;
      $text=$token['text'] ;
      if (isset($frequencies[$class]['sum'])) {
        $frequencies[$class]['sum']++ ;
        if (isset($frequencies[$class]['tokens'][$text])) {
          $frequencies[$class]['tokens'][$text]++ ; 
        } else {
          $frequencies[$class]['tokens'][$text]=1;
        }
      } else {
        $frequencies[$class]['sum']=1 ;
        $frequencies[$class]['tokens'][$text]=1 ;
      }
      if (!startsWith($class,'co')) {
        $nonCommentedLines[$token['line']]=true;
      }
    }
    foreach($frequencies as $class=>$map) {
      $frequencies[$class]['distinct']=count($frequencies[$class]['tokens']) ;
      $frequencies[$class]['min']=min($frequencies[$class]['tokens']) ;
      $frequencies[$class]['max']=max($frequencies[$class]['tokens']) ;
    }
    return array(
      'sourceId'=>$this->getSourceId(),
      'language'=>$this->getLanguageCode(),
      'frequencies'=>$frequencies,
      'size'=>strlen($this->plainSourceCode),
      'ncloc'=>count($nonCommentedLines),
      'nloc'=>$this->getNLOC() ) ;
  }
  
  /**
   * Return a simplified and flatten version of the summary.
   * Token frequencies have been removed 
   * @return Map(String!,String|Integer)!
   */
  public static function simplifiedSummary($summary) {
    $r = $summary ;
    $frequencies=$summary['frequencies'] ;
    unset($r['frequencies']) ;
    foreach ($frequencies as $classshortcut => $map) {
      $classfullname = GeSHiExtended::getFullTokenClassName($classshortcut)  ;
      foreach ($map as $key => $value) {
        if (!is_array($value)) {
          $r[$classfullname.' '.$key]=$value ;
        }
      }
    }
    return $r ;
  }
  
  /**
   * Return both the summary and the list of tokens as a Json string.
   * @see getSummary and getTokens for the documentation
   * @return Json!
   */
  
  public function getTokensAndSummaryAsJson(
      $classExclude='/co/',
      $trimClassExclude='/st|es/',
      $shortClassName=true)  {
    $tokens = $this->getTokens($classExclude,$trimClassExclude,$shortClassName) ;
    $summary = $this->getSummary($tokens) ;
    $summary['tokens']=$tokens ;
    return json_encode($summary) ; 
  }
   
  
  /**
   * Create a source code.
   * 
   * @param String! $text The text representing the source code
   * 
   * @param String! $language The geshiLanguageCode in which this source code is written
   * 
   * @param String? $sourceid an optional sourceId that will be used to differentiate this source 
   * from other sources if in the same html page. This will be used as a CSS class name and prefix
   * for CSS id, so it should be short. If nothing is provided an identifier will be automatically
   * generated. This is probably the best.
   */
  public function __construct($text,$language,$sourceid=null) {
    $this->plainSourceCode = $text ;
    $this->geshiLanguageCode = $language ;
    if (isset($sourceid)) {
      $this->sourceId = $sourceid ;
    } else {
      $this->sourceId = SourceCode::getNewSourceId() ;
    } 
  }
  
}





/**
 * Interface that both SourceFile and NonSourceFile implements. Such file can
 * be declared to be relative with a SourceDirectory in which case the filename
 * can be relative to the base of the SourceDirectory.
 *
 */
interface SomeFile {
  /**
   * Return the source directory if the source file have been declared within such
   * directory. Otherwise return null.
   * @return SourceDirectory? null or the source directory object.
   */
  public function getSourceDirectory() ;
  
  /**
   * Return the short filename of the file. It is relative to the source directory
   * if provided. Otherwise this is the same value as full filename.
   * @return Filename! the filename.
   */
  public function getShortFilename() ;
      
  /**
   * Return the full filename of the file. If the this source file pertains to
   * a source directory then this is the base of the directory + the short file name.
   * Otherwise it is the same as the short filename.
   */
  public function getFullFilename() ;
  
  public function getGenerationResults() ;
  
  
  /**
   * Gompute where the derived files should go given the 'base' argument.
   * See the explaintion in the generate function descrbed below. 
   * This offers various level of overloading but this is a bit tricky.
   * @param DirectoryName
   * @param Boolean|String|null $base
   */
  public function getOutputFileName($outputBase,$base) ;
    
  /**
   * Generate derived files associated with this file
   * 
   * @param DirectoryName! $outputBase the directory where the derived files should go.
   * In principle this directory is outside the base for the source.
   * 
   * @param Map*(String!,RangeString!)? $fragmentSpecs a map that give for
   * each fragment id (an arbitrary string that will appear in a filename), the
   * range of lines considered. If null then this parameter is ignored.
   *
   * @param Boolean|String|null $base @see parameter $base of rebasePath.
   * Here if nothing is specified, the base will take the value true if the
   * file is not inside a source directory, and false if it is. That is
   * by default the basename will be used if the file is not in a source directory
   * (so all files will be generated at the same level, leading to a flat structure),
   * otherwise the short name will be used, meaning that the output will be
   * isomorphic to the source directory.
   * 
   * @return multitype:
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) ;  
  
}



/**
 * A source file. This class extends SourceCode but additionally a source file has a filename,
 * possibly relative to a specific SourceDirectory. It also has methods to generate various
 * files from the source code in a output directory.
 */
class SourceFile extends SourceCode implements SomeFile {
  /**
   * @var SourceDirectory? If set then this source file pertains to 
   * a given source directory. It this case the relative filename
   * will be relative to the base of the source directory.
   */
  private $sourceDirectory ;
  
  /**
   * @var Filename! The name of the source file. 
   * If the source directory is specified then this filename is relative to the
   * base of the source directory. Otherwise it is a regular filename.
   */
  private $filename ;
  
  /**
   * @var Map(Filename,Integer|String)? file generated and corresponding results 
   */
  private $generationResults ;
  
  
  /**
   * @see SomeFile interface
   */
  public function getSourceDirectory() {
    return $this->sourceDirectory ;
  }
  
  /**
   * @see SomeFile interface
   */
  public function getShortFilename() {
    return $this->filename ;
  }
  
  /**
   * @see SomeFile interface
   */
  public function getFullFilename() {
    if ($this->getSourceDirectory()!==null) {
      return addToPath($this->getSourceDirectory()->getBase(),$this->getShortFilename()) ;
    } else {
      return $this->getShortFilename() ;
    }
  }
  
  
  /**
   * Overload the method to add filename as an additional field.
   */
  
  public function getSummary($tokens=null) {
    $summary = parent::getSummary($tokens) ;
    $summary['filename']=$this->getShortFilename() ;
    if ($this->getSourceDirectory()===null) {
      $summary['fullFilename']=$this->getFullFilename() ;
    }
    return $summary ;
  }
  
  public function getGenerationResults() {
    return $this->generationResults ;
  }

  /**
   * Gompute where the derived files should go given the 'base' argument.
   * See the explaintion in the generate function descrbed below.
   * This offers various level of overloading but this is a bit tricky.
   * @param 
   * @param Boolean|String|null $base
   */
  public function getOutputFileName($outputBase,$base) {
    $base = isset($base)
               ? $base
               : ($this->getSourceDirectory()===null) ; // trick here.
    return rebasePath($this->getShortFilename(),$outputBase,$base) ;
  }
  
  
  
  /**
   * Generate derived files associated with this source file
   * @see interface SomeSource for documentation. 
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) {
    $outputfilename = $this->getOutputFileName($outputBase,$base) ;
        
    $htmlBody = $this->getHTML() ;
    
    $generated=array() ;
    //----- generate html ------------------------------------------------------
    //-- generate the main html file with no fragment emphasis
    $simpleHeader = $this->getHTMLHeader() ;
    saveFile(
        $outputfilename.'.html',
        $simpleHeader.$htmlBody,
        $generated) ;
  
    //-- generate one html file per fragment
    if (isset($fragmentSpecs)) {
      // generate a file for each fragmentSpec
      foreach($fragmentSpecs as $fragmentName => $fragmentSpec) {
        $header = $this->getHTMLHeader($fragmentSpec,'background:#ffffaa ;') ;
        saveFile(
            $outputfilename.'__'.$fragmentName.'.html',
            $header.$htmlBody,
            $generated) ;
      }
    }
  
    //-- generate the json summary
    saveFile(
        $outputfilename.'.summary.json',
        $this->getTokensAndSummaryAsJson(), 
        $generated) ;
  
    //-- generate the raw text file
    saveFile(
        $outputfilename.'.txt',
        $this->getPlainSourceCode(), 
        $generated) ;
    
    $this->generationResults = array_merge($this->generationResults,$generated) ;
    
    
    //-- we have finished with generation for this file so release the resource
    // to avoid out of memory errors.
    $this->releaseMemory() ;
    
    return $generated ;
  }
  
  public function computeLanguage($filename) {
    $language = GeSHiExtended::getLanguageFromExtension(fileExtension($filename)) ;
    if ($language==='') {
      $language='text' ;
    }
    return $language ;
  }
  
  /**
   * Create a SourceFile potentially within a source directory.
   * @param Filename! $filename The filename of the source
   * @param SourceDirectory! $sourcedirectory 
   * @param String? $language Geshi language code. If not provided then
   * the language code will be computed via the compute language method. 
   * If this fail then 'text' will be taken as default. That is we assume
   * that this is a source file otherwise this object should not be
   * created on the first place.
   * @param String? $sourceid see the constructor of SourceCode.
   * @return after calling this constructor, $this->error() should be called.
   * If it returns false everything is fine. Otherwise it is an error message.
   */
  public function __construct($filename,SourceDirectory $sourcedirectory=null,$language=null,$sourceid=null) {
    $this->filename = $filename ;
    $this->generationResults = array() ;
    $this->sourceDirectory = $sourcedirectory ;
    if (!isset($language)) {
      $language = $this->computeLanguage($filename) ;
    }
    $fullfilename = $this->getFullFilename() ;
    $text = file_get_contents($fullfilename) ;
    if ($text===false) {
      $this->error = 'cannot read file '.$fullfilename ;
      echo $this->error ;
    }
    parent::__construct($text,$language,$sourceid) ;
  }
  
}



/**
 * Non source file.
 * Currently this class do not do anything usefull. It is just used for by SourceDirectory
 * in the case where non source file are found. 
 */
class NonSourceFile implements SomeFile {
  /**
   * @var SourceDirectory? If set then this source file pertains to
   * a given source directory. It this case the relative filename
   * will be relative to the base of the source directory.
   */
  private $sourceDirectory ;
  
  /**
   * @var Filename! The name of the source file.
   * If the source directory is specified then this filename is relative to the
   * base of the source directory. Otherwise it is a regular filename.
   */
  private $filename ;
  
  /**
   * @var Map(Filename,Integer|String)? file generated and corresponding results
   */
  private $generationResults ;
  
  
  /**
   * @see SomeFile interface
   */
  public function getSourceDirectory() {
    return $this->sourceDirectory ;
  }
  
  /**
   * @see SomeFile interface
   */
  public function getShortFilename() {
    return $this->filename ;
  }
  
  /**
   * @see SomeFile interface
   */
  public function getFullFilename() {
    if ($this->getSourceDirectory()!==null) {
      return addToPath($this->getSourceDirectory()->getBase(),$this->getShortFilename()) ;
    } else {
      return $this->getShortFilename() ;
    }
  }
    
  
  public function getGenerationResults() {
    return $this->generationResults ;
  }
  
  /**
   * Compute where the derived files should go given the 'base' argument.
   * See the explaintion in the generate function descrbed below.
   * This offers various level of overloading but this is a bit tricky.
   * @param 
   * @param Boolean|String|null $base
   */
  public function getOutputFileName($outputBase,$base) {
    $base = isset($base)
              ? $base
              : ($this->getSourceDirectory()===null) ; // trick here.
    return rebasePath($this->getShortFilename(),$outputBase,$base) ;
  }
  
  public function getSummary() {
    $s = array(
           "filename" => $this->getShortFilename(),
           "language" => "",
           "size" => filesize($this->getFullFilename()) 
         ) ;
    return $s ;
  } 
  
  public function getSummaryAsJson() {
    return json_encode($this->getSummary()) ;
  }
  
  /**
   * Generate derived files associated with this source file
   * @see interface SomeSource for documentation.
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) {
    $outputfilename = $this->getOutputFileName($outputBase,$base) ;
    //-- generate the json summary
    $generated = array() ;
    saveFile(
        $outputfilename.'.html',
        "<html>Sorry, the content of <b>".basename($this->getShortFilename())." cannot be rendered</html>",
        $generated) ;
    saveFile(
        $outputfilename.'.summary.json',
        $this->getSummaryAsJson(),
        $generated) ;
    $this->generationResults = array_merge($this->generationResults,$generated) ;
    return $generated ;
  }
  
  public function __construct($filename,SourceDirectory $sourceDirectory=null) {
    $this->filename = $filename ;
    $this->sourceDirectory = $sourceDirectory ;
    $this->generationResults = array() ;
  }
  
}





/**
 * SourceDirectory.
 * 
 */
abstract class SourceDirectory {  
  
  /**
   * @var Map*(String!,SomeFile!)? The map that given each
   * short file name (the basename), the corresponding SomeFile objects. 
   * These objects are either SourceFile or NonSourceFile.
   * Computed once but on demand.
   */
  protected $fileMap ;
  
  /**
   * @var List*(SourceSubDirectory!)? The map that given each
   * short directory name (the basename) yields the corresponding 
   * SubDirectory object.
   * Computed once but on demand.
   */
  protected $subDirectoryMap ;
  
  
  /**
   * @var DirectoryName! Relative path of the current directory (with respect to the base)
   */
  protected $directoryPath ;

  
  /**
   * All 'relative' pathname that are returned are relative to this base.
   * @return DirectoryName! The name of the base directory.
   */
  public abstract function getBase() ;
  
  
  /**
   * Direct Access to the top directory.
   */
  public abstract function getTopDirectory() ;
  
  /**
   * Directory path (relative to the base)
   * @return DirectoryPath!
   */
  public function getDirectoryPath() {
    return $this->directoryPath ;
  }
  
  /**
   * Full path name of this directory
   * @return DirectoryName!
   */
  public function getFullDirectoryName() {
    return addToPath($this->getBase(),$this->getDirectoryPath()) ;
  }
  
  
  /* ???*/
  public function getFullFileName($relativeFileName) {
    return addToPath($this->getBase(),$relativeFileName) ;
  }
  
  /**
   * Make the path relative to the base
   * @param Path! $path a path
   * @return Path!
   */
  public function getRelativePath($path) {
    return substr($path,strlen($this->getBase())) ;
  }

  /**
   * Return the default output base if any
   * @return DirectotyName?
   */
  public function getDefaultOutputBase() {
    return $this->getTopDirectory()->getDefaultOutputBase() ;
  }
  
  /**
   * This function will be improved. Currently it just check the extension
   * TODO should this function be here? This is weird. It could be static 
   * instead and in some file class. But may be we can use local information
   * stored in the current directory.
   * 
   * @param $fullFileName
   * @return String! a language code if it is a source code or "" otherwise.
   */
  public function getFileKind($fullFileName) {
    // get the language
    $language = GeSHiExtended::getLanguageFromExtension(fileExtension($fullFileName)) ;
//    if ($language==='javascript') {
//      $language='' ;
//    }
    return $language ;    
  }
  
  
  
  /**
   * Return the map of files (directly) contained in the directory.
   * @return Map*(String!SomeFile!)! The map basename => SomeFile objects.
   */
  public function getFileMap() {
    if (!isset($this->fileMap)) {
      $this->fileMap=array() ;
      $fullFileNames=        
        listFileNames(
            $this->getFullDirectoryName(),
            'file',   // only files
            null,         // No regular expression
            false,        // do not ignore dot files
            true,         // return file path not only basenames
            true) ;       // ignore dot directories
    
      foreach($fullFileNames as $fullFileName) {
        $relativeFileName = $this->getRelativePath($fullFileName) ;
        $language=$this->getFileKind($fullFileName) ;
        if ($language==="") {
          $someFile = new NonSourceFile($relativeFileName,$this) ;
        } else {
          $someFile = new SourceFile($relativeFileName,$this,$language) ;
        }
        $this->fileMap[basename($relativeFileName)] = $someFile ;
      }
    }
    return $this->fileMap ;
  }
  
  /**
   * Return the map of sub directories.
   * @var Map*(String!=>SubDirectory!)! 
   */
  public function getDirectoryMap() {
    if (!isset($this->subDirectoryMap)) {
      $this->subDirectoryMap=array() ;
      $fullDirNames=        
        listFileNames(
            $this->getFullDirectoryName(),
            'dir',        // only directory
            null,         // No regular expression
            false,        // do not ignore dot files
            true,         // return full path not only basenames
            true) ;       // ignore dot directories
    
      foreach($fullDirNames as $fullDirName) {
        $relativeDirName = $this->getRelativePath($fullDirName) ;
        $this->subDirectoryMap[basename($relativeDirName)] =
          new SourceSubDirectory($relativeDirName,$this) ;
      }
    }
    return $this->subDirectoryMap ;
  }
  

  
  /*-------------------------------------------------------------------------------
   *   Summary of the directory
   *-------------------------------------------------------------------------------
   */
  
  public function getSummary() {
    $summary = array() ;
   
    // Summarize information about files directly in this directory
    $fileMap = $this->getFileMap() ;
    $summary["nbOfFiles"] = count($fileMap) ;
    $summary["files"] = array() ;
    $languageDistribution=array() ;
    foreach($fileMap as $fileShortName => $file) {
      $fileSummary = $file->getSummary() ;
      $language=$fileSummary['language'] ;
      $filename=$fileSummary['filename'] ;
      $summary['files'][$fileShortName]=array() ;
      $summary['files'][$fileShortName]['filename']=$filename ;
      $summary['files'][$fileShortName]['language']=$language ;      
      
      // initialize integer fields to 0 if necessary
      if (!isset($languageDistribution[$language])) {
        $languageDistribution[$language]['nbFiles']=0 ;
        foreach($fileSummary as $key => $value) {
          if (is_numeric($value)) {
            $languageDistribution[$language][$key]=0;
          }
        }
      }
      $languageDistribution[$language]['files'][$fileShortName]['filename']=$filename;
      // add integer fields.
      $languageDistribution[$language]['nbFiles']++ ;
      foreach($fileSummary as $key => $value) {
        if (is_numeric($value)) {
          $languageDistribution[$language][$key] += $value;
        }
      }
    }
    $summary['languages']=$languageDistribution ;
  
  
    // Summarize informùation about direct subdirectories
    $dirMap = $this->getDirectoryMap() ;
    
    $summary["nbOfSubDirectories"] = count($dirMap) ;
    $summary["subDirectories"] = array() ;
    foreach($dirMap as $dirShortName => $dir) {
      $summary['subDirectories'][$dirShortName] = array() ;
      $summary['subDirectories'][$dirShortName]['name'] = $dirShortName ;     
    }
    return $summary ;
  }
  
  
  public function getSummaryAsJson() {
    return json_encode($this->getSummary()) ;
  }
  
  
  
  
  /*-------------------------------------------------------------------------------
   *   HTML Representation of this directory
   *-------------------------------------------------------------------------------
   */  
    
  public function getHTML_Path($outputBase) {
    $html = '<div class="dirPath">' ;
    $ancestors=$this->getAncestors() ;
    $nbAncestors=count($ancestors) ;
    for ($i = 0; $i<$nbAncestors; $i++) {
      $html .= '<span class="dirName">' ;
      $basename = basename($ancestors[$i]->getDirectoryPath()) ;
      $relativePath = str_repeat('../', $nbAncestors-$i) ;
      $html .= '<a href="'.$relativePath.'">'.$basename.'</a>' ;
      $html .= '</span> > ';
    }
   
    $html .= '<span class="dirName currentDirName">' ;
    $html .= '<b>'.basename($this->getDirectoryPath()).'</b>' ;
    $html .= '</span>  ' ;
    $html .= '</div>' ;
    return $html ;
  }
  
  public function getHTML_DirectorySummary($outputBase) {
    $html = '<div class="dirSummary">' ;
    $html .= '<a href="index.summary.json">summary</a>' ;
    $html .= '</div>' ;  
    return $html ;  
  }
  
  public function getHTML_Listing($outputBase) {
    // add the listing box
    $html = '<div class="dirListing"><table border=1>' ;
    
    foreach($this->getDirectoryMap() as $shortdirname => $sourceDirectory) {
      $html .= '<tr class="dirItem">' ;
      $html .= '<td>DIR</td>' ;
      $html .= '<td><a href="'.$shortdirname.'/index.html"><b>'.$shortdirname.'</b></a></td>' ;
      $html .= '<td><a href="'.$shortdirname.'/index.summary.json">summary</a></td>' ;
      $html .= '<td></td>' ;
      $html .= '</tr>' ;
    }
    
    foreach($this->getFileMap() as $shortfilename => $someFile) {
      $html .= '<tr class="fileItem">' ;
      if ($someFile instanceof SourceFile) {
        $html .= '<td>SOURCE</td>' ;
      } else {
        $html .= '<td>NON SOURCE</td>' ;
      }
      $html .= '<td><a href="'.$shortfilename.'.html">'.$shortfilename.'</a></td>' ;
      $html .= '<td><a href="'.$shortfilename.'.summary.json">summary</a></td>' ;
      if ($someFile instanceof SourceFile) {
        $html .= '<td><a href="'.$shortfilename.'.txt">txt</a></td>';     
      } else {
        $html .= '<td></td>' ;
      }
      $html .= '</tr>' ;
    }
    
    $html .= '</table></div>' ;
    return $html ;
  }
  
  
  public function getHTML($outputBase) {
    $html = '<div class="dirBox">' ;
    
    $html .= $this->getHTML_Path($outputBase) ;    
    $html .= $this->getHTML_DirectorySummary($outputBase) ;
    $html .= $this->getHTML_Listing($outputBase) ;
    return $html ;
  }
  
  
  
  
  
  /*-------------------------------------------------------------------------------
   *   Generation for this directory
   *-------------------------------------------------------------------------------
   */
  

  /**
   * Generate elements for all files in this source directory
   * @param DirectoryName $outputDirectory
   */
  public function generate($outputBase=null) {    
    // compute the output base
    if (!isset($outputBase)) {
      $outputBase = $this->getDefaultOutputBase() ;
      if ($outputBase===null) {
        die('SourceDirectory: not output base specified') ;
      }
    }
    $outputDirectory=addToPath($outputBase,$this->getDirectoryPath()) ;
    $outputDirectoryRootFileName=addToPath($outputDirectory,'index') ;
        
    // for each file generate corresponding derived files
    foreach($this->getFileMap() as $shortfilename => $someFile) {
      echo "File:    ".$shortfilename."\n" ;
      $someFile->generate($outputBase) ;
    }
    
    // for each directory generate what should be generated
    foreach($this->getDirectoryMap() as $shortdirname => $sourceDirectory) {
      echo "Dir:     ".$shortdirname."\n" ;
      $sourceDirectory->generate($outputBase) ;
    }
    saveFile($outputDirectoryRootFileName.'.summary.json',$this->getSummaryAsJson()) ;
    saveFile($outputDirectoryRootFileName.'.html',$this->getHTML($outputBase)) ;
    
  }
  
  /**
   * @param DirectoryName! $directoryPath the directory path relative to the base.
   */
  public function __construct($directoryPath) {
    $this->directoryPath = $directoryPath ;
  }
  
}




/**
 * SourceSubDirectory.
 */
class SourceSubDirectory extends SourceDirectory {
  /**
   * @var SourceDirectory! Always defined as we are in a subdirectory.
   */
  protected $parentDirectory ;
  /**
   * @var SourceTopDirectory! Always defined as we are in a subdirectory.
   */
  protected $topDirectory ;
  
  /**
   * Parent directory. That is either a SourceSubDirectory or SourceTopDirectory.
   * @return SourceDirectory!
   */
  public function getParentDirectory() {
    return $this->parentDirectory ;
  }
  
  /**
   * Top directory.
   * @return SourceTopDirectory! The top directory.
   */
  public function getTopDirectory() {
    return $this->topDirectory ;
  }
  
  
  public function getBase() {
    return $this->getTopDirectory()->getBase() ;
  }
  
  /**
   * List of ancestor source directories, the toplevel directory being first,
   * the current directory being excluded.
   * @return List*(SourceDirectory!) List of ancestors.
   */
  public function getAncestors() {
    $ancestors = $this->getParentDirectory()->getAncestors() ;
    $ancestors[] = $this->getParentDirectory() ;
    return $ancestors ;
  }
  

  /**
   * @param DirectoryName! $directoryPath the directory path relative to the base.
   * @parem SourceDirectory! $parentDirectory A source directory (either top or sub)
   */
  public function __construct($directoryPath,$parentDirectory) {
    parent::__construct($directoryPath) ;
    $this->parentDirectory = $parentDirectory ;
    $this->topDirectory = $this->parentDirectory->getTopDirectory() ;    
  }

}




/**
 * Top level Source Directory.  By contrast to subdirectories we store
 * here various global information.
 * 
 */
class SourceTopDirectory extends SourceDirectory {

  /**
   * @var DirectoryName! The name of the base directory.
   */
  protected $base ;
  
  /**
   * @var DirectoryName? The default for the output base.
   */
  protected $defaultOutputBase ;
  
  /**
   * @var The results of the generation process.
   */
  protected $processingResults ;
  
  /**
   * @var Boolean! Indicates if the generation process should be traced
   * on the standard output.
   */
  protected $traceOnStdout ;
  
  
  /**
   * Top directory.
   * @return SourceTopDirectory! The top directory.
   */
  public function getTopDirectory() {
    return $this ;
  }
  
  public function getBase() {
    return $this->base;
  }
  
  /**
   * List of ancestor source directories. Returns empty here as this is the top level.
   * @return List*(SourceDirectory!) .
   */
  public function getAncestors() {
    return array() ;
  }
  
  
  /**
   * @param DirectoryName! $basedir a directory that will serve as the base of 
   * everything. That is all path will be relative to this base.
   * 
   * @param DirectoryName! $directoryPath the directory to consider for analysis. Its
   * value is relative to the base.
   * 
   * @param DirectoryName! $defaultOutputBase the default base directory for
   * output. It is not necessary to specify it if generation is not to be used
   * of if a base is specified in the generate method. Default to null.
   * 
   * @param $traceOnStdout? $traceOnStdout  as the generation can be quite
   * time consuming this parameter allow to trace the process via some output
   * on stdout. Default to true which means verbose mode. Otherwise nothing
   * is displayed.
   *
   */
  public function __construct($basedir,$directoryPath,$defaultOutputBase=null,$traceOnStdout=true) {
    parent::__construct($directoryPath) ;
    $this->base = $basedir ;
    $this->defaultOutputBase = $defaultOutputBase ;
    $this->traceOnStdout     = $traceOnStdout ;
    $this->processingResults = array() ;
  }
}




class FileSystemPatternMatcher {
  protected $rules ;
  
  public function getRules() {
    return $this->rules ;
  }
  
  public function getRulesNumber() {
    return count($this->getRules()) ; 
  }
  
  public function getRulesSummary($groups=array()) {
    $summary['rules']=$this->rules ;
    foreach($groups as $group) {
      $summary[$group] = groupedBy($group,$this->rules) ;
    }
    return $summary ;
  }
  
  public function getRulesAsJSON($beautify=true) {
    $json = jsonjson_encode($this->getRules()) ;
    if ($beautify) {
      $json = jsonBeautifier($json) ;
    }
    return $json ;
  }
  
  public function getPatternKeys() {
    return array('patternRestriction','patternType','pattern','patternLength') ; // ,'regexprKeys'
  }
  
  /**
   * Merge a list of results corresponding to a same match.
   * In case of conflicts, that is a multiple assignments to
   * a key value, then an array is returned for the value of
   * the corresponding key, and the conflicting key is added to
   * a key 'conflictKeys'.
   * @param List*(List*(String!)|String!)! $results
   * @return Map*(String!,Set*(String!)|String!)!  
   */
  public function mergeMatchResults($results) {
    if (count($results)===0) {
      return array() ;
    } elseif (count($results)===1) {
      // there is not multiple results, so nothing to merge
      return $results[0] ;
    }
    $mergedResult = array() ;
    $patternKeys = $this->getPatternKeys() ;
    foreach($results as $result) {
      foreach ($result as $resultKey => $resultValue) {

        // we do not care about patternKeys, i.e. regexpr
        // as they are certainly distincts
        if (!in_array($resultKey,$patternKeys)) {
          if (!isset($mergedResult[$resultKey])) {
            // the key is not already defined, so no problem
            $mergedResult[$resultKey] = $resultValue ;
          } else {
            // there is already a value for that key
            if ($mergedResult[$resultKey]===$resultValue) {
              // that the same value, excellent!
            } else {
              // we just found a conflict, something already there
              if (is_array($mergedResult[$resultKey])) {
                // this was a array of values
                if (in_array($resultValue,$mergedResult[$resultKey]) ) {
                  // no problem,the value is already registerd
                  // it was not found in the test above because an
                  // array has been compared with the value
                } else {
                  // ok, there was already a conflict on that key
                  // (otherwise the old value would not have been an array)
                  // No additional conflict declaration for that key
                  // Just add this new value 
                  $mergedResult[$resultKey][] = $resultValue ;
                }
              } else {
                // so we have two values, this is a conflict
                $mergedResult['conflictingKeys'][] = $resultKey ;
                // create an array with the old value and the new one.
  
                $mergedResult[$resultKey] = array($mergedResult[$resultKey],$resultValue) ;
              }
            }
          }
        }
      } // foreach
    } // foreach
    return $mergedResult ;
  }
  
  /**
   * Match a path against the rules.
   * @param 'directory'|'file'! $type
   * @param Pathname! $path
   * @param Boolean? $merge 
   * @return List*(Map*(String!,String!))! The list of matching rules
   */
  public function matchPath($type,$path,$merge=true) {
    $results = array() ;
    foreach ($this->rules as $rule) {
      if ($type===$rule['patternRestriction']) {
        if (matchPattern($rule['patternType'],$rule['pattern'],$path,$matches)) {
          $result = $rule ;
          $result['matchedString']=$matches[0] ;
          $result['patternLength']=strlen($rule['pattern']) ;
          $results[] = $result ;
        }
      }
    }
    return $results ;
  }
  
  /**
   * @param Pathname! $rootDirectory an existing directory name to be explored.
   * All files recursively this directory will be matched agains the rules.
   * The same for subdirectories.
   */
  
  public function matchFileSystem($rootDirectory,$groupSpecifications=array()) {
      $artefactType = "file" ;
      $files = listAllFileNames($rootDirectory,$artefactType) ;
      
      $filesNotMatched=array() ;
      $basenamesOfFilesNotMatched=array() ;
      $extensionsOfFilesNotMatched=array() ;
      
      foreach($files as $filename) {
        $shortfilename=basename($filename) ;
        $relativefilename=substr($filename,strlen($rootDirectory)) ;
        $matchResults = $this->matchPath($artefactType,$shortfilename) ;
        if (count($matchResults)===0) {
          
          $filesNotMatched[]=$relativefilename ;
          @ $basenamesOfFilesNotMatched[$shortfilename]['nb'] += 1 ;
          @ $basenamesOfFilesNotMatched[$shortfilename]['occurrences'] .= "<li>".$relativefilename."</li>" ;
        
          $extension=fileExtension($shortfilename) ;
          @ $extensionsOfFilesNotMatched[$extension]['nb'] += 1 ;
          @ $extensionsOfFilesNotMatched[$extension]['occurrences'] .= "<li>".$relativefilename."</li>" ;
      
        } else {
          $filesMatched[$relativefilename] = $this->mergeMatchResults($matchResults) ;
          if (count($matchResults)>=2) {
            $filesMatched[$relativefilename]['rulesMatched'] = $matchResults ;
          }
        }
      }
      $nbOfFiles = count($files) ;
      $nbOfFilesNotMatched = count($filesNotMatched) ;
      $nbOfFilesMatched = $nbOfFiles-count($filesNotMatched) ;
      $ratio = (($nbOfFilesMatched/$nbOfFiles)*100) ;
      $output =  
        array(
          'rootDirectory' => $rootDirectory,
          'nbOfFiles' => $nbOfFiles,
          'nbOfFilesMatched' => $nbOfFilesMatched,
          'nbOfFilesNotMatched' => $nbOfFilesNotMatched,  
          'matchRatio' => $ratio,
          'filesMatched' => $filesMatched,
          'filesNotMatched'=> $filesNotMatched,
          'extensionsOfFilesNotMatched' => $extensionsOfFilesNotMatched,
          'basenamesOfFilesNotMatched' => $basenamesOfFilesNotMatched
        ) ;
      if (isset($groupSpecifications) 
          && is_array($groupSpecifications) && count($groupSpecifications)>=1) {
        $output = array_merge($output,groupAndProject($groupSpecifications,$filesMatched)) ;
      }
      return $output ;
  }
    
  public function generate(
      $rootDirectory,
      $outputbase=null,
      $matchedFilesGroupSpecifications=array(),
      $rulesGroups
      ) {
    $html =  "<h2>Rules</h2>" ;
    $html .= '<b>'.count($this->rules).'</b> rules defined<br/>' ;
    $html .= mapOfMapToHTMLTable($this->rules,'',true,true,null,2) ;
    $output['rules.html'] = $html ;
    
    $rulesSummary = $this->getRulesSummary($rulesGroups) ;
    $output['rulesSummary.json'] = jsonBeautifier(json_encode($rulesSummary)) ;
    
    
    $r = $this->matchFileSystem($rootDirectory,$matchedFilesGroupSpecifications) ;
    $html = '' ;
    foreach ($r['filesMatched'] as $fileMatched => $matchingDescription) {
      $html .= "<hr/><b>".$fileMatched."</b><br/>" ;
      
      $mergedResult = $matchingDescription ;
      if (isset($mergedResult['conflictingKeys'])) {
        $keys = $mergedResult['conflictingKeys'] ;
        foreach ($keys as $key) {
          $html .= "<li><span style='background:red;'>conflict on key $key </span></li>" ;
          $mergedResult[$key] = "<li>".implode("<li>",$mergedResult[$key]) ;
        }
      }
      $html .= "Merged result" ;
      $html .= mapOfMapToHTMLTable(array($mergedResult),'',true,true,null,2) ;
      
      if (isset($matchingDescription['rulesMatched'])) {
        $html .= mapOfMapToHTMLTable($matchingDescription['rulesMatched']) ;
      }
    }
    $output['filesMatches.html'] = $html ;
    
    
    $html =  "<h3>Basenames of files not matched</h3>" ;
    $html .=  mapOfMapToHTMLTable($r['basenamesOfFilesNotMatched'],'',true,true,null,2) ;
    $html .= "<h3>Extensions of files not matched</h3>" ;
    $html .=  mapOfMapToHTMLTable($r['extensionsOfFilesNotMatched'],'',true,true,null,2) ;
    $output['filesNotMatched.html'] = $html ;
    
    $output['matchSummary.json'] = json_encode($r) ;
    
    if (is_string($outputbase)) {
      $index = "" ;
      $index .= '<b>'.count($this->rules).'</b> rules defined</br>' ;
      $index .= $r['nbOfFilesMatched']." files matched over ".$r['nbOfFiles']." files : ".$r["matchRatio"]."%<br/>" ;    
      foreach($output as $file => $content) {
        saveFile(addToPath($outputbase,$file),$content) ;
        $index .= '<li><a href="'.$file.'">'.$file.'</a></li>' ;
      }
      $output['index.html']=$index ;
      saveFile(addToPath($outputbase,'index.html'),$index) ;
    }
    return $r ;
  }
  
  public function __construct($rulesOrFile) {
    if (is_string($rulesOrFile) && endsWith($rulesOrFile,'.csv')) {
      // this is a csv file. Load it and convert it to rules
      $csv = new CSVFile() ;
      if (! $csv->load($rulesOrFile)) {
        die('Cannot read '.$rulesOrFile);
      }
      $this->rules = $csv->getListOfMaps() ;   
    } elseif (is_string($rulesOrFile) && endsWith($rulesOrFile,'.rules.json')) {
      $json = file_get_contents($rulesOrFile) ;
      if ($json === false) {
        die('Cannot read '.$rulesOrFile);
      }
      $this->rules = json_decode($json) ;
    } else {
      $this->rules = $rulesOrFile ;
    }
    if (!is_array($this->rules)) {
      die('incorrect set of rules') ;
    }
  }
}









