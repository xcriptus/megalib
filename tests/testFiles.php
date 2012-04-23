<?php
require_once 'main.config.local.php' ;

require_once '../Files.php' ;
testIsAbsolutePath() ;
testGetRelativePath() ;
testListFileNameFunctions('listFileNames','..') ;
testListFileNameFunctions('listAllFileNames','.') ;
testListAllLinksWithInfo('..') ;

function testIsAbsolutePath() {
  echo "<h2>Testing isAbsolutePath</h2>" ;
  $a=explode(' ',
      'c:/x/b x:\e\f ./sdkfjs flf/kdf:a \sdfkj /skfjs/sdfkj .. sdfsdf'
      . ' http://domain.org/toto file://') ;
  
  foreach($a as $path) {
    $result = (isAbsolutePath($path)?'true':'false') ;
    echo "<li>isAbsolutePath($path)=$result</li>" ;
  }
}

function testGetRelativePath() {
  echo "<h2>Testing getRelativePath</h2>" ;
  $a= array(
      array('/srv/foo/bar','/srv','foo/bar'),
      array('/srv/foo/bar','/srv/','foo/bar'),
      array('/srv/foo/bar/','/srv','foo/bar',),
      array('/srv/foo/bar/','/srv/','foo/bar'),
      array('/srv/foo/bar','/srv/test','../foo/bar'),
      array('/srv/foo/bar','/srv/test/fool','../../foo/bar'),
      array('/srv/mad/xp/mad/model/static/css/uni-form.css','/srv/mad/xp/liria/','../mad/model/static/css/uni-form.css'),
  );
  foreach($a as $values){ 
    echo "<li>In <b>$values[1]</b> , the directory <b>$values[0]</b> is at " ;
    $result = getRelativePath( $values[0], $values[1] );
    echo "$result</li>";
    if ($result != $values[2]) {
      die('<b>value expected: '.$values[2].'</b>') ;  
    }
  }
}

function testListFileNameFunctions($funname,$dir) {
  echo "<h2>Testing $funname</h2>" ;
  echo "<h4>$funname($dir)</h4>" ;
  $items = $funname($dir) ;
  echo implode('<br/>',$items) ;
  
  echo "<h4>$funname($dir,'dir')</h4>" ;
  $items = $funname($dir,'dir') ;
  echo implode('<br/>',$items) ;
  
  echo "<h4>$funname($dir,'link')</h4>" ;
  $items = $funname($dir,'link') ;
  foreach ($items as $link) {
    echo '<li>'.$link.'  -->  '.readlink($link) ;
  }
  
  
  echo "<h4>$funname($dir,'file','/RDF/',null,false)</h4>" ;
  $items = $funname($dir,'file','/RDF/',null,false) ;
  echo implode('<br/>',$items) ;
}

function testListAllLinksWithInfo($dir) {
  echo "<h2>Testing listAllLinksWithInfo</h2>" ;
  var_dump(listAllLinksWithInfo($dir)) ;
}




