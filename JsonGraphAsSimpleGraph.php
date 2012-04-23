<?php
require_once ABSPATH_MEGALIB.'SimpleGraph.php' ;
require_once ABSPATH_MEGALIB.'SimpleGraphAsRDF.php' ;
require_once ABSPATH_MEGALIB.'HTML.php' ;
require_once ABSPATH_MEGALIB.'Structures.php' ;


/**
 * Convert JSONGraph to a SimpleGraph
 * @param URL! $jsonUrl URL or filename of the JSON file to convert
 * @param String! $schemaFile URL or Filename of the schema use to direct the conversion
 * @param String! $entityJsonMappingFile URL or filename containing the map from entity kind to json
 * tag. This should be a json file.
 * @return SimpleGraph! return a simple graph.
 */
function jsonGraphToSimpleGraph($jsonUrl, $schemaUrl, $entityJsonMappingUrl) {
  if (DEBUG) echo '<h2>jsonGraphToSimpleGraph</h2>' ;

  if (DEBUG>2) echo "<p>Loading the file $jsonUrl, $schemaUrl and $entityJsonMappingUrl</p>" ;

  // load the json file
  $jsonSource = file_get_contents($jsonUrl) ;
  if ($jsonSource===false) {
    die("Cannot open ".$jsonUrl) ;
  }
  $json = json_decode($jsonSource,true) ;
  if (! is_array($json)) {
    if (DEBUG>10) var_dump($json) ;
    die("incorrect json value in $jsonUrl : $jsonSource") ;
  }
  // load the schema file
  $schemaSource = file_get_contents($schemaUrl) ;
  if ($schemaSource === false){
    die("cannot open $schemaUrl") ;
  }
  $schema = new SimpleSchema($schemaSource) ;

  // load the mapping file
  $mappingSource = file_get_contents($entityJsonMappingUrl) ;
  if ($mappingSource === false){
    die("cannot open $entityJsonMappingUrl") ;
  }
  $entityJsonMapping = json_decode($mappingSource,true) ;
  if (! is_array($entityJsonMapping)) {
    if (DEBUG>10) var_dump($entityJsonMapping) ;
    die("incorrect mapping in $entityJsonMappingUrl : $mappingSource") ;
  }

  // create the graph
  $graph = new SimpleGraph($schema) ;
  loadJsonGraphIntoSimpleGraph($graph,$json,$entityJsonMapping) ;

  // checking the constraint on the simple graph (referential constraints)
  if (DEBUG) echo '<h2>Checking constraints</h2>' ;
  $graph->checkConstraints() ;
  return $graph ;
}




// type EntityKind == String!  (entity kinds defined in the SimpleSchema)
// Here feature, language, etc.

/**
 * @param Entity $entityKind
 * @param unknown_type $keyValueOfEntity
 * @return string
 */
function makeEntityId($entityKind,$keyValueOfEntity) {
  return $entityKind.'/'.strtolower($keyValueOfEntity) ;
}

/**
 * Load the information from a json representation into a SimpleGraph structure.
 * @param SimpleGraph! $graph  The target graph where the information will
 * be load. The schema should be already loaded into the graph (that is
 * $graph->SCHEMA should be set).
 * @param JSON! $json the json structure to convert with the top level being
 * a Map(String!,Set*(Entity)) where String is the extension tag (e.g.
 * "implementations" and the result is the extension of the "implementation"
 * entity kind).  See below for the name of the entity kind.
 * @param Map+(EntityKind!,String!)! This map describes the link between
 * the entity kind name in the schema (e.g. "implementation") and the
 * corresponding top-level tag in the json file (e.g."implementations").
 * This is not necessarily simply a "s" at the because of some irregularities in naming
 * such as "categories" => "category".
 */
function loadJsonGraphIntoSimpleGraph($graph,$json,$extensionMap) {
  assert(isset($graph->SCHEMA));
  // for each entity kind defined in the schema, load the corresponding extension
  foreach (  $graph->SCHEMA->getEntityKinds() as $entitykind ) {
    $jsonExtensionTag = $extensionMap[$entitykind] ;
    if (DEBUG>1) echo "<li>Loading '$entitykind' extension from top-level tag '$jsonExtensionTag':<br/>".count($json[$jsonExtensionTag])." json entities found." ;
    loadJsonEntityExtensionIntoSimpleGraph($graph,$json[$jsonExtensionTag],$entitykind) ;
    if (DEBUG>1) echo '</br>'.count($graph->DATA[$entitykind])." ".$entitykind."(s) in the resulting graph</li>" ;
  }
}


/**
 * Extract
 * @param SimpleGraph! $graph The graph object in which to add the instances.
 * The schema of the graph should be already loaded. 
 * The schema defines define what element will be extracted from the json.
 * @param JSON! $jsonExtensionMapOrArray The extension for the given entity kind, represented as
 * an array of entities in which case the key is an attribute (defined in the schema)
 * or a map of entities in which case the key of the map is added as an attribute.
 * @param EntityKind! $entitykind The kind of entities to load.
 */
function loadJsonEntityExtensionIntoSimpleGraph($graph,$jsonExtensionMapOrArray, $entitykind) {
  assert(isset($graph->SCHEMA));
  $schema = $graph->SCHEMA ;

  // get info about attributes of this type of entities
  $attributes = $schema->getAttributeDescriptions($entitykind) ;
  $key_attribute = $schema->getKeyAttribute($entitykind) ;
  if (DEBUG >= 6) {
    echo "expected attributes are " ;
    print_r($attributes) ;
  }
  
  // accept two formats for the extension
  // if the input is a map with key values then change it to an
  // array with the key as an attribute (this is the format used in
  // the remaining of the function).
  if(is_string_map($jsonExtensionMapOrArray)) {
    $jsonExtensionArray = array() ;
    foreach($jsonExtensionMapOrArray as $key => $record) {
      // add the key value as the key attribute
      $record[$key_attribute] = $key ; 
      $jsonExtensionArray[] = $record ;
    }
  } else {
    $jsonExtensionArray = $jsonExtension ;
  }
  
 
  // for each entity in the file
  foreach ($jsonExtensionArray as $entity) {

    // create the record for the entity
    $key_value = $entity[$key_attribute] ;
    $entity_id = makeEntityId($entitykind,$key_value) ;
    $graph->DATA[$entitykind][$entity_id] = array() ;

    // process each attribute in the schema depending on its characteristics
    foreach($attributes as $attribute => $attributeinfo) {
      $type = $attributeinfo['type'] ;
      switch ($attributeinfo['tag']) {
        case '@':
        case '!':
          if (! isset($entity[$attribute])) {
            die("attribute $attribute is not set for $entitykind named $key_value");
          }
          $graph->DATA[$entitykind][$entity_id][$attribute]=$entity[$attribute] ;
          break ;
        case '?':
          if (isset($entity[$attribute])) {
            $graph->DATA[$entitykind][$entity_id][$attribute]=$entity[$attribute] ;
          }
          break ;
        case '*':
          if (isset($entity[$attribute])) {
            $graph->DATA[$entitykind][$entity_id][$attribute]=array() ;
            foreach($entity[$attribute] as $reference) {
              // here we deal with various format of references.
              // simplest form: a reference is just a string
              if (is_string($reference)) {
                $referencedEntityId = makeEntityId($type,$reference) ;
              } elseif (is_array($reference)) {
                // this is an array, so we assume that the name is used as a key.
                // TODO this should be generalized
                $referencedEntityId = makeEntityId($type,$reference["name"]) ;
              } else {
                die("Unexpected reference in json file for the attribute '$attribute' of entity '$entityid' of type '$entitykind'") ;
              }
              // here we eliminate the 'name' level which is boring and useless in the json model
              $graph->DATA[$entitykind][$entity_id][$attribute][]=$referencedEntityId ;
            }
          }
          break ;
        default:
          assert(false) ;
      }
    }
  }
}

;

