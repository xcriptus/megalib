<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;

define('OUTPUT_DIR','data/generated/') ;

$basedir = '../../101repo/' ;
echo 'Exploring directory $srcdir\n' ;
$srcdir = new SourceDirectory($basedir,'contributions') ;
$srcdir->generate(OUTPUT_DIR) ;
echo "<h1>END OF TESTS</h1>" ;

