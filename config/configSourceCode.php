<?php
/**
 * COPY AND PASTE the code below into a file named 
 * localConfigXXX.php in this very directory.
 * Then adapt the constants to your local settings.
 * Note that the file localConfigXXX are ignored during commit.
 * -------------------------------------------------------------
 * Setup for using SourceCode.php
 * Download the geshi package from http://qbnz.com/highlighter/ 
 * Copy the directory at the top level. Otherwise change the 
 * _LIBRARY constant below.
<?php
// Path to the package
define('SRC_GESHI_LIBRARY',__DIR__.'/../../geshi') ;
 */
require_once 'localConfigSourceCode.php' ;
require_once SRC_GESHI_LIBRARY.'/geshi.php' ;