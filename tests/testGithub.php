<?php
require_once 'main.config.local.php' ;

define('GIT_ACCOUNT','101companies') ;
define('GIT_REPOSITORY','101repo') ;
//define('GIT_FILE','https://github.com/megaplanet/101implementations/blob/master/xsdClasses/xsd/Company.xsd') ;
define('GIT_FILE','http://planet-mde.org/idm06/') ;
echo 'If this page display errors then have a look in the corresponding configs/configXXX.php file<br/>' ;
echo 'Possible errors can come from the Github API so refresh the script to see that the problem persist<br/>' ;

require_once '../HTML.php' ;
require_once '../Github.php' ;

echo '<h1>Repository</h1>' ;
$repository = new GithubRepository(GIT_ACCOUNT, GIT_REPOSITORY) ;
echo 'owner: '.$repository->getUsername().'<br/>' ;
echo 'name: '.$repository->getRepositoryName().'<br/>' ;
$contributors = $repository->getContributors() ;
echo 'countributors#: '.count($contributors) ;

echo '<h2>Repository Tags</h2>' ;
$tagsInfo = $repository->getTagsInfo() ;
echo mapToHTMLList($tagsInfo) ;

$tree = $repository->getBranchTree() ;
echo '' ;
/*
$gfile = new GithubFile(GIT_FILE);
$html = $gfile->getHighlighted() ;
if (isset($html)) {
  echo $html ;
} else {
  echo "no file ".GIT_FILE ;
}
*/
getFile(GIT_FILE) ;
echo '<h1>END OF TESTS</h1>' ;