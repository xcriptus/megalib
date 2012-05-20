<?php
require_once 'main.config.local.php' ;

require_once '../Structures.php' ;
require_once '../HTML.php' ;
require_once '../TExpr.php' ;

testSaveLoadMerge() ;
testJsonBeautifier() ;
testMapFromJsonDirectory() ;


function testSaveLoadMerge() {
  echo "<h2>test Save, Load, Merge json files</h2>" ;
  $in="data/input/index.summary.json" ;
  $out="ata/generated/x.json" ;
  $m = jsonLoadFileAsMap($in) ;
  $m['toto']='added' ;
  if (saveAsJsonFile($out,$m)) {
    echo "file saved" ;
    $r = jsonLoadFileAsMap($out) ;
    var_dump($r) ;
    echo "file saved" ;
    saveOrMergeJsonFile($out,$r) ;
    $r = jsonLoadFileAsMap($out) ;
    var_dump($r) ;
    $m2=array("added"=>"new elem") ;
    saveOrMergeJsonFile($out,$m2) ;
    $r = jsonLoadFileAsMap($out) ;
    var_dump($r) ;
    
  } else {
    die('error file saving file') ;
  }
}

function testJsonBeautifier() {
  echo '<h2>Testing jsonBeautifier</h2>' ;
  $a = array(
      'x' => 'x1',
      'y' => array('y1','y2','y3')) ;
  
  echo htmlAsIs(jsonBeautifier(json_encode($a))) ;
}

function testMapFromJsonDirectory() {
  echo '<h2>Testing mapFromJsonDirectory </h2>' ;
  $testCases1 = array(
      array(
          'dir'=>'data/generated/contributions/gwt',
          'recursive'=>false,
          'pattern'=>'suffix:index.summary.json',
          'key'=>'${0}'
      ),
      array(
          'dir'=>'../../101results/101repo',
          'recursive'=>true,
          'pattern'=>'/(.*)\.fratala$/',
          'key'=>'${1}'
      ),
      array(
          'dir'=>'../../101results/101repo',
          'recursive'=>true,
          'pattern'=>'suffix:.fratala',
          'key'=>'${1}'
      ),
  ) ;
  foreach ($testCases1 as $t) {
    if (is_dir($t['dir'])) {
      echo '<h3>mapFromJsonDirectory('.$t['dir'].'" , "'.$t['recursive'].'" , "'.$t['pattern'].'" , "'.$t['key'].'" )</h3>' ;
      echo htmlAsIs(jsonEncode(mapFromJsonDirectory($t['dir'],$t['recursive'],$t['pattern'],$t['key']),true)) ;
    } else {
      echo "<h3>Directory ".$t['dir']." doesn't exist <h3>" ;
    }
  }
}

echo "<h1>END OF TESTS</h1>" ;
