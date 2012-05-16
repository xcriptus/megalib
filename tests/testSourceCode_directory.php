<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
gc_enable() ;
ini_set('memory_limit', '2048M');
define('OUTPUT_DIR','data/generated/') ;
$basedir = '../../101results/101repo' ;
$dir='contributions/' ;
echo "Exploring directory $dir \n" ;
$srcdir = new SourceTopDirectory($basedir,$dir) ;
$srcdir->generate(OUTPUT_DIR) ;
echo '<a href="'.addToPath(OUTPUT_DIR,$dir).'">generated directory</a>' ;
echo "<h1>END OF TESTS</h1>" ;

