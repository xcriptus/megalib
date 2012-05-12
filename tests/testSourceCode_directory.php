<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;

define('OUTPUT_DIR','data/generated/') ;
$basedir = addToPath(ABSPATH_BASE,'101results/101repo') ;
$dir='contributions/ruby' ;
echo "Exploring directory $dir \n" ;
$srcdir = new SourceDirectory($basedir,$dir) ;
$srcdir->generate(OUTPUT_DIR) ;
echo "<h1>END OF TESTS</h1>" ;

