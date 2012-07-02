<?php

require_once '../Colors.php' ;
// MODIFIED by Jean-Marie Favre

/* PTagCloud PHP class is Copyright (C) 2009 Jeannette Global Enterprises LLC.

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation files
(the "Software"), to deal in the Software without restriction,
including without limitation the rights to use, copy, modify, merge,
publish, distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT.  IN NO EVENT SHALL THE COPYRIGHT HOLDERS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/ 
	
class CoreTagCloud {
  protected $tagFrequency = array();
  protected $tagDescription = array() ;
  protected $tagUrl = array() ;
  
  
  protected $tagClass = array() ;
  
  // style
  protected $fontfamily="arial" ;
  protected $letterspacing="0px" ;
  protected $padding="0px" ;
  protected $width;  // width of the div
  
  // custom parameters
  protected $maxTagDisplayed;
  protected $genericURL; 
  protected $backgroundImage;
  protected $backgroundColor;
  protected $UTF8;
  
  protected $colorLow;
  protected $colorHigh;
  protected $colorGrades;
  protected $nbSteps ;

  
  	
  public function setGenericURL($url) {
    $this->genericURL = $url;
  }

  public function setUTF8($bUTF8) {
	$this->UTF8 = $bUTF8;
  }

  public function setWidth($width) {
    $this->width = $width ;
  }

  public function setBackgroundImage($backgroundImage) {
    $this->backgroundImage = $backgroundImage;
  }

  public function setBackgroundColor($backgroundColor) {
    $this->backgroundColor = $backgroundColor;
  }
	
  public function setTextColors($arColors) {
    $this->colorGrades = $arColors;
  }
	
  public function addTag($tag, $frequency=1, $class=null, $description=null, $url=null) {
    if (!is_string($tag)) {
      echo "introduction of this value" ;var_dump($tag) ;
    }
    @ $this->tagFrequency[$tag] += $frequency;
    if (isset($description)) {
      $this->tagDescription[$tag] = $description ;
    }
    if (isset($url)) {
      $this->tagUrl[$tag] = $url ;
    }
    if (isset($class)) {
      $this->tagClass[$tag] = $class ;
    }
  }
	 
  protected function gradeFrequency($frequency) {
	$grade = 0;
    if ($frequency >= 90)
    	$grade = 9;
    else if ($frequency >= 70)
    	$grade = 8;
    else if ($frequency >= 60)
    	$grade = 7;
    else if ($frequency >= 50)
    	$grade = 6;
    else if ($frequency >= 40)
    	$grade = 5;
    else if ($frequency >= 30)
    	$grade = 4;
    else if ($frequency >= 20)
    	$grade = 3;
    else if ($frequency >= 10)
    	$grade = 2;
    else if ($frequency >= 5)
    	$grade = 1;
     
    return $grade;
  }
	 
  protected function getTagURL($tag) {
    if (isset($this->tagUrl[$tag])) {
      return urlencode($this->tagUrl[$tag]) ;
    } else {
      return $this->genericURL.urlencode($tag) ;
    }
  }
  
  protected function htmlHeader() {
    $result =
      '<div id="id_tag_cloud" style="' 
      . (isset($this->width) ? ("width:". $this->width. ";") : "")
      . 'line-height:70%;"><div style="border-style:solid;border-width:1px;'
      . (isset($this->backgroundImage) ? ("background:url('". $this->backgroundImage ."');") : "")
      . 'border-color:#888;margin-top:20px;margin-bottom:10px;padding:5px 5px 20px 5px;'
      . 'background-color:'.$this->backgroundColor.';">';
    return $result ;
  }
  
  protected function htmlFooter() {
    return "</div></div>" ;
  }
  
  protected function tagToHtmlLink($tag,$grade) {
    $frequency = $this->tagFrequency[$tag] ;
    $title = $tag ." (".$frequency.")" ;
    if (isset($this->tagDescription[$tag])) {
      $title .= " -- ".$this->tagDescription[$tag] ;
    }
    $url = $this->getTagURL($tag) ;
    $color = $this->colorGrades[$grade] ;
    $fontsize = (0.6 + 0.2 * $grade).'em' ;
    return
      '<a '
    . ' href="'.$url.'"'
    . ' title="'.$title.'"'
    . ' style="color:'.$color.';'
    . ' text-decoration:none;">'
       . '<span style="color:'.$color.';'
             . 'padding:'.$this->padding.';'
             . 'font-family:'.$this->fontfamily.';'
             . 'letter-spacing:'.$this->letterspacing.';'
             . 'font-weight:900;'
             . 'font-size:'.$fontsize.'">'
           . $tag
        . '</span>'
    . "</a> " ;
  }
  
  /**
   * @param Boolean? $generateHtml indicates whether a structure or some HTML must be generated
   * @return string
   */
  public function cloud($generateHtml = true, $sortFun='ksort') {
    // if there is a limit in terms of tag to display then select only these tags
	arsort($this->tagFrequency);    
	$topTags = array_slice($this->tagFrequency, 0, $this->maxTagDisplayed);
	$sortFun($topTags) ; 
	
    $this->maxCount = max($this->tagFrequency);
    if ($generateHtml) { 
	  $result = $this->htmlHeader() ;
    } else {
      $result = array();
    }
      
    foreach ($topTags as $tag => $count) {
      $grade = $this->gradeFrequency(($count * 100) / $this->maxCount);
      if ($generateHtml) {
        $result .= $this->tagToHtmlLink($tag,$grade) ;
      } else {
        $result[$tag] = $grade;
      }
    }
    
    if ($generateHtml) {
      $result .=  $this->htmlFooter() ;
    }
    return $result;
  }
  
  public function __construct($maxTagDisplayed=null, $arSeedWords = false) {
    $this->maxTagDisplayed = $maxTagDisplayed ;
    $this->UTF8 = false;
    $this->backgroundColor = "#DDDDDD";
    $this->colorLow = 0x2C4B48 ;
    $this->colorHigh = 0xF20204 ;
    $this->nbSteps = 10 ; 
    $this->colorGrades = getColorGrades($this->colorLow,$this->colorHigh,$this->nbSteps) ;
    if ($arSeedWords !== false && is_array($arSeedWords)) {
      foreach ($arSeedWords as $key => $value) {
        $this->addTag($value);
      }
    }
  }
  
}



class TagCloud extends CoreTagCloud {
  
  /* word replace helper */
  public function str_replace_word($needle, $replacement, $haystack) {
    $pattern = "/\b$needle\b/i";
    $haystack = preg_replace($pattern, $replacement, $haystack);
    return $haystack;
  }
  
  public function keywords_extract($text) {
    $text = strtolower($text);
    $text = strip_tags($text);
  
    /*
     * Handle common words first because they have punctuation and we need to remove them
    * before removing punctuation.
    */
    $commonWords = "'tis,'twas,a,able,about,across,after,ain't,all,almost,also,am,among,an,and,any,are,aren't," .
        "as,at,be,because,been,but,by,can,can't,cannot,could,could've,couldn't,dear,did,didn't,do,does,doesn't," .
        "don't,either,else,ever,every,for,from,get,got,had,has,hasn't,have,he,he'd,he'll,he's,her,hers,him,his," .
        "how,how'd,how'll,how's,however,i,i'd,i'll,i'm,i've,if,in,into,is,isn't,it,it's,its,just,least,let,like," .
        "likely,may,me,might,might've,mightn't,most,must,must've,mustn't,my,neither,no,nor,not,o'clock,of,off," .
        "often,on,only,or,other,our,own,rather,said,say,says,shan't,she,she'd,she'll,she's,should,should've," .
        "shouldn't,since,so,some,than,that,that'll,that's,the,their,them,then,there,there's,these,they,they'd," .
        "they'll,they're,they've,this,tis,to,too,twas,us,wants,was,wasn't,we,we'd,we'll,we're,were,weren't,what," .
        "what'd,what's,when,when,when'd,when'll,when's,where,where'd,where'll,where's,which,while,who,who'd," .
        "who'll,who's,whom,why,why'd,why'll,why's,will,with,won't,would,would've,wouldn't,yet,you,you'd,you'll," .
        "you're,you've,your";
    $commonWords = strtolower($commonWords);
    $commonWords = explode(",", $commonWords);
    foreach($commonWords as $commonWord)
    {
      $text = $this->str_replace_word($commonWord, "", $text);
    }
  
    /* remove punctuation and newlines */
    /*
     * Changed to handle international characters
    */
    if ($this->UTF8)
      $text = preg_replace('/[^\p{L}0-9\s]|\n|\r/u',' ',$text);
    else
      $text = preg_replace('/[^a-zA-Z0-9\s]|\n|\r/',' ',$text);
  
    /* remove extra spaces created */
    $text = preg_replace('/ +/',' ',$text);
  
    $text = trim($text);
    $words = explode(" ", $text);
    foreach ($words as $value)
    {
      $temp = trim($value);
      if (is_numeric($temp))
        continue;
      $keywords[] = trim($temp);
    }
  
    return $keywords;
  }
  
  public function addTagsFromText($SeedText) {
    $words = $this->keywords_extract($SeedText);
    foreach ($words as $key => $value) {
      $this->addTag($value);
    }
  }
  
}
