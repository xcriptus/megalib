<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
require_once '../CSV.php' ;
define('OUTPUT_DIR','data/generated/') ;
define('RULES_FILE','data/input/FileSystemPattern.csv') ;
define('EXPLORE_DIR','../../101results/101repo/contributions') ;

echo '<h2>Reading rules from '.RULES_FILE.'</h2>' ;
$csv = new CSVFile() ;
if (! $csv->load(RULES_FILE)) {
  die('Cannot read '.RULES_FILE);
}
$rules = $csv->getListOfMaps() ;
echo '<b>'.count($rules).'</b> rules defined' ;
echo mapOfMapToHTMLTable($rules,'',true,true,null,2) ;


$patternMatcher = new FileSystemPatternMatcher($rules) ;
$r = $patternMatcher->matchFileSystem(EXPLORE_DIR) ;

echo '<h2>Launching the exploration and matching of '.EXPLORE_DIR.'</h2>' ;
echo 'Please wait as this can take a few minutes</br>' ;
echo $r['nbOfFilesMatched']." files matched over ".$r['nbOfFiles']." files : ".$r["matchRatio"]."%<br/>" ;
echo "<h2>Basenames of files not matched</h2>" ;
echo mapOfMapToHTMLTable($r['basenamesOfFilesNotMatched'],'',true,true,null,2) ;
echo "<h2>Extensions of files not matched</h2>" ;
echo mapOfMapToHTMLTable($r['extensionsOfFilesNotMatched'],'',true,true,null,2) ;

