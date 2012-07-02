<?php
require_once 'main.config.local.php' ;
require_once '../TExpression.php' ;
require_once '../HTML.php' ;


$matchToTemplateTestCases =  array(
    array('matches /.*\.java/',"/x/y/toto.java",'${0}'),
    array('endsWith .java',"/x/y/toto.java",'${0}'),
    array('matches #(.*)(\.java)#',"/x/y/toto.java",'matching ${0} yields ${1} with extension ${2}',),
    array('basename | endsWith .java',"/x/y/toto.java",'${0}'),
    array('dirname | basename  | matches/.*/',"/x/y/toto.java",'${0}'),
    array('content | /\A<\?xml/',"data/input/countries.graphml",'${0}'),
    array('content | /\A<\?xml/',"donotexit.x",'${0}'),
    array('endsWith .graphml && content | matches /graphml/',"data/input/countries.graphml",'${0}'),
    array('endsWith .graphml && content | head 3 | matches /.*/',"data/input/countries.graphml",'${0}'),
    
    ) ;

foreach($matchToTemplateTestCases as $t) {
  echo htmlAsIs('matchToTemplate( "'.$t[0].'" , "'.$t[1].'" , "'.$t[2].'")). =') ;
  $r = matchToTemplate($t[0],$t[1],$t[2]) ;
  var_dump($r) ;
}

