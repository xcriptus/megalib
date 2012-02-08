<?php
/**
 * COPY AND PASTE the code below into a file named 
 * localConfigRDF.php in this very directory.
 * Then adapt the constants to your local settings.
 * Note that the file localConfigXXX are ignored during commit.
 * -------------------------------------------------------------
 * Setup for using RDF.php
 * Download the arc2 library from https://github.com/semsol/arc2
 * Copy the arc2 directory at the top level. Otherwise change the
 * RDF_ARC2_LIBRARY constants.
 * Create a database for test if you want to run the tests.
<?php
// Path to the arc2 package
define('RDF_ARC2_LIBRARY',__DIR__.'/../../arc2') ;
// A database account necessary if you want to run the tests
define('RDF_TEST_DATABASE_NAME','rdfdbtest') ;
define('RDF_TEST_DATABASE_USER','rdfdbuser') ;
define('RDF_TEST_DATABASE_PASSWORD','645dfsf664') ;
 */
require_once 'localConfigRDF.php' ;
require_once RDF_ARC2_LIBRARY.'/ARC2.php' ;
