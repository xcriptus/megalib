<?php
require_once 'main.config.local.php' ;

require_once '../Fragments.php' ;

$root = '../../101results/101repo/contributions' ;
$rulesFile = 'data/input/sourceDirectoryMatchingRules.csv' ;
$tmpDir = '/tmp' ;
$commandsBaseDirectory='.' ;

echo "=== Creating the reader with rules from $rulesFile\n" ;
$reader = new TaggedFragmentSetReader($rulesFile) ;

echo "=== Reading tagged fragment definitions from $root\n" ;
$taggedFragmentSet = $reader->read($root) ;
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
$taggedFragmentSet->saveInJsonSummaryFiles(true) ;