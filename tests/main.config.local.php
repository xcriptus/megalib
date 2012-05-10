<?php

define('_MEGALIB','true') ;

define('DEBUG',10) ;

define('ABSPATH_BASE',dirname(dirname(__DIR__)).'/') ;

// Absolute path to the external libraries
define('ABSPATH_EXTERNAL_LIBRARIES',ABSPATH_BASE) ;


// megalib directory
// Change it only if it is not in the regular place for libraries
define('ABSPATH_MEGALIB',ABSPATH_EXTERNAL_LIBRARIES.'megalib/') ;


//---- Config for Github.php
define('ABSPATH_GITHUB_LIBRARY',ABSPATH_EXTERNAL_LIBRARIES.'php-github-api/') ;


//---- Config for Environment.php
define('ABSPATH_ENV_WRAPPERS_BIN','/home/megaplan/php-bin/') ;

//---- Config for SourceCode.php
define('ABSPATH_SRC_GESHI_LIBRARY',ABSPATH_EXTERNAL_LIBRARIES.'geshi/') ;


//---- Config for RDF.php

define('RDF_ARC2_LIBRARY',ABSPATH_EXTERNAL_LIBRARIES.'arc2') ;
// A database account necessary if you want to run the tests
define('RDF_TEST_DATABASE_NAME','arc2_test') ;
define('RDF_TEST_DATABASE_USER','rdfdbuser') ;
define('RDF_TEST_DATABASE_PASSWORD','6456yiu78464') ;



