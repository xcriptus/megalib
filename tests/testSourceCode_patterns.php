<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
require_once '../CSV.php' ;
define('OUTPUT_DIR','data/generated/sourceCodePatterns') ;
define('RULES_FILE','data/input/FileSystemPattern.csv') ;
define('EXPLORE_DIR','../../101results/101repo/contributions') ;  // /gwtTree
echo '<h2>Exploring and matching the directory '.EXPLORE_DIR.'</h2>' ;
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
$r = $matcher->generate(EXPLORE_DIR,OUTPUT_DIR,$groups) ;
echo 'files generated are in <a href="'.OUTPUT_DIR.'" target="_blank">'.OUTPUT_DIR.'</a>' ;
