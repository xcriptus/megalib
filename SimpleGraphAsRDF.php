<?php
/**
 * TODO: Should be rewritten based on RDF.php
 */

require_once 'config.php' ;

/**
 * TODO: Should be rewritten based on RDF.php
 */
function makeURIName($s) {
  return strtr(strtolower($s),' .@-+','_____') ;
}

/**
 * TODO: Should be rewritten based on RDF.php
 */
function toRDFTriplet($entitykind,$keyvalue,$attributename,$value,$type,$ontologyprefix) {
  $triple = array() ;
  $triple['s']        = makeURIName($keyvalue) ;
  $triple['s_type']   = 'uri';
  $triple['p'] = ((strpos($attributename,':')) ? '' : $ontologyprefix.':') .$attributename ;
  switch ($type) {
    case 'string':
      $triple['o']=$value ;
      $triple['o_type']='literal' ;
      break ;
    case 'class':
      $triple['o']=$value ;
      $triple['o_type']='uri' ;
      break ;
    default :
      $triple['o']=makeURIName($value) ;
    $triple['o_type']='uri' ;
    break ;
  }
  return $triple ;
}

function simpleGraphToRDFtripletSet($graph, $ontologyprefix) {
  $triples = array() ;
  foreach($graph->R as $entitykind => $mapofentities) {
    $attributes=$graph->schema->getAttributeDescriptions($entitykind) ;
    foreach ($mapofentities as $keyvalue => $map ) {
      $triples[]=toRDFTriplet($entitykind,$keyvalue,
                              'rdf:type',$ontologyprefix.':'.$entitykind,'class',$ontologyprefix) ;
      foreach ($attributes as $attributename => $attributeinfo) {
        switch ($attributeinfo['tag']) {
          case '@':
          case '!':
          case '?':
            if (isset($map[$attributename])) {

              $value=$map[$attributename] ;
              $triples[]=toRDFTriplet($entitykind,$keyvalue,
                                      $attributename,$value,$attributeinfo['type'],$ontologyprefix) ;

            }
            break ;
          case '*':
            if (isset($map[$attributename])) {
              foreach ($map[$attributename] as $value) {
                $triples[]=toRDFTriplet($entitykind,$keyvalue,
                                        $attributename,$value,$attributeinfo['type'],$ontologyprefix) ;
              }
            }
            break ;
          default:
            assert(false) ;
        }

      }
    }
  }
  return $triples ;
}

function saveTriplesToRDFStore($triples,$rdfstore,$graphuri) {
  $rdfstore->insert($triples,$graphuri) ;
  if ($errs = $rdfstore->getErrors()) {
    print_r($errs) ;
  } else {
    if (DEBUG) echo "<h1>triples saved to RDF store.</h1>" ;
  }
}
