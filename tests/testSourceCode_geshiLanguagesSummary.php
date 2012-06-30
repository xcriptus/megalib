<?php
require_once 'main.config.local.php' ;

require_once '../SourceCode.php' ;
//echo "Results go to data/generated/*" ;


$filters = array('/COMMENT/','/KEYWORDS|URLS|SYMBOLS/','/STYLE/',true) ;
displayLanguageMatrices($filters) ;
function displayLanguageMatrices($filters) {
  foreach($filters as $filter) {
    echo '<h2>Language matrix with filter '.$filter.'</h2>' ;
    echo GeSHiExtended::getLanguagePropertyHTMLMatrix($filter) ;
  }
}


echo "<h1>END OF TESTS</h1>" ;

