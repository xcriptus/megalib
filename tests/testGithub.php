<?php

echo 'If this page display errors then have a look in the corresponding config/configXXX.php file<br/>' ;
echo 'Possible errors can come from the Github API so refresh the script to see that the problem persist<br/>' ;

require_once '../HTML.php' ;
require_once '../Github.php' ;

echo '<h1>Repository</h1>' ;
$repository = new GithubRepository('101companies', '101implementations') ;
echo 'owner: '.$repository->getUsername().'<br/>' ;
echo 'name: '.$repository->getRepositoryName().'<br/>' ;
$contributors = $repository->getContributors() ;
echo 'countributors#: '.count($contributors) ;

echo '<h2>Repository Tags</h2>' ;
$tagsInfo = $repository->getTagsInfo() ;
echo mapToHTMLList($tagsInfo) ;

$tree = $repository->getBranchTree() ;
echo '' ;




echo '<h1>Ends of Tests</h1>' ;