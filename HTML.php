<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'Strings.php' ;
require_once 'Structures.php' ;


/**
 * Return a html string keeping the format of the existing string.
 * For convinence if the passed parameter is a boolean or the null value
 * then display the corresponding value.
 * @param String|Boolean|null $value
 * @return HTML!
 */
function htmlAsIs($value) {
  if (is_bool($value)) {
    $value = boolStr($value) ;
  } else if ($value ===null) {
    $value = "null" ;
  }
  return '<pre>'.htmlentities($value).'</pre>' ;
}
    
/*
 * Generate HTML output according to basic data structure. Useful to debug.
 */

/**
 * Return a list from a map where all pair in the map is on a line.
 * @param Map*(String!,String!)! $map
 * @param String? $separator Separator between the key and the value (' => ' by default).
 * @return HTML!
 */
function mapToHTMLList($map,$separator=' => ') {
  $r ='' ;
  if (count($map)!=0) {
    $r .= '<ul>' ;
    foreach($map as $att => $val) {
      $r .= '<li><b>'.$att.'</b>'.$separator.$val.'</li>' ;
    }
    $r .= '</ul>' ;
  }
  return $r ;
}

/**
 * 
 * @param List*(Map(String!,String!))! $arraymap
 * @return HTML!
 */
function arrayMapToHTMLList($arraymap) {
  $r ='' ;
  if (count($arraymap)!=0) {
    $r .= '<ul>' ;
    $i = 0 ;
    foreach ($arraymap as $map) {
      if (count($map)!=0) {
        $r .=  '<li>#'.$i++ ;
        $r .= mapToHTMLList($map) ;
        $r .=  '</li>' ;
      }
    }
    echo '</ul>' ;
  }
  return $r ;
}


/**
 * Transform map of map to into a two dimentsional array indexed by integers and
 * with optional column names and row names.
 * Each inside map becomes a row (with the key if $printKeys is selected).
 * Each key in a inside map leads to a colum. The first row is the table header
 * if $addRowKeys is selected.
 *
 * @param Map*(Scalar!,Map*(Scalar!,Any!)!)! $mapOfMap A map of map not necessarily
 * filled (homogeneous) and with arbitrary scalar keys.
 *
 * @param String? $filler an optional filler that will be used if a cell has no value.
 *
 * @param false|true|RegExp|List*(String!*)? $columnSpec
 * If false there will be no header (no special first row) but all columns are included.
 * If true the first row is a header, and all columns are included.
 * If a string is provided then it is assumbed to be a regular expression. Only matching
 * column names will be added to the table.
 * If $displayFilter is a list, this list will constitute the list of columns headers.
 * Default is true.
 *
 * @param Boolean? $rowSpec
 * If true then the first column will contains the key of rows.
 * Default to true.
 * 
 * @param FunctionName $cellRenderer the name of the function to render a cell value.
 * Takes the cell value as an argument. If null then the cell value is not transformed.
 * Default to 'htmlAsIs' so that all characters and break lines are rendered. null
 * should be passed explicitely if the cell contains HTML that should be interpreted.

 * @return List*(List*(Scalar!)) the resulting table.
 */

function mapOfMapToHTMLTable($mapOfMap,$filler='',$columnSpec=true,$rowSpec=true,$cellRenderer='htmlAsIs',$border="1") {
  if (count($mapOfMap) == 0) {
    return "<b>(empty)</b>" ;
  } else {
    $table=mapOfMapToTable($mapOfMap,$filler,$columnSpec,$rowSpec) ;// fill the map if necessary
    $html = '<table'.($border?" border=$border":"").'>' ;

    $irow = 0 ;
    // if the first row is a header, render it as an html table header
    if ($columnSpec!==false) {
      $html .= '<tr>' ;
      foreach ($table[$irow] as $cell) {
        $html .= '<th><b>'.(isset($cellRenderer)?$cellRenderer($cell):$cell).'</b></th>' ;
      }
      $html .= '</tr>' ;
      $irow++ ;
    }

    // output the table body
    $nbrows=count($table) ;
    for($i=$irow; $i<$nbrows; $i++) {
      $html .= '<tr>' ;
      foreach ($table[$i] as $cell) {
        $html .= '<td>'.(isset($cellRenderer)?$cellRenderer($cell):$cell).'</td>' ;
      }
      $html .= '</tr>' ;
    } 
    $html .= '</table>' ;
    return $html ;
  }
}






/* Example of form generation 
$options =
  array(
    "corpusKind" => array("Corpus","ENUM",
        "token|Tokenized Source File Directory",
        "sources|File Directory",
        "filenames|Filenames"),
    "directory" => array("Directory","STRING",DEFAULT_DIRECTORY),
    "buildIndexes" => array("Build indexes from corpus","BOOLEAN",0),
    "reload" => array("Reload indexes from file","BOOLEAN",1),
    "showGlobal" => array("Show global cloud for the corpus","BOOLEAN",0),
    "showTextCloud" => array("Show clouds for each text in the corpus","BOOLEAN",0),
    "showContainerTree" => array("Show the tree of containers of a following identifier","BOOLEAN",0)
) ;

echo HTMLFormFromOptions("THISPAGE.php",$options,$_GET) ;

*/

/**
 * Generate a form from a specification. Current values are used to fill the form. 
 * Default values in the specification are used otherwise.  
 * @param URL $url
 * @param OptionMap $options
 * @param Map(String!,String!) $currentValues
 * @return HTMLForm
 */
function HTMLFormFromOptions($url,$options,$currentValues) {
  $html = "<form name='parameters' action='$url' method='get'>\n" ;
  foreach($options as $name => $info) {
    $currentValue = (isset($currentValues[$name])?$currentValues[$name]:null) ;
    $htmlOption = HTMLFieldFromOption($name,$info,$currentValue) ;
    $html .=   "  <div>\n"
    . "    ".$htmlOption."\n"
    . "  </div>" ;
  }
  $html .= '<input type="submit" value="Submit">' ;
  $html .= "</form>" ;
  return $html ;
}

/**
 * Generate a field to be inserted within a form. 
 * This function is normally used only by HTMLFormFromOptions.
 * @param String! $name
 * @param OptionInfo! $info
 * @param Scalar? $currentValue
 * @return HTMLString
 */
function HTMLFieldFromOption($name,$info,$currentValue=null) {
  $titre=$info[0] ;
  $type=$info[1] ;
  $value=$currentValue ;
  switch ($type) {
    case "ENUM":
      $html = $titre."<select name='$name'>\n" ;
      for($i=2;$i<count($info);$i++) {
        $optionSpec=$info[$i] ;
        $pos=strpos($optionSpec,"|") ;
        if ($pos===false) {
          $value=$optionSpec ;
          $text=$optionSpec ;
        } else {
          $value=substr($optionSpec,0,$pos) ;
          $text=substr($optionSpec,$pos+1) ;
        }
        $ifSelected = ($currentValue===$value ? " selected" : "") ;
        $html .= "  <option value='$value' $ifSelected>".$text."</option>\n" ;
      }
      $html .= "</select>" ;
      break ;
    case "STRING":
      $defaultValue = $info[2] ;
      $value = ($currentValue===null?$defaultValue:$currentValue) ;
      $html = $titre . "<input type='text' name='$name' value='$value'>" ;
      break ;
    case "BOOLEAN":
      $defaultValue = $info[2] ;
      $value = ($currentValue===null?$defaultValue:$currentValue) ;
      $html = "<input type='checkbox' name='$name' value='1'"
      . ($value ? " checked" : "") . "> $titre" ;
      break ;
    default:
      die("InputFromOption: type of option not recognized for option $name : $type") ;
  }
  return $html ;
}

class Options {
  protected $optionMap ;
  /**
   * Generate a HTML form. Current values are used to fill the form. 
   * Default values in the specification are used otherwise.  
   * @param URL $url the body of the page to which the submit action will lead
   * @param Map(String!,String!) $currentValues
   * @return HTMLForm
   */  
   public function getHTML($url,$currentValues) {
    $html = "<form name='parameters' action='$url' method='get'>\n" ;
    foreach($this->optionMap as $name => $option) {
      $currentValue = (isset($currentValues[$name])?$currentValues[$name]:null) ;
      $htmlOption = $option->getHTML($currentValue) ;
      $html .=   "  <div>\n"
      . "    ".$htmlOption."\n"
      . "  </div>" ;
    }
    $html .= '<input type="submit" value="Submit">' ;
    $html .= "</form>" ;
    return $html ;
  }
  public function getValues($currentValues) {
    $values=array() ;
    foreach($this->optionMap as $name=>$option) {
      $currentValue = (isset($currentValues[$name])?$currentValues[$name]:null) ;
      $value = $option->getValue($currentValue) ;
      if ($value!=null) {
        $values[$name]=$value;
      }
    }
    return $values ;
  } 
  public function __construct($options) {
    $this->optionMap = array() ;
    foreach ($options as $name => $optionInfo ) {
      $this->optionMap[$name] = optionFactory($name,$optionInfo) ;
    }
  }
}

function optionFactory($name,$optionInfo) {
  $class = $optionInfo[1]."Option" ;
  $option = new $class($name,$optionInfo) ;
  return $option ;
}
abstract class Option {
  protected $name ;
  protected $type ;
  protected $titre ;
  protected $defaultValue ;
  public function getName() {
    return $this->name ;
  }
  public function getType() {
    return $this->type ;
  }
  public function getTitre() { 
    return $this->titre ;
  }
  public function getDefaultValue() {
    return $this->defaultValue ;
  }
  public function getValue($currentValue=null) {
    return (isset($currentValue) ? $currentValue : $this->getDefaultValue() )  ;
  }
  public abstract function getHTML($currentValue) ;
  protected function __construct($name,$titre,$type,$defaultValue) {
    $this->name = $name ;
    $this->type = $type ;
    $this->titre = $titre ;
    $this->defaultValue = $defaultValue ;
  }
}

class ENUMOption extends Option {
  protected /*List*({"value"=>String!,"text"=>String!})!*/$enumLiterals ;
  private function addLiteralPair($literalPair) {
    $pos=strpos($literalPair,"|") ;
    if ($pos===false) {
      $value=$literalPair ;
      $text=$literalPair ;
    } else {
      $value=substr($literalPair,0,$pos) ;
      $text=substr($literalPair,$pos+1) ;
    }
    $this->enumLiterals[] = 
      array("value"=>$value,
            "text"=>$text) ;
  } 
  protected function getDefaultLiteralValue() {
    return $this->enumLiterals[0]["value"] ;
  }
  public function getHTML($currentValue) {
    $titre = $this->getTitre() ;
    $name = $this->getName() ;
    $value = $this->getValue($currentValue) ;
    $html = $titre."<select name='$name'>\n" ;
    foreach ($this->enumLiterals as $enumLiteral) {
      $ifSelected = ($value===$enumLiteral["value"] ? " selected" : "") ;
      $html .= "  <option value='".$enumLiteral["value"]."'".$ifSelected.">".$enumLiteral["text"]."</option>\n" ;
    }
    $html .= "</select>" ;
    return $html ;
  }
  public function __construct($name,$infoOption) {
    $type = $infoOption[0] ;
    $titre = $infoOption[1] ;
    for($i=2;$i<count($infoOption);$i++) {
      $this->addLiteralPair($infoOption[$i]) ;
    }
    $defaultValue = $this->getDefaultLiteralValue() ;
    parent::__construct($name,$type,$titre,$defaultValue) ;
  }  
}

class STRINGOption extends Option {
  public function getHTML($currentValue) {
    $titre = $this->getTitre() ;
    $name = $this->getName() ;
    $value = $this->getValue($currentValue) ;
    return  $titre . "<input type='text' name='$name' value='$value'>" ;
  }
  public function __construct($name,$infoOption) {
    parent::__construct($name,$infoOption[0],$infoOption[1],$infoOption[2]) ;
  }
}

class BOOLEANOption extends Option {
  public function getHTML($currentValue) {
    $titre = $this->getTitre() ;
    $name = $this->getName() ;
    $value = $this->getValue($currentValue) ;
    return "<input type='checkbox' name='$name' value='1'"
           . ($value ? " checked" : "") . "> $titre" ;
  }
  public function __construct($name,$infoOption) {
    parent::__construct($name,$infoOption[0],$infoOption[1],$infoOption[2]) ;
  }
}


function GetValueFromOption($name,$info,$currentValue=null) {
  $type=$info[1] ;
  $value=$currentValue ;
  switch ($type) {
    case "ENUM":
      $html = $titre."<select name='$name'>\n" ;
      for($i=2;$i<count($info);$i++) {
        $optionSpec=$info[$i] ;
        $pos=strpos($optionSpec,"|") ;
        if ($pos===false) {
          $value=$optionSpec ;
          $text=$optionSpec ;
        } else {
          $value=substr($optionSpec,0,$pos) ;
          $text=substr($optionSpec,$pos+1) ;
        }
        $ifSelected = ($currentValue===$value ? " selected" : "") ;
        $html .= "  <option value='$value' $ifSelected>".$text."</option>\n" ;
      }
      $html .= "</select>" ;
      break ;
    case "STRING":
      $defaultValue = $info[2] ;
      $value = ($currentValue===null?$defaultValue:$currentValue) ;
      $html = $titre . "<input type='text' name='$name' value='$value'>" ;
      break ;
    case "BOOLEAN":
      $defaultValue = $info[2] ;
      $value = ($currentValue===null?$defaultValue:$currentValue) ;
      $html = "<input type='checkbox' name='$name' value='1'"
      . ($value ? " checked" : "") . "> $titre" ;
      break ;
    default:
      die("InputFromOption: type of option not recognized for option $name : $type") ;
  }
  return $html ;
}


/**
 * Generate a HTML link with an optional text and parameters
 * @param URL $rootUrl
 * @param String! $text
 * @param Map*(String!,Scalar!)! $params optional map of attribute values
 * @return string
 */
function HTMLLink($urlBody,$text="",$params=array()) {
  foreach($params as $variable=>$value) {
    $urlparams[] = urlencode($variable)."=".urlencode($value) ;
  }
  $urlParams = implode("&",$urlparams) ;
  $bodySeparator = (substr($urlBody,-1,1)==="?" ? "" : "?"  ) ;
  $url = $urlBody . $bodySeparator . $urlParams ;
  if ($text==="") {
    $text = $url ;
  } 
  return "<a href='".$url."'>".$text."</a>" ; 
}