<?php
require_once 'main.config.local.php' ;

echo 'If this page display errors then have a look in the corresponding configs/configXXX.php file<br/>' ;
echo 'Note that this page access the web</br>' ;
require_once '../RDF.php';
require_once '../HTML.php';

define('ICPW2009_RDF','http://data.semanticweb.org/dumps/conferences/icpw-2009-complete.rdf') ;


$testdbaccount = new DatabaseAccount(RDF_TEST_DATABASE_NAME, RDF_TEST_DATABASE_USER, RDF_TEST_DATABASE_PASSWORD) ;
$configuration = new RDFStoreConfiguration(array(),$testdbaccount, 'test') ;
$store = new RDFStore($configuration,'') ;
$store->startSparqlEndpoint() ;

