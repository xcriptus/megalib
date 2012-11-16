<?php
require_once 'main.config.local.php' ;

require_once '../Files.php' ;
require_once '../Symbols.php' ;
require_once '../HTML.php' ;



// showLink("Rebuild corpus",array("buildIndexes"=>1)) ;
// var_dump($_GET) ;


define('DEFAULT_INDEXES_FILE','data/generated/symbolIndexes.json') ;
define('DEFAULT_DIRECTORY',"../../101repo/contributions") ;
define("DEFAULT_SYMBOL_ROOT","employe") ;
define("DEFAULT_EXTENSIONS",".java .hs .cs .rb .php") ;
$optionsInfo = 
  array(
    "indexesFile" => array("Indexes (in a one json file)","STRING",DEFAULT_INDEXES_FILE),  
    "mode" => array("Initialization mode","ENUM",
                    "build|BUILD: Indexes are built from the corpus (parameters below) and then saved",
                    "load|LOAD: Indexes are loaded (parameters are ignored)" ),
    "corpusKind" => array("Corpus Kind (build mode)","ENUM",
                            "filenames|Filenames within a Directory",
                            "tokens|Tokensized Source File Directory",
                            "sources|File content of a Directory"),                            
    "corpusDirectory" => array("Corpus Directory (build mode)","STRING",DEFAULT_DIRECTORY),
    "extensions" => array("Extensions (build mode, only for 'File content' corpus)","STRING",DEFAULT_EXTENSIONS),
    "showGlobal" => array("SHOW Global Corpus Cloud","BOOLEAN",0),
    "showTextCloud" => array("SHOW Individual Text Clouds","BOOLEAN",0),
    "Q" => array("Qualified Symbols","BOOLEAN",0),
    "C" => array("Composite Symbols","BOOLEAN",0),
    "A" => array("Atomic Symbols","BOOLEAN",0),
    "R" => array("Root Symbols","BOOLEAN",0),
    "showContainerTree" => array("SHOW Container Tree (from symbol below)","BOOLEAN",0),
    "symbolRoot" => array("Symbol for Container Tree","STRING",DEFAULT_SYMBOL_ROOT),
    "showExtensionCloud" => array ("SHOW File Extension Cloud (do not use corpus/indexes)","BOOLEAN",0),
  ) ;

$options = new Options($optionsInfo) ;
$PARAM = $options->getValues($_GET) ;
echo $options->getHTML("testSymbols.php",$PARAM) ;

define('LEVELFORTEXTCLOUD','R') ;
define('SYMBOLLEVELEND','T') ;



if ($PARAM["mode"]==="build") {
  $indexes = buildAndSaveIndexes($PARAM) ;
} else {
  $indexes = loadIndexes($PARAM["indexesFile"]) ;
}

if (isset($PARAM["showGlobal"])) {
  showGlobalClouds($indexes,$PARAM) ;
}

if (isset($PARAM["showTextCloud"])) {
  showTextClouds($indexes,$PARAM) ;
}

if (isset($PARAM["showContainerTree"])) {
  showContainerTree($indexes,'R',$PARAM['symbolRoot'],SYMBOLLEVELEND) ;
}

if (isset($PARAM["showExtensionCloud"])) {
  showExtensionCloud($PARAM['corpusDirectory']) ;
}







function corpusFactory($PARAM) {
  $decomposer = new RegExprBasedSymbolDecomposer() ;
  $corpusKind=$PARAM['corpusKind'] ;
  $directory=$PARAM['corpusDirectory'];
  $extension=$PARAM['extensions'];
  switch ($corpusKind) {
    case 'tokens':
      $description = "TokenizedSourceCodeDirectoryCorpus(".$directory.")" ;
      $corpus = new TokenizedSourceCodeDirectoryCorpus($directory,$decomposer,array('de')) ;
      break ;
    case 'sources':
      $extensions = $PARAM["extensions"] ;
      $description = "DirectoryFileContentsCorpus(".$directory."endsWithOne ".$extensions.")" ;
      $corpus = new DirectoryFileContentsCorpus($directory,array('pattern'=>'endsWithOne '.$extensions),$decomposer) ;
      break ;
    case 'filenames':
      $description = "SubDirectoriesFilenamesCorpus(".$directory.")" ;
      $corpus = new SubDirectoriesFilenamesCorpus($directory,array('apply'=>'basename'),$decomposer) ;
      break ;
    default:
      die("unknown corpus kind: ".$corpusKind) ;
  }
  return array("corpus" => $corpus, "description" => $description) ; 
}


function buildAndSaveIndexes($PARAM) {
  
  //--------- corpus creation  ---------------
  $corpusAndDescription=corpusFactory($PARAM) ;
  
  echo '<hr/><h2>BUILD Indexes</h2><ul>' ;

  //--------- create symbol indexes  ---------------
  echo '<li>Creating Indexes from corpus '.$corpusAndDescription["description"].' ... ' ;
  $indexes = new SymbolIndexes($corpusAndDescription["corpus"]) ;
  echo 'done</li>' ;
  
  //--------- create symbol indexes  ---------------
  echo "<li>Saving indexes ... " ;
  $jsonfile=$PARAM["indexesFile"];
  $indexes->saveAsJsonFile($jsonfile,$results) ;
  echo "done. Saved in ".filesize($jsonfile)." bytes in <a href='".$jsonfile."'>".$jsonfile."</a></li>" ;
  echo "</ul>" ;
  return $indexes ;
}
  



function loadIndexes($jsonfile) {
  echo "<hr/><h2>LOAD Indexes</h2><ul>" ;
  $indexes = new SymbolIndexes() ;
  echo '<li>Loading indexes from '.$jsonfile.' ... ' ;
  $indexes->loadJsonFile($jsonfile) ;
  echo "done</li>" ;
  echo "</ul>" ;
  return $indexes ;
}

function showGlobalClouds($indexes,$PARAM) {
  echo "<hr/><h2>SHOW Global Corpus Clouds</h2>" ;
  foreach (explode(' ','Q C A R') as $kind) { 
    $name = $indexes->getLevelName($kind) ;
    echo '<h3>'.$indexes->getSymbolCount(null,$kind)." $name symbols - Global Corpus</h3>" ;
    if (isset($PARAM[$kind])) {
      echo $indexes->getCloud(null,$kind) ;
    }
  }
}

function showTextClouds($indexes,$PARAM) {
  echo "<hr/><h2>SHOW Text Clouds</h2>" ;
  foreach ($indexes->getSymbols('T') as $textId) {
    echo '<h3> Text <a href="'.$textId.'" target="_blank">'.$textId.'</a></h3>' ;
    foreach (explode(' ','Q C A R') as $kind) {
      $name = $indexes->getLevelName($kind) ;
      echo '<h4>'.$indexes->getSymbolCount($textId,$kind)." $name symbols - Corpus</h4>" ;
      if (isset($PARAM[$kind])) {
        echo $indexes->getCloud($textId,$kind) ;
      }   
    } 
  }
}

function showContainerTree($indexes,$symbolKind,$symbol,$targetSymbolKind) {
  //var_dump( $indexes->getDirectContainers('employe','R') );
  //var_dump( $indexes->getDirectContainers('Panel','A') );
  //var_dump( $indexes->getDirectContainers('getText','C') );
  if ($indexes->exists($symbolKind,$symbol)) {
    $tree =  $indexes->getContainersTree($symbol,$symbolKind,$targetSymbolKind) ;
    echo(htmlAsIs($indexes->treeToTxt($tree))) ;
  } else {  
    echo "Invalid choice: '$symbol' is not a '$symbolKind' symbol" ;
  }
  // var_dump($tree) ;
}


function showExtensionCloud($directory) {
  //--------- get the list of extensions and produce a cloud ---------------
  // not related to directly symbols, but fun anyway...
  $extensions = findFiles($directory,array("types"=>"file","apply"=>"extension")) ;
  $freq = array_frequencies($extensions) ;
  echo "<hr/><h2>FILE EXTENSION CLOUD</h2><h3>".count($freq)." extensions in directory $directory</h3>" ;
  echo "" ;
  echo cloud($freq) ;
}
