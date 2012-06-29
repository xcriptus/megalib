<?php
require_once 'main.config.local.php' ;

require_once '../YEdGraphBrowser.php' ;

define('ROOTDIR','../../101dev/sandbox/m12/implementations/xsdClasses') ;
define('TARGETDIR',ROOTDIR.'/docs') ;
define('YEDGRAPHFULLNAME',ROOTDIR.'/xsdClassesArtefactView.megal.graphml') ;
echo "<h2>Generating a browser for the graph".YEDGRAPHFULLNAME.'</h2>' ;
$sourceFileDirectory = ROOTDIR.'/repo' ;
$SourceDefinitions = array(
    array('Company.xsd','xml', array('Company'=>'4-11', 'Department'=>'13-20', 'Employee'=>'22-28')),
    array('Company.cs','csharp', array('Company'=>'25-51', 'Department'=>'59-110', 'Employee'=>'118-155')),
    array('ACMECorp.xml','xml'),
    array('ACMECorpCut.xml','xml'),
    array('CompanyXSD2CS.bat','batch'),
    array('Demo.cs','csharp', array('CutAcmeCorp'=>'38-43')),
    array('Operations.cs','csharp'),
    array('Serialization.cs','csharp')
);

$generator = 
  new YEdGraphBrowserGenerator(
       YEDGRAPHFULLNAME,
       $sourceFileDirectory,
       $SourceDefinitions,
       TARGETDIR) ;

echo "result is in <a href='".TARGETDIR."'>".TARGETDIR."</a><br/>" ;

echo "<h1>END OF TESTS</h1>" ;