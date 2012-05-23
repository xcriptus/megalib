<?php

function cleaning($what_to_clean, $tidy_config='' ) {

  $config = array(
      'show-body-only' => false,
      'clean' => true,
      'char-encoding' => 'utf8',
      'add-xml-decl' => true,
      'add-xml-space' => true,
      'output-html' => false,
      'output-xml' => false,
      'output-xhtml' => true,
      'numeric-entities' => false,
      'ascii-chars' => false,
      'doctype' => 'strict',
      'bare' => true,
      'fix-uri' => true,
      'indent' => true,
      'indent-spaces' => 4,
      'tab-size' => 4,
      'wrap-attributes' => true,
      'wrap' => 0,
      'indent-attributes' => true,
      'join-classes' => false,
      'join-styles' => false,
      'enclose-block-text' => true,
      'fix-bad-comments' => true,
      'fix-backslash' => true,
      'replace-color' => false,
      'wrap-asp' => false,
      'wrap-jste' => false,
      'wrap-php' => false,
      'write-back' => true,
      'drop-proprietary-attributes' => false,
      'hide-comments' => false,
      'hide-endtags' => false,
      'literal-attributes' => false,
      'drop-empty-paras' => true,
      'enclose-text' => true,
      'quote-ampersand' => true,
      'quote-marks' => false,
      'quote-nbsp' => true,
      'vertical-space' => true,
      'wrap-script-literals' => false,
      'tidy-mark' => true,
      'merge-divs' => false,
      'repeated-attributes' => 'keep-last',
      'break-before-br' => true,
  );

  if( $tidy_config == '' ) {
    $tidy_config = &$config;
  }

  $tidy = new tidy();
  $out = $tidy->repairString($what_to_clean, $tidy_config, 'UTF8');
  unset($tidy);
  unset($tidy_config);
  return($out);
}

$url="http://en.wikipedia.org/wiki/C_Sharp_%28programming_language%29" ;
//$url='http://101companies.org/index.php/Language:CSharp' ;
$html=file_get_contents($url) ;
//$tidy = new tidy;
//$tidy->parseString($html);
//$tidy->cleanRepair();

//$htmlcleaned = cleaning($html) ;
$html = preg_replace("/.*<!-- start content -->/s",'<!--coucou-->',$html) ;
$html = preg_replace("/<!-- end of content -->.*/s",'<!--coucou-->',$html) ;

echo $html ;
exit (1) ;



$doc=simplexml_load_string($htmlcleaned); 
echo $doc ;
echo "<h1>Extracted</h1>" ;
$nodes = $doc->xpath("//div[@id='content']") ;
echo count($nodes) ;
foreach ($nodes as $node) {
  echo $node->asXml() ;
}
//echo $html ;
