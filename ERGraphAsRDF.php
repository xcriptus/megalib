<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'RDF.php' ;
require_once 'ERGraph.php' ;

/**
 * Converter for ERGraph to RDF Triples
 * 
 *   type URIPattern == URISimplePattern | URIChoicePattern
 *   type URIChoicePattern == Map*(EntitKind!,URISimplePattern!)!
 *   type URISimplePattern == string (with URIVariables)
 *   type URIVariable == '${id}' | '${type}' 
 *   
 */
class ERGraphAsRDF {
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
   * Compute the URI of a given entity according to a pattern
   * @param URIPattern! $uriPattern a pattern used 
   * @param ERGraph! $graph  The graph which contains the entity in case some of its attributes should be consulted
   * @param EntityKind! $entityKind The type of the entity.
   * @param EntityId! $entityId The id of the entity in the ERGraph.
   * @return URI the uri of the entity
   */
  public function getEntityURI($uriPattern, ERGraph $graph,$entityKind, $entityId) {
    if (is_string($uriPattern)) {
      $uri = $uriPattern ;
    } else {
      $uri = $uriPattern[$entityKind] ;
    }
 //   $graph->DATA[$entity]
    $uri=str_replace('${type}',$entityKind,$uri) ;
    $uri=str_replace('${id}',$entityId,$uri) ;
    return $uri ;
  }
  
  /**
   * Add the given graph to the triple set using the prefixes specified.
   * @param ERGraph! $graph
   * @param URI!|Map(EntityKind!,URI!)! $dataprefixes Prefix(es) to build URIs for
   * entities. If $dataprefixes is a string, then all kinds of entities will use
   * the same prefix. Otherwise a prefix should be given for each entity kind in
   * the form of a Map(EntityKind!,URI!)! 
   * @param URI! $schemaprefix This prefix will be added in the context
   * type declaration of entities to create an URI for the type of entity.
   * For instance a type of entity 'feature' will be converted to
   * http://data.mydomain.org/schema#feature if $schemaprefix has the 
   * value http://data.mydomain.org/schema# 
   * @return void 
   */
  public function addERGraph(ERGraph $graph,$dataURIPattern,$schemaprefix) {
    
    // set the default schema prefix.
    // This is the same for all types
    $this->tripleSet->currentSchemaPrefix = $schemaprefix ;
    $this->tripleSet->getConfiguration()->addPrefix('schema',$schemaprefix) ;
    
    // for each entity type extension
    foreach($graph->DATA as $entitykind => $mapofentities) {
            
      $attributes=$graph->SCHEMA->getAttributeDescriptions($entitykind) ;
      foreach ($mapofentities as $entityid => $map ) {
        
        // compute the uri of the entity
        $entityuri = $this->getEntityURI($dataURIPattern, $graph, $entitykind, $entityid ) ;
        // define the type of the entity
        $this->tripleSet->addTriple('type',$entityuri,'rdf:type',$entitykind) ;
        
        // define data or links triple for each attribbute
        foreach ($attributes as $attributename => $attributeinfo) {
          switch ($attributeinfo['tag']) {
            case '@':
            case '!':
            case '?':
              if (isset($map[$attributename])) {
                $value=$map[$attributename] ;
                $this->tripleSet->addTriple(
                    'data',$entityuri,$attributename,$value) ;
              }
              break ;
            case '*':
              if (isset($map[$attributename])) {
                foreach ($map[$attributename] as $reference) {
                  if (! $graph->isReference($reference)) {
                    die("ERGraphAsRDF.addERGraph: the value of"
                        ." $addERGraph($entityuri).$attributename is not a reference") ; 
                  }
                  $targetid = $graph->getReferenceKey($reference) ;
                  $targettype = $graph->getReferenceType($reference) ;
                  $targetentityuri = $this->getEntityURI($dataURIPattern,$graph,$targettype,$targetid) ;
                  $this->tripleSet->addTriple(
                      'link',$entityuri,$attributename,$targetentityuri) ;
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
   * Save the triple set created with the graph in a rdf store
   * @param RDFStore! $rdfstore the store to save the triples in
   * @param URI! $namedgraph the name of the graph
   * @return the number of triples added.
   */
  public function save($rdfstore,$namedgraph){
    $n = $rdfstore->loadTripleSet($this->tripleSet,$namedgraph,false) ;
    return $n ;
  }
  /**
   * Create a converter of ERGraph to RDF. 
   */
  public function __construct() {
    $this->tripleSet = new RDFTripleSet() ;
  }
}
