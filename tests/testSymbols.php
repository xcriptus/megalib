<?php
require_once 'main.config.local.php' ;

require_once '../Files.php' ;
require_once '../Symbols.php' ;
require_once '../libraries/Stemmer/Stemmer.php' ;
require_once '../libraries/TagCloud/TagCloud.php' ;


define('DIR','../../101repo/contributions') ;
define('EXTENSIONS','.java .hs .cs .rb .php') ;  // .java .hs .cs .rb .php
define('JSONOUTPUT','data/generated/symbolIndexes.json') ;
$generate=0 ;
$reload = 1 ;
if ($generate) {
  extensionCloud() ;
  $indexes = generate() ;
}
//-------- load the symbol indexes from json -----------
if ($reload) {
  echo "<h2>Loading indexes from json file <a href='".JSONOUTPUT."'>".JSONOUTPUT."</a></h2>" ;
  $indexes = new SymbolIndexes() ;
  $indexes->loadJsonFile(JSONOUTPUT) ;
  echo "done<hr/>" ;
}

//echo $indexes->getCloud('../../101repo/contributions/rubyonrails/companies/app/views/layouts/application.html.erb','A') ;
//exit ;

//--------- display Q C A R global clouds  ---------------

// echo title and the clouds
foreach (explode(' ','Q C A R') as $kind) { 
  $name = $indexes->getLevelName($kind) ;
  echo '<h2>'.$indexes->getSymbolCount(null,$kind)." $name symbols</h2>" ;
  echo $indexes->getCloud(null,$kind) ;
}

foreach ($indexes->getSymbols('T') as $textId) {
  echo '<h3>'.$textId.'</h3>' ;
  echo $indexes->getCloud($textId,'R') ;
}

//var_dump( $indexes->getDirectContainers('employe','R') );
//var_dump( $indexes->getDirectContainers('Panel','A') );
//var_dump( $indexes->getDirectContainers('getText','C') );
$tree =  $indexes->getContainersTree('employe','R','T') ;
var_dump($tree) ;
echo(htmlAsIs($indexes->treeToTxt($tree))) ;


function extensionCloud() {
  //--------- get the list of extensions and produce a cloud ---------------
  // not related to directly symbols, but fun anyway...
  $allFiles = findFiles(DIR,array("types"=>"file")) ;
  echo count($allFiles) .' files in total' ;
  $freq = extensionFrequencies($allFiles) ;
  asort($freq) ;
  echo "<h2>".count($freq).' extensions'.'</h2>' ;
  echo "" ;
  echo cloud($freq) ;
}

function generate() {  
  
  //--------- source file to consider  ---------------
  $sourceFiles = findFiles(DIR,array("types"=>"file",'pattern'=>'endsWithOne '.EXTENSIONS)) ;
  echo '<h2>Indexing '.count($sourceFiles).' files</h2>' ;
  
  //--------- create symbol indexes  ---------------
  $decomposer = new RegExprBasedSymbolDecomposer() ;
  $indexes = new SymbolIndexes() ;
  
  // load the all texts from source files and construct the text map
  
  foreach($sourceFiles as $filename) {
    $text = file_get_contents($filename) ;
    $indexes->addText($filename,$text,$decomposer) ;
  }
  echo "done<br/>" ;
  
  
  //--------- save as json -------------------------
  echo "<h2>Save indexes as json file</h2>" ;
  $indexes->saveAsJsonFile(JSONOUTPUT,$results) ;
  echo "Json file as be saved (.".filesize(JSONOUTPUT)." bytes) <a href='".JSONOUTPUT."'>".JSONOUTPUT."</a><br/>" ;
  return $indexes ;
}