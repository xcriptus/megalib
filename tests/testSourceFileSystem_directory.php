<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
require_once '../SourceFileSystem.php' ;
require_once '../FileSystemMatcher.php' ;

define('OUTPUT_DIR','data/generated/') ;
define('RULES_FILE','data/input/sourceDirectoryMatchingRules.csv') ;
$basedir = '../../101results/101repo' ;
$dir='contributions' ; // gwtTree

gc_enable() ;
ini_set('memory_limit', '2048M');
echo "Exploring directory $dir \n" ;
$matcher = new RuleBasedFileSystemPatternMatcher(RULES_FILE) ;
$srcdir = new SourceTopDirectory($basedir,$dir,array(),$matcher) ;
$srcdir->generate(OUTPUT_DIR) ;
echo '<a href="'.addToPath(OUTPUT_DIR,$dir).'" target="_blank">generated directory</a>' ;
echo "<h1>END OF TESTS</h1>" ;

