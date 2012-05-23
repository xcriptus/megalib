<?php
require_once 'main.config.local.php' ;

require_once '../Fragments.php' ;

$sourceBaseDirectory = '../../101results/101repo' ;
$targetBaseDirectory = '../../101web/101repo' ;
$mainDirectory = 'contributions' ;
$rulesFile = 'data/input/sourceDirectoryMatchingRules.csv' ;
$tmpDir = '/tmp' ;
$commandsBaseDirectory='.' ;

echo "=== Creating the reader with rules from $rulesFile\n" ;
$reader = new TaggedFragmentSetReader($rulesFile) ;

$sourceDirectory=addToPath($sourceBaseDirectory,$mainDirectory) ;
echo "=== Reading tagged fragment definitions from $sourceDirectory\n" ;
$taggedFragmentSet = $reader->read($sourceDirectory) ;
$nbErrors = count($reader->getErrors()) ;
if ($nbErrors!==0) {
  echo "$nbErrors error(s) found\n" ;
  echo htmlAsIs($reader->getErrorsAsJson(true)) ;
}

if (DEBUG>10) echo htmlAsIs($taggedFragmentSet->asJson(true)) ;

echo "=== Applying locators to find fragment location\n" ;
$locatorIterator = new FragmentLocatorIterator($tmpDir,$commandsBaseDirectory) ;
$locatorIterator->addLocationToAllFragments($taggedFragmentSet,true) ;

echo "=== Computing derived informationn\n" ;
$taggedFragmentSet->computeDerivedInformation() ;
if (DEBUG>10) echo htmlAsIs($taggedFragmentSet->asJson(true)) ;

echo "=== Saving taggedFragments to json files\n" ;
$taggedFragmentSet->saveInJsonSummaryFiles($sourceBaseDirectory,$targetBaseDirectory,true) ;