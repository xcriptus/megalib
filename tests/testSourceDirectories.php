<?php
require_once 'main.config.local.php' ;

require_once '../SourceDirectories.php' ;
require_once '../FileSystemMatcher.php' ;

define('OUTPUT_DIR','data/generated/') ;
define('RULES_FILE','data/input/sourceDirectoryMatchingRules.csv') ;

$basedir = '../../101results/101repo' ;
$dir='contributions/gwtTree' ; // gwtTree

gc_enable() ;
ini_set('memory_limit', '2048M');
echo "Exploring directory $dir \n" ;
$matcher = new GeSHiExtensionPatternMatcher() ;
$srcdir = new SourceTopDirectory($basedir,$dir,$matcher) ;
$srcdir->generate(OUTPUT_DIR) ;
echo '<a href="'.addToPath(OUTPUT_DIR,$dir).'" target="_blank">generated directory</a>' ;
echo "<h1>END OF TESTS</h1>" ;

