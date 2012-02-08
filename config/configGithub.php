<?php
/**
 * COPY AND PASTE the code below into a file named
 * localConfigRDF.php in this very directory.
 * Then adapt the constants to your local settings.
 * Note that the file localConfigXXX are ignored during commit.
 * -------------------------------------------------------------
 * Setup for using Github.php
 * Download the php-github-api library from https://github.com/ornicar/php-github-api
 * Install it at the top level or change the GITHUB_LIBRARY constant.
<?php
// Path to the php-github-api directory 
define('GITHUB_LIBRARY',__DIR__.'/../../php-github-api') ;
 */

require_once 'localConfigGithub.php' ;
require_once GITHUB_LIBRARY.'/lib/Github/Autoloader.php' ;
Github_Autoloader::register();
