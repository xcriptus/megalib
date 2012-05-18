<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
require_once '../CSV.php' ;
define('OUTPUT_DIR','data/generated/sourceCodePatterns') ;
define('RULES_FILE','data/input/FileSystemPattern.csv') ;
$root = addToPath(ABSPATH_BASE,'101results') ;
if (!is_dir($root)) {
  $root = addToPath(ABSPATH_BASE,'../101results') ;
}
$exploreDir = addToPath($root,'101repo/contributions') ;  // /gwtTree

echo '<h2>Exploring and matching the directory '.$exploreDir.'</h2>' ;
$matcher = new FileSystemPatternMatcher(RULES_FILE) ;
$groups=array(
    'languages' => 
       array(
           'select' => array('locator','geshiLanguage'),
           'groupedBy' => 'language' 
        ),
     'technologies' =>
       array(
           'select' => array('role'),
           'groupedBy' => 'technology'
       )
 ) ;
$r = $matcher->generate($exploreDir,OUTPUT_DIR,$groups) ;
echo 'files generated are in <a href="'.OUTPUT_DIR.'" target="_blank">'.OUTPUT_DIR.'</a>' ;
