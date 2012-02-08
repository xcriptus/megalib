<?php
echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
require_once '../HTML.php' ;
require_once '../Github.php' ;
require_once '../GithubAsRDF.php' ;

$repository = new GithubRepository('101companies', '101implementations') ;
$githubasrdf = new GithubAsRDF('http://data.mydomain.org/data/', 'http://data.mydomain.org/schema/') ;
$githubasrdf->githubRepositoryAsTriples($repository,array()) ;
$triples = $githubasrdf->getTriples() ;
echo arrayMapToHTMLList($triples) ;