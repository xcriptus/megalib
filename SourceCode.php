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
   * @var HTML? the html version of the highlighted source code. Computed on demand.
   */
  protected $geshiHtmlSourceCode ;
  
  /**
   * @var SimpleXMLElement? The XML representation of htmlSourceCode source code, computed on demand. 
   */
  protected $geshiXMLSourcCode ;
  
  /**
   * @var GeSHI? the geshi object used for highlighting. Computed on demand.
   */
  protected $geshi = null;
  
  /**
   * @var String|false the last error generated by this class.
   * There is no need to change this state in case of a geshi error
   * (see getError).
   */
  protected $error = false ;
  
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
  
  public function getSourceId() {
    return $this->sourceId ;
  }

  
  /**
   * Get a geshi object for this source. This function is for internal use.
   * @return GeSHI! The geshi object associated with the source.
   */
  protected function getGeSHI() {
    if (!isset($this->geshi)) {
      $geshi = new GeSHi() ;
      $geshi->set_source($this->plainSourceCode) ;
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
   * Return the full token list for a given line. For internal purpose only.
   * @param DOMNode $node the node corresponding the line
   * @return List*(Map{'text'=>String!,'class'=>String!})! All tokens.
   */
  protected function getLineTokens($node) {
    switch ($node->nodeType) {
      case XML_TEXT_NODE:
        $text = $node->nodeValue ;
        $class=$node->parentNode->getAttributeNode('class')->nodeValue ;
        return array(array('text'=>$text,'class'=>$class)) ;
        break ;
      case XML_ELEMENT_NODE:
        // $class = $node->getAttributeNode('class')->nodeValue ;
        // echo $class ;
        $r = array() ;
        foreach($node->childNodes as $child) {
          $r = array_merge($r,$this->getLineTokens($child)) ;
        }
        return $r ;
        break ;
      default:
    }
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
    $nline=0 ;
    $tokens=array();
    // for each lines 
    foreach($this->getXMLElements('li','li1') as $linenode) {
      $nline++ ;
      $ntoken=0 ;
      // for each token in the line
      foreach($this->getLineTokens($linenode) as $token) {
        if (!preg_match($trimClassExclude,$token['class'])) {
          $token['text']=trim($token['text']) ;
        }
        if ($token['text']!=='' && !preg_match($classExclude,$token['class'])) {       
          if ($shortClassName) {
            $token['class']=substr($token['class'],0,2) ;
          }
          $token['line']=$nline ;
          $token['i']=++$ntoken ;
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
   * Return a summary of the source analysis including token
   * frequencies and lines number.
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
  public function getSummary($tokens) {
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
      'languageCode'=>$this->getLanguageCode(),
      'frequencies'=>$frequencies,
      'size'=>count($this->plainSourceCode),
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
  
  public function getSummary($tokens) {
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
   * Generate files associated with this source file
   * @param DirectoryName! $outputBase the directory where the files should go.
   * This directory should not be within the source directory and its path
   * is therefore outside of it.
   * @param Map*(String!,RangeString!)? $fragmentSpecs a map that give for
   * each fragment id (an arbitrary string that will appear in a filename), the
   * range of lines considered. If null then this parameter is ignored.
   * @param Boolean|String|null $base @see parameter $base of rebasePath.
   * Here if nothing is specified, the base will take the value true if the
   * file is not inside a source directory, and false if it is. That is
   * by default the basename will be used if the file is not in a source directory
   * (so all files will be generated at the same level, leading to a flat structure),
   * otherwise the short name will be used, meaning that the output will be
   * isomorphic to the source directory.  
   * @return multitype:
   */
  public function generate($outputBase,$fragmentSpecs=null,$base=null) {
    $base = isset($base) 
              ? $base 
              : ($this->getSourceDirectory()===null) ;
    $htmlBody = $this->getHTML() ;
    $filename = $this->getShortFilename() ;
    $outputfilename = rebasePath($filename,$outputBase,$base) ;
    
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
    if ($this->getSourceDirectory()===null) {
      return addPath($this->getSourceDirectory()->getBase(),$this->getShortFilename()) ;
    } else {
      return $this->getShortFilename() ;
    }
  }
    
  
  public function getGenerationResults() {
    return $this->generationResults ;
  }
  
  public function __construct($filename,SourceDirectory $sourceDirectory=null) {
    $this->filename = $filename ;
    $this->sourceDirectory = $sourceDirectory ;
    $this->generationResults = array() ;
  }
  
}


/**
 * SourceDirectory
 */
class SourceDirectory {
  protected $baseDir ;
  protected $relativeDirectory ;
  protected $defaultOutputBase ;
  protected $processingResults ;
  protected $allRelativeFilenames ;
  protected $traceOnStdout ;

  /**
   * @return DirectoryName! The base of the source directory.
   */
  public function getBase() {
    return $this->baseDir ;
  }
  
  public function getFullDirectoryName() {
    return addToPath($this->getBase(),$this->relativeDirectory) ;
  }
  
  public function getFullFileName($relativeFileName) {
    return addToPath($this->getBase(),$relativeFileName) ;
  }
  
  /**
   * Make the path relative to the base
   * @param Path! $path a path
   * @return Path!
   */
  public function getRelativePath($path) {
    return substr($path,strlen($this->baseDir)) ;
  }

  /**
   * Return the default output base if any
   * @return DirectotyName?
   */
  public function getDefaultOutputBase() {
    return $this->defaultOutputBase ;
  }
  
  /**
   * Return all files recursively found in the directory.
   * Ignore dot files, files with no extensions and do not explore dot directories.
   * @return List*(Filename!)! list of filename relative to the base.
   */
  public function getAllRelativeFileNames() {
    if (!isset($this->allRelativeFilenames)) {
      $filenames = 
        listAllFileNames(
            $this->getFullDirectoryName(),
            'file',       // only files
            '/.+\..+/',   // that have an extension
            true,         // ignore dot files
            true,         // return file path not only basenames
            false,        // do not follow links
            true) ;       // ignore dot directories
      $this->allRelativeFilenames = array() ;
      foreach( $filenames as $filename) {
        $this->allRelativeFilenames[] = $this->getRelativePath($filename) ;
      }
    }
    return $this->allRelativeFilenames ;
  }
  
  protected function processSourceFile($relativeFilename,$language,$outputBase) {
    if ($this->traceOnStdout) {
      echo "SourceFile:    ".$relativeFilename." as $language\n" ;
    }
    $source = new SourceFile($relativeFilename,$this,$language) ;
    // currently process it with the assumption that there is no fragment for this file
    // TODO add support for fragments
    $source->generate($outputBase,null,false) ;
  }
  
  protected function processNonSourceFile($relativeFilename,$outputBase) {
    if ($this->traceOnStdout) {
      echo "NonSourceFile: ".$relativeFilename."n" ;
    }
  }
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
    
    // get the list of files to process
    $relativeFilenames = $this->getAllRelativeFileNames() ;
    foreach($relativeFilenames as $relativeFilename) {
      
      // get the language 
      $language = GeSHiExtended::getLanguageFromExtension(fileExtension($relativeFilename)) ;
      
      if ($language!=='') {
        $this->processSourceFile($relativeFilename,$language,$outputBase) ;
      } else {
        $this->processNonSourceFile($relativeFilename,$outputBase) ;
      }
    }
  }
  
  /**
   * @param DirectoryName! $basedir a directory that will serve as the base of 
   * everything. That is all path will be relative to this base.
   * 
   * @param DirectoryName! $dir the directory to consider for analysis. Its
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
  public function __construct($basedir,$relativeDirectory,$defaultOutputBase=null,$traceOnStdout=true) {
    $this->baseDir = $basedir ;
    $this->relativeDirectory = $relativeDirectory ;
    $this->defaultOutputBase = $defaultOutputBase ;
    $this->traceOnStdout     = $traceOnStdout ;
    $this->processingResults = array() ;
  }
  
}

