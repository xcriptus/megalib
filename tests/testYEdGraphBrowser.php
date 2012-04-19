<?php
require_once '../YEdGraphBrowser.php' ;

define('ROOTDIR','../../101dev/sandbox/m12/implementations/xsdClasses') ;
define('TARGETDIR',ROOTDIR.'/docs') ;
define('YEDGRAPH','xsdClassesArtefactView.megal.graphml') ;
define('YEDGRAPHFULLNAME',ROOTDIR.'/'.YEDGRAPH) ;
define('IMAGEHTML',TARGETDIR.'/'.YEDGRAPH.'.html') ;
$sourceFileDirectory = ROOTDIR.'/repo' ;
$SourceDefinitions = array(
    array('Company.xsd','xml', array('Company'=>'4-11', 'Department'=>'13-20', 'Employee'=>'22-28')),
    array('Company.cs','csharp', array('Company'=>'25-51', 'Department'=>'59-110', 'Employee'=>'118-155')),
    array('ACMECorp.xml','xml'),
    array('ACMECorpCut.xml','xml'),
    array('CompanyXSD2CS.bat','batch'),
    array('Demo.cs','csharp'),
    array('Operations.cs','csharp'),
    array('Serialization.cs','csharp')
);

$generator = new YEdGraphBrowserGenerator(YEDGRAPHFULLNAME,$sourceFileDirectory,$SourceDefinitions,TARGETDIR) ;