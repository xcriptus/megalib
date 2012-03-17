<?php
define("DEBUG",0) ;
echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
require_once '../HTML.php' ;
require_once '../Github.php' ;
require_once '../GithubAsRDF.php' ;
require_once '../Graph.php' ;
define('OUTPUT_DIR','data/generated/') ;



function getRDFTestStore($storename) {
  $testdbaccount = new DatabaseAccount(RDF_TEST_DATABASE_NAME, RDF_TEST_DATABASE_USER, RDF_TEST_DATABASE_PASSWORD) ;
  $configuration = new RDFStoreConfiguration(array(),$testdbaccount, $storename) ;
  return new RDFStore($configuration,'') ;
}

function testGithubAsRDF($account,$reponame) {
  echo '<h1>Testing GithubAsRDF</h1>' ;
  $repoid = $account.'_'.$reponame ;
  echo "<p>Creation the github repository object for $repoid</p>" ;
  $repository = new GithubRepository($account, $reponame) ;
  
  // get the TripleSet
  echo '<p>Generation of the triple set</p>' ;
  $githubasrdf = new GithubAsRDF('http://data.101companies.org/data/github', 
                                 'http://data.101companies.org/schema/github') ;
  $githubasrdf->githubRepositoryAsTriples($repository) ;
  $tripleset = $githubasrdf->getTripleSet() ;
  
  // save the TripleSet in different files with different format
  echo '<p>Saving the triples to the files'.OUTPUT_DIR.$repoid.'.xxx</p>' ;
  $tripleset->saveFiles('HTML,Turtle,GraphML',OUTPUT_DIR.$repoid) ;
  
  // save it to a store
  echo '<p>Saving the triples to the '.$repoid.' RDF store</p>' ;  
  $store = getRDFTestStore($repoid) ;
  $n = $store->loadTripleSet($tripleset, 'http://data.101companies.org/data/'.$repoid) ;
  echo "<p>$n triples added</p>" ;
  
}

//testGithubAsRDF('megaplanet','asop');
testGithubAsRDF('megaplanet','101implementations');

echo "<h1>End of tests</h1>" ;