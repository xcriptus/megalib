<?php

require_once 'RDF.php' ;
require_once 'SimpleGraph.php' ;

/**
 * Converter for SimpleGraph to RDF Triples
 *
 */
class SimpleGraphAsRDF {
  protected /*RDFTripleSet!*/ $tripleSet ;
  
  /**
   * Return the triples as a arc2 array of triples.
   * @return Set*(RDFTriple!)!
   */
  public function getTriples() {
    return $this->tripleSet->triples ;
  }
  
  /**
   * Return a TripletSet object
   * @return RDFTripleSet
   */
  public function getTripleSet() {
    return $this->tripleSet ;
  }
  
  /**
   * Add the given graph to the triple set using the prefixes specified.
   * @param SimpleGraph! $graph
   * @param URI!|Map(EntityKind!,URI!)! $dataprefixes Prefix(es) to build URIs for
   * entities. If $dataprefixes is a string, then all kinds of entities will use
   * the same schema. Otherwise a prefix should be given for each entity kind in
   * the form of a Map(EntityKind!,URI!)! 
   * @param URI! $ontologyprefix This prefix will be added in the context
   * type declaration of entities to create an URI for the type of entity.
   * For instance a type of entity 'feature' will be converted to
   * http://data.mydomain.org/schema#feature if $ontologyprefix has the 
   * value http://data.mydomain.org/schema# 
   * @return void 
   */
  public function addSimpleGraph(SimpleGraph $graph, $dataprefixes,$ontologyprefix) {
    
    echo( isset($this->tripletSet)) ;
    // set the default schema prefix
    $this->tripleSet->currentSchemaPrefix = $ontologyprefix ;
    
    foreach($graph->DATA as $entitykind => $mapofentities) {
      // set the data prefix for this kind of entities.
      $dataprefix = (is_string($dataprefixes)?$dataprefixes:$dataprefixes[$entitykind]) ;
      $this->tripleSet->currentDataPrefix = $dataprefix ;
      
      $attributes=$graph->SCHEMA->getAttributeDescriptions($entitykind) ;
      foreach ($mapofentities as $keyvalue => $map ) {
        
        // define the type of the entity
        $this->tripleSet->addTriple('type',$keyvalue,'rdf:type',$entitykind) ;
        
        // define data or links triple for each attribbute
        foreach ($attributes as $attributename => $attributeinfo) {
          switch ($attributeinfo['tag']) {
            case '@':
            case '!':
            case '?':
              if (isset($map[$attributename])) {
                $value=$map[$attributename] ;
                $this->tripleSet->addTriple(
                    'data',$keyvalue,$attributename,$value) ;
              }
              break ;
            case '*':
              if (isset($map[$attributename])) {
                foreach ($map[$attributename] as $value) {
                  $this->tripleSet->addTriple(
                      'link',$keyvalue,$attributename,$value) ;
                }
              }
              break ;
            default:
              assert(false) ;
          }
  
        }
      }
    }
    // reset the default prefixes to avoid side effect in the future
    $this->tripleSet->currentSchemaPrefix = null ;
    $this->tripleSet->currentDataPrefix = null ;
    
  }
  
  /**
   * Create a converter of SimpleGraph to RDF. 
   */
  public function __construct() {
    $this->tripleSet = new RDFTripleSet() ;
  }
}
