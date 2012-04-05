<?php
/* CUT AND PASTE the following text in localConfigEnvironment.php
 <?php
// all constants starting by ABSPATH are absolute and ending with a trailing /
define('ABSPATH_ENV_WRAPPERS_BIN','/home/megaplan/php-bin/') ;
*/
require_once 'localConfigEnvironment.php' ;

define('ENV_GREPDIR_CMD',ABSPATH_ENV_WRAPPERS_BIN.'php-grepdir') ;
define('ENV_GREPLINK_CMD',ABSPATH_ENV_WRAPPERS_BIN.'php-greplink') ;
