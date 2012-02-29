<?php
/**
 * RDF support. Provides helpers for arc2 rdf store.
 */

/**
 * See the content of the file below to configure your system and use this library.
 */
require_once 'config/configRDF.php' ;
require_once 'Database.php' ;
require_once 'Graph.php' ;


/**
 * Helper class to create arc2 triples as defined in https://github.com/semsol/arc2/wiki/Internal-Structures .
 * This class helps in the burden of creating arc2 RDF triples with 
 * always specifying prefixes, datatypes, etc.  
 * with default current values for both data prefix and schema prefix.
 * These are public properties that can be accessed directly.
 * This class also provide a method to load a document at once (load)
 * as well as some helper functions.
 * 
 * Optionaly if a RDFConfiguration is given, its list of prefixes is used 
 * for short display. So far there is no support for using other elements
 * from the configuration (and it is not clear whether this would help).
 * 
 * In arc2 triples are represented as the php structure specified below.
 *
 * @See https://github.com/semsol/arc2/wiki/Internal-Structures for the 
 * documentation of triple internal structure.
 * 
 * type ResourceKind == 'uri'|'bnode'|'var' 
 * type ItemKind == ResourceKind | 'literal'
 * 
 * type RDFTriple == Map{ 
 *    's'          : String!  // the subject value (a URI, Bnode ID, or Variable)
 *    's_type'     : ResourceType!
 *    'p'          : String!  // the property URI (or a Variable)
 *    'o'          : String!  // the subject value (see below)
 *    'o_type'     : ItemKind!
 *    'o_datatype' : URI?
 *    'o_lang'     : String?   // a language identifier, e.g. ("en-us")
 *   }
 */   
class RDFTripleSet {

  /**
   * @var Set*(RDFTriple!)!
   * The resulting triples. This property can be manipulated directly
   * if necessary, but a priori it should be just read. 
   */
  public $triples ;
  
  /**
   * @var RDFConfiguration! A configuration used for its prefixes.
   */
  public $rdfConfiguration ;
  
  
  /*-------------------------------------------------------------------------
   *  Bulk interface.
  *-------------------------------------------------------------------------
  */
  
  /**
   * Empty the set of triples.
   */
  public function reset() {
    $this->triples = array() ;
  }
  
  /**
   * Load a document (after resetting the triple set).
   * @param URL corresponding to a local file or a remove file.
   * @see ARC2::getRDFParser()->parse for supported format.
   * @return the number of elements loaded or false if an error happened.
   */
  public function load($url) {
    $this->reset() ;
    $parser = ARC2::getRDFParser();
    $parser->parse($url);
    if ($parser->getErrors()) {
      return false ;
    } else {
      $this->triples = $parser->getTriples() ;
      return count($this->triples);
    }
  }

  /**
   * Serialize the triple set in a given format. Return the resulting string or save the result into a file if 
   * the $filename parameter is given. In this case return the number of byte written.
   * @param 'HTML'|'GraphML'|'NTriples'|'Turtle'|'RDFXML'|'RDFJSON'|'MicroRDF'|'POSHRDF'|'RSS10' $format the serialization format
   * @param String? $filename The file in which to save the result or null.
   * @return Integer|false If no filename is specified return the string generated. 
   * Otherwise return either the number of byte written or false in case of an error.
   */
  public function save($format,$filename=null) { 
    switch ($format) {
      case 'HTML':
        $document = $this->toHTML() ;
        break ;
      case 'GraphML' : 
        $gmlizer = new RDFAsGraphml() ;
        $document = $gmlizer->rdfTripleSetAsGraphml($this) ;
        break ;
      default :
        $serializer = ARC2::getSer($format,$this->rdfConfiguration->getARC2Config()) ;
        $document = $serializer->getSerializedTriples($this->triples) ;
    }
    if (isset($filename)) {
      return file_put_contents($filename, $document) ;
    } else {
      return $document ;
    }
  }
  
  /*-------------------------------------------------------------------------
   *  Incremental interface. 
   *-------------------------------------------------------------------------
   * The methods and fields below allow to add easily new triples without 
   * the burden of adding prefixes, dealing with annoying characters, etc.
   * It also provides means to add triples from various structures.
   * Interesting functions are
   *    - addTriple
   *    - addArrayAsTriples
   *    - addMapAsTriples
   */
  
  /**
   * @var String? current schema prefix for the ontology.
   * This property can be set att will at any moment and it will
   * be used for subsequent triple additions. It can also be set to null.
   * Not that this prefix will be added only if the added value is not
   * already prefixed. See makeURI function.
   */
  public $currentSchemaPrefix ;
  /**
   * @var String? current prefix for data object.
   * This property can be set att will at any moment and it will
   * be used for subsequent triple additions. It can also be set to null.
   * Not that this prefix will be added only if the added value is not
   * already prefixed. See makeURI function.
   */
  public $currentDataPrefix ;
  
  
  /**
   * @param String! $string
   * @return Boolean!
   */
  public function isFullURI($string) {
    return preg_match('/^[a-z0-9A-Z]+:\/\//',$string) !=0 ;
  }
  
  /**
   * Indicates if the string contains a ':' and is not a full uri.
   * There is no check currently with respect to the prefixes in the configuration.
   * @param String! $string
   * @return Boolean!
   */
  public function isPrefixedName($string) {
    return !$this->isFullURI($string) && (strpos($string,':')!== false) ;
  }
  
  
  /**
   * Create a prefix either for 'data' or 'schema' or if a string is provideo
   * adds a ':' at the end if this is not an URI and there is no ':'
   * There is no check currently with respect to the prefixes in the configuration.
   * @param 'data'|'schema'|String! $kindOrPrefix
   * @return String!
   */
  protected function makePrefix($kindOrPrefix) {
    switch ($kindOrPrefix) {
      case 'data':
        return $this->currentDataPrefix ;
        break ;
      case 'schema':
        return $this->currentSchemaPrefix ;
        break ;
      default :
        if (isFullURI($kindOrPrefix)) {
        return $kindOrPrefix ;
      } elseif (substr($kindOrPrefix,-1,1) == ':') {
        return $kindOrPrefix ;
      } else {
        return $kindOrPrefix.':' ;
      }
    }
  }
  
  /**
   * Replace annoying characters for URI by some _
   * @param unknown_type $string
   * @return String!
   */
  protected function makeStringForURI($string) {
    return strtr(strtolower($string),' .,!?;@-+','_________') ;
  }
  
  // if the string is a URI then returns it as is
  // if the string contains a : it is assumed that it is already prefixed
  // otherwise add the prefix to it and convert illegal characters
  /**
   * Add a prefix to the given URI only if the URI is not already prefixed.
   * There is no check currently with respect to the prefixes in the configuration.
   * @param String! $string
   * @param 'data'|'schema'|String! $kindOrPrefix
   * @return URI! a prefixed uri
   */
  protected function makeURI($string,$kindOrPrefix){
    if ($this->isFullURI($string)
        || (!isset($kindOrPrefixOrNull) && $this->isPrefixedName($string))) {
      return $string ;
    } else {
      return $this->makePrefix($kindOrPrefix)
      . $this->makeStringForURI($string) ;
    }
  }
  
  /**
   * Internal method to factor the creation of one part of the triple.
   * The triple will be completed later.
   * @param unknown_type $source
   * @param unknown_type $predicate
   */
  protected function _makePartialTriple($source,$property) {
    $triple = array() ;
    $triple['s'] = $this->makeURI($stource,'data') ;
    $triple['s_type'] = 'uri' ;
    $triple['p'] = $this->makeURI($property,'schema') ;
  }

  /**
   * Add a triple of a given kind (data, link or type).
   * Link types must have type predicate as predicate.
   * TODO add support for indicating the Datatype, and language
   * TODO add support to infer the type from the value
   * @param 'data'|'link'|'type' $triplekind
   * @param String! $source
   * @param String! $predicate
   * @param String! $value
   */
  public function addTriple($triplekind,$source,$predicate,$value) {
    // source
    $triple = array() ;
    $triple['s'] = $this->makeURI($source,'data') ;
    $triple['s_type'] = 'uri' ;

    // predicate
    $triple['p'] = $this->makeURI($predicate,'schema') ;

    // target
    switch ($triplekind) {
      case 'data' :
        $triple['o'] = $value ;
        $triple['o_type'] = 'literal' ;
        break ;
      case 'link' :
        $triple['o'] = $this->makeURI($value,'data') ;
        $triple['o_type'] = 'uri' ;
        break ;
      case 'type' :
        assert('$this->rdfConfiguration->isTypePredicate($predicate)') ;
        $triple['o'] = $this->makeURI($value,'schema') ;
        $triple['o_type'] = 'uri' ;
        break ;
      default:
        assert(false) ;
    }
    $this->triples[] = $triple ;
  }

  /**
   * Add in batch a set of triples that differ only by the values.
   * This basically corresponds to the ',' notation in the turtle language.
   * @param 'data'|'link'|'type' $triplekind
   * @param String! $source
   * @param String! $predicate
   * @param Set!<String!>! $array
   */
  public function addArrayAsTriples($triplekind,$source,$predicate,$array) {
    foreach( $array as $value) {
      $this->addTriple($triplekind,$source,$predicate,$array) ;
    }
  }

  /**
   * Add in batch a set of triples from a map of values.
   * This basically corresponds to the ';' notation in the turtle language.
   * TODO add support for values as array as well as scalar
   * TODO add a function for the heterogeneous triplekind
   * @param 'data'|'link'|'type' $triplekind
   * @param String! $source
   * @param unknown_type $map
   */
  public function addMapAsTriples($triplekind,$source,$map) {
    foreach($map as $property => $value) {
      $this->addTriple($triplekind,$source,$property,$value) ;
    }
  }
  
  
  /*-------------------------------------------------------------------------
   *  Conversion to HTML.
   *-------------------------------------------------------------------------
   */  
  
  /**
   * Generate either a link for uri or a simple value.
   * @param Any $item
   * @param ItemKind! $type
   */
  public function itemToHTML($item,$kind) {
    switch ($kind) {
      case 'uri':
        $shorturl = $this->rdfConfiguration->prefixed($item) ;
        return 
          '<a class="rdfuri" href="'.$item.'">'
          .$shorturl.'</a>' ;
        break ;
      case 'literal':
        return $item ;
        break ;
      default:
        return $item ;
    }
  }
  
  /**
   * Generate an table.
   * @return HTML! the triples represented as a table
   */
  public function toHTML() {
    $table=array() ;
    foreach($this->triples as $triple) {
      $row['s'] = $this->itemToHTML($triple['s'],$triple['s_type']) ;
      $row['p'] = $this->itemToHTML($triple['p'],'uri') ;
      $row['o'] = $this->itemToHTML($triple['o'],$triple['o_type']) ;
      $table[] = $row;
    }
    return homoArrayMapToHTMLTable($table) ;
  }

  
  
  
  /**
   * FIXME check what to do if no prefix are given as parameters. This may provoke errors if used.
   * @param String? $dataPrefix
   * @param String? $schemaPrefix
   * @param RDFConfiguration? $configuration  a RDFConfiguration used for its prefixes.
   */
  public function __construct($dataPrefix=null,$schemaPrefix=null,$configuration=null) {
    $this->currentDataPrefix = $dataPrefix ;
    $this->currentSchemaPrefix = $schemaPrefix ;
    $this->triples = array() ;
    if (isset($configuration)) {
      $this->rdfConfiguration = $configuration ;
    } else  {
      $this->rdfConfiguration = RDFConfiguration::getDefault() ;
    }
  }
}




/**
 * Wrapper for an arc2 configuration but contains as well convienience method to
 * deal with uri and prefixes. 
 * This is the root of a hierarchy which makes it easier to understand which parameters 
 * in the configuration should be set. This class provide the most simplified one.
 */
class RDFConfiguration {
  
  /**
   * @var RDFConfiguration? The default configuration instance. Singleton pattern.
   */
  private static $DEFAULT_CONFIGURATION=null ;
  /**
   * Return the default configuration instance. 
   * @return RDFConfiguration the default configuration instance.
   */
  public static function getDefault() {
    if (! isset(RDFConfiguration::$DEFAULT_CONFIGURATION)) {
      RDFConfiguration::$DEFAULT_CONFIGURATION = new RDFConfiguration() ;
    }
    return RDFConfiguration::$DEFAULT_CONFIGURATION ;
  }

  /**
   * @var Map(String!,Mixed) An ARC configuration is a map with different fields used by ARC2 functions.
   * This field could be used as a parameters for use with the ARC2 API.
   */
  public $arc2config ;
  
  /**
   * @return Map(String!,Mixed) The arc2 configuration map for in the ARC2 API.
   */
  public function getARC2Config() {
    return $this->arc2config ;
  }


  /**
   * @var Set of property that serves as a type. 
   * Includes rdf:type as well as full urls. 
   */
  public $RDF_TYPE_PREDICATES = array(
      'rdf:type',
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') ;
  
  /**
   * Return
   * @param URI $predicate
   */
  public function isTypePredicate($predicate) {
    return array_search($predicate,$this->RDF_TYPE_PREDICATES)!==false ;
  }
    
  /**
   * Return the domain part of an uri
   * @param URI $uri
   * @return String domain (e.g. www.schema.org)
   */
  public function domain($uri) {
    return parse_url($uri,PHP_URL_HOST) ;
  }
  
  /**
   * Return the set of domains from a list of uris
   * @param List*(URI!)! $uris
   * @return Set*(String!)! set of domains
   */
  public function domains($uris) {
    $domains = array() ;
    foreach ($uris as $uri) {
      $domain = $this->domain($uris) ;
      if (!in_array($domain,$domains)) {
        $domains[] = $domain ;
      }
    }
    return $domains ;
  }
  
  /**
   * Return the short part of the url (after # or the last /)
   * @param URI $uri
   * @return string the segment after # or the last /
   */
  public function shortname($uri) {
    $pos = strpos($uri,'#') ;
    if ($pos===false) {
      $pos = strrpos($uri,'/') ;
    }
    assert('$pos!==false') ;
    return substr($uri,$pos+1) ;
  }
  
  /**
   * Return the base of the url (before # or the last / included)
   * @param URI $uri
   * @return URI the path before # or the last / included
   */
  public function base($uri) {
    if (strpos($uri,'#')===false) {
      $pos = strrpos($uri,'/') ;
      return substr($uri,0,$pos+1) ;
    } else {
      $pos = strrpos($uri,'#') ;
      return substr($uri,0,$pos+1) ;
    }
  }
  
  /**
   * @param unknown_type $uris
   * @return multitype:Ambigous <string, mixed>
   */
  public function bases($uris) {
    $bases = array() ;
    foreach ($uris as $uri) {
      $base = RDFDefinitions::base($uris) ;
      if (!in_array($base,$bases)) {
        $bases[] = $base ;
      }
    }
    return $domains ;
  }
  
  /**
   * Return the shortened URI if it is corresponds to a prefix or the same URI otherwise
   * @param unknown_type $uri
   * @return String A string of the form <prefix>:<segment> or a full uri
   */
  public function prefixed($uri) {
    $r = array_search($this->base($uri),$this->arc2config['ns']) ;
    if ($r===false) {
      return $uri ;
    } else {
      return $r.':'.$this->shortname($uri) ;
    }
  }
  
  /**
   * Add a prefix to the list of prefixes. Ignore this statement if the prefix
   * is already defined (even with another value).
   * @param String $prefix The prefix without : (for instance "rdf")
   * @param URI The full uri corresponding to the prefix
   * @return void
   */
  public function addPrefix($prefix,$url) {
    if (array_search($prefix,$this->arc2config['ns'])===false) {
      $this->arc2config['ns'] = $prefixes ;
    }
  }
  
  /**
   * Return the map of all prefixes.
   * @return Map(String!,URI!)!
   */
  public function getPrefixes() {
    return $this->arc2config['ns'] ;
  }
  
  /**
   * @param Map*(String!,URI!)? $additionalPrefixes A list of prefix to define (without :). 
   * Default to an empty array.  xsd,rdf,rdfs,owl  are always defined.
   */
  public function __construct($additionalPrefixes=array()) {
    $this->arc2config = array() ;
  
    /* stop after 100 errors */
    $this->arc2config['max_errors'] = 100 ;
    
    // Compute the list of prefixes available
    $defaultprefixes = array(
        'rdf'       => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs'      => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl'       => 'http://www.w3.org/2002/07/owl#',
        'xsd'       => 'http://www.w3.org/2001/XMLSchema#',
        'swc'       => 'http://data.semanticweb.org/ns/swc/ontology#',
        'swrc'      => 'http://swrc.ontoware.org/ontology#',
        'foaf'      => 'http://xmlns.com/foaf/0.1/',
        'ical'      => 'http://www.w3.org/2002/12/cal/ical#',
        'dc'        => 'http://purl.org/dc/elements/1.1/',
        'dbpedia'   => 'http://dbpedia.org/resource/',
        'sdgperson' => 'http://data.semanticweb.org/person/',
        'sdgorg'    => 'http://data.semanticweb.org/organization/'        
    ) ;
    $prefixes = array_merge($defaultprefixes,$additionalPrefixes) ;
    $this->arc2config['ns'] = $prefixes ;
  }
}









  
/**
 * Configuration suitable for a RDF Store and Sparql Endpoint.
 */
class RDFStoreConfiguration extends RDFConfiguration {
  
  /**
   * @var SparqlFeatures! Readonly features list.
   * Can be used as parameter to the constructor in this class.
   */
  const SPARQL_R_FEATURES   = 'select construct ask describe dump' ;

  /**
   * @var SparqlFeatures! Readonly features list
   * Can be used as a parameter to the constructor in this class.
   */
  const SPARQL_RW_FEATURES  = 'select construct ask describe dump load insert' ;

  /**
   * @var SparqlFeatures!
    * Can be used as a parameter to the constructor in this class.
  */
  const SPARQL_RWD_FEATURES = 'select construct ask describe dump load insert delete' ;
  
  /**
   * Create a configuration suitable for a RDF store.
   * @param Map*(String!,URI!)? $additionalPrefixes A list of prefix to define (without :).
   * Default to an empty array.  xsd,rdf,rdfs,owl  are always defined.
   * @param DatabaseAccount! $dbaccount  Database account for a RDF Store.
   * @param String! $storename Name of a storein $dbaccount.
   * @param String? $sparql_features 
   * A list of features if the store is to be used as a sparql endpoint.
   * See the constants SPARQL_xxx to see how to specify these features. One of
   * the predefined constants can be used or the list of feature can be specified
   * via a string with one space as a separator.
   * Default to the read features (constant SPARQL_R_FEATURES).
   * @param String? $sparql_read_key Key for using the sparql endpoint in read mode.
   * No key by default so read operations are allowed. 
   * @param String? $sparql_write_key Key for using the read/write endpoint.
   * A key is defined by default (see in the code) but it is safer to define one if 
   * you need it.
   */
  public function __construct(
      $additionalPrefixes=array(),
      $dbaccount, 
      $storename,
      $sparql_features=self::SPARQL_R_FEATURES,
      $sparql_read_key='',
      $sparql_write_key='dowrite') {
  
    parent::__construct($additionalPrefixes) ;
    
    $this->arc2config['db_host'] = $dbaccount->hostname ;
    $this->arc2config['db_name'] = $dbaccount->dbname ;
    $this->arc2config['db_user'] = $dbaccount->username ;
    $this->arc2config['db_pwd']   = $dbaccount->password ;
    $this->arc2config['store_name'] = $storename ;
      
    // necessary if using the RDF store as a sparql endpoint
    // otherwise these parameters are not used
    $this->arc2config['endpoint_read_key'] = $sparql_read_key ;
    $this->arc2config['endpoint_write_key'] = $sparql_write_key ;
    $this->arc2config['endpoint_features'] = explode(' ',$sparql_features) ;
    $this->arc2config['endpoint_timeout'] = 60 ; /* not implemented in ARC2 preview */
  }
}

  


  
/**
 * A RDF store with higher level functions than those provided by arc2. 
 * This includes:
 *   - some helpers
 *   - transparent initialization
 *   - logging and basic error handling
 *   - a "current resource" which can act as a placeholder for simpler actions
 *   - easy queries
 *   - direct support for sparql endpoint
 *   - a little language to evaluation expressions
 */
class RDFStore {
    
  protected /*Logger!*/        $logger ;        /* a logger where to trace warning and errors */

  // arc2 stuff
  protected /*RDFStoreConfiguration!*/  $configuration ;  /* The configuration for ARC2 library */
  protected /*ARC2_Store!*/    $arc2store ;    /* The RDF store containing all information */
  protected /*ARC2_Resource!*/ $currentResource ;    /* Used as a placeholder to access to ressource */
  
  
  /**
   * Return the wrapped arc2 rdf store to allow direct interfaction with it.
   * This provides a way to have direct access to the ARC2 library if features
   * provided by this class are not enough.
   * @return ARC2_Store!
   */
  public function getARC2Store() {
    return $this->arc2store ;
  }

  //-------------------------------------------------------------------------------
  // Basic logging and error handling support
  //-------------------------------------------------------------------------------
  
  protected function log($msg) {
    $this->logger->log($msg) ;
  }

  protected function checkErrors( $msg, $die = true) {
    // check if there are some errors in the store or in the current resource
    $errs = $this->getARC2Store()->getErrors() ;
    if (! $errs && isset($this->currentResource)) {
      $errs = $this->currentResource->getErrors() ;
    }
    if ($errs) {
      $msg = "<b>RDFStore::checkErrors - ERROR:</b>$msg (from ARC2)<br/><ul>" ;
      foreach ($errs as $err) {
        $msg .= "<li>".$err."</li>" ;
      }
      $msg .= "</ul>" ;
      $this->log($msg) ;
      ! $die || die("RDFStore::checkErrors - Fatal error (see log for details)") ;
    }
  }
  
  
  //-------------------------------------------------------------------------------
  // Support for Basic Querying
  //-------------------------------------------------------------------------------
  
  /**
   * Execute a 'select' query and returns selected rows. 
   * @see https://github.com/semsol/arc2/wiki/Using-ARC%27s-RDF-Store
   * @param String! $query A 'select' query.
   * @return List*(String*,String*>)! rows
   */
  public function selectQuery($query) {
    $this->log('RDFStore:executeQuery '.$query) ;
    $rows = $this->getARC2Store()->query($query, 'rows') ;
    $this->checkErrors("Error executing SPARQL query $query") ;
    return $rows ;
  }
  
  /**
   * Return the value selected by a sparql query. The query must return
   * only one row and the variable is selected by the second parameter.
   * @param String! $query A select query returning only one row.
   * @param String! $variablename A variable name that appear in the result.
   * @return Mixed $value
   */
  public function selectTheValue($query,$variablename) {
    $rows = $this->selectQuery($query) ;
    assert('count($rows)==1') ;
    return $rows[0][$variablename] ;
  }
  
  /**
   * Return the list of values in the column produced by a select query.
   * @param String! $query A select query.
   * @param String! $variablename A variable name that appear in the result.
   * @param Boolean? $distinct Indicates whether to remove duplicates or not.
   * No duplicate removal by default.
   * @param Mixed? default value
   * @return List*(Mixed) $value The list of values corresponding to the variable.
   */
  public function selectTheColumnValues(
      $query,
      $variablename,
      $distinct=false) {
    $rows = $this->selectQuery($query) ;
    return columnValuesFromArrayMap($rows,$variablename,$distinct) ;
  }

  /**
   * Execute a 'ask' query and returns a boolean value.
   * @param String! $query A 'ask' query.
   * @see https://github.com/semsol/arc2/wiki/Using-ARC%27s-RDF-Store
   * @return Boolean!
   */
  public function askQuery($query) {
    $this->log('RDFStore:executeQuery '.$query) ;
    $result = $this->getARC2Store()->query($query, 'raw') ;
    $this->checkErrors("Error executing SPARQL query $query") ;
    return ($result?true:false) ;
  }
     
  /**
   * Check for the existence of a triplet in the RDF store
   * @param RDFId! $subject
   * @param RDFId! $predicate
   * @param RDFId! $object
   * @return Boolean
   */
  public function isItFact($subject,$predicate,$object ) {
    return $this->askQuery('ASK { '.$subject.' '.$predicate.' '.$object.' }') ;
  }
  
  /**
   * Check if an object is explicitely declared as a given type.
   * No inference is done. Just look for the existence of a "type" predicates. 
   * @param RDFId! $subject
   * @param RDFId! $type
   * @return boolean
   */
  public function /*boolean*/ isOfType($subject,$type ) {
    foreach (RDFDefinitions::$RDF_TYPE_PREDICATES as $rdftypepredicates) {
      if ($this->isItFact($subject,'$rdftypepredicates',$type)!==false) {
        return true ;
      }
    }
    return false ; 
  }
  

  //-------------------------------------------------------------------------------
  // Support for updates
  //-------------------------------------------------------------------------------
  
  /**
   * Execute a 'load' or 'insert' or 'delete' query and return the number 
   * of triples added/deleted.
   * @param String! $query A 'load' or 'insert' or 'deleted' query. 
   * @see https://github.com/semsol/arc2/wiki/SPARQL%2B
   * @return Integer! The number of triples added.
   */
  protected function loadOrInsertOrDeleteQuery($query) {
    $this->log('RDFStore:executeQuery '.$query) ;
    $rs = $this->getARC2Store()->query($query) ;
    $this->checkErrors("Error executing SPARQL query $query") ;
    return $rs['result']['t_count'] ;
  }

  /**
   * Execute a 'load' query and return the number of triples added.
   * @param String! $query A 'load' query.
   * @see https://github.com/semsol/arc2/wiki/SPARQL%2B
   * @return Integer! The number of triples added.
   */
  public function loadQuery($query) {
    return $this->loadOrInsertOrDeleteQuery($query) ;
  }
  
  /**
   * Load a document from the web.
   * @param URL! $url
   * @return Integer! The number of triples added.
   */
  public function load($url) {
    return $this->loadQuery('LOAD <'.$url.'>') ;
  }  

  /**
   * Load an RDFTripleSet into the store and reset the TripleSet (default).
   * @param RDFTripleSet! $tripleSet The triple set to load.
   * @param URI! $graphURI The target named graph where to put the triples.
   * @param Boolean $resetTripleSet Should the triple set be emptied. 
   * True by default.
   * @return Integer! The number of triples added.
   */
  public function loadTripleSet(RDFTripleSet $tripleSet,$graphURI,$emptyTripleSet=true) {
    $result=$this->arc2store->insert($tripleSet->triples,$graphURI) ;
    if ($emptyTripleSet) {
      $tripleSet->triples = array() ;
    }
    return $result['t_count'] ;
  }
  
  /**
   * Execute a 'insert' query and return the number of triples added.
   * @param String! $query An 'insert' query.
   * @see https://github.com/semsol/arc2/wiki/SPARQL%2B
   * @return Integer! The number of triples added.
   */
  public function insertQuery($query) {
    return $this->loadOrInsertOrDeleteQuery($query) ;
  }
  
  
  /**
   * Execute a 'delete' query and return the number of triples deleted.
   * @param String! $query An 'delete' query.
   * @see https://github.com/semsol/arc2/wiki/SPARQL%2B
   * @return Integer! The number of triples deleted.
   */
  public function deleteQuery($query) {
    return $this->loadOrInsertOrDeleteQuery($query) ;
  }
  
    
  /**
   * Remove all the content of the store.
   * @return void 
   */
  public function reset() {
    $this->getARC2Store()->reset() ;
  }
  
  
  
  
  
  
  
  //-------------------------------------------------------------------------------
  // Support for PropertyExpression
  //-------------------------------------------------------------------------------
  //
  // <PropertyExpression> ::=
  //     [ '~' ] <PropertyName> [ '?' | '!' | '*' | '+' ]
  //
  //
  // type PropertyDescription = Map{
  //     'property'  : String!
  //     'inverse'   : Boolean!
  //     'card'      : ('?'|'!'|'*'|'+')?
  //     'optional"  : Boolean?
  //     'multiple'  : Boolean?
  //   }
  /**
   * @param ProperyExpression! $pexpr
   * @return PropertyDescription! 
   */
  protected function parsePropertyExpression($pexpr){
    $result = array() ;
  
    $firstchar = substr($pexpr,0,1) ;
    $lastchar = substr($pexpr,-1,1) ;
    $result['inverse'] = ($firstchar=='~') ;
    if ($result['inverse']) {
      $pexpr = substr($pexpr,1) ;
    }
    switch ($lastchar) {
      case '?':
        $result['optional'] = true ;
        $result['multiple'] = false ;
        $result['card'] = '?' ;
        break ;
      case '!':
        $result['optional'] = false ;
        $result['multiple'] = false ;
        $result['card'] = '!' ;
        break ;
      case '*':
        $result['optional'] = true ;
        $result['multiple'] = true ;
        $result['card'] = '*' ;
        break ;
      case '+':
        $result['optional'] = false ;
        $result['multiple'] = true ;
        $result['card'] = '+' ;
        break ;
    }
    if (isset($result['card'])) {
      $pexpr = substr($pexpr,0,strlen($pexpr)-1) ;
    }  
    assert(strlen($pexpr)>=3) ;
      $result['property']=$pexpr ;
    return $result ;
  }

  // Parse a property set expression, that is a sequence of PropertyExpression separated by some spaces
  // return a map of description, the first element being the property expression
  protected function /*Map*<PropertyExpression!,PropertyDescription!>!*/ parsePropertySetExpression(
    /*PropertySetExpression*/ $psexpr){
    $result = array() ;
    /*List*<PropertyExpression!>!*/ $properties = explode(' ',$psexpr) ;
    foreach ($properties as $pexpr) {
      if (strlen($pexpr)>=1) {
        $pdescr = $this->parsePropertyExpression($pexpr) ;
        $result[$pexpr] = $pdescr ;
      }
    }
    return $result ;
  }



  
  // TODO the syntax should be merged/unified with the SimpleGraph schema format
  // The value returned depends on the cardinality
  //   prop?  => String?
  //   prop!  => String!
  //   prop*  => Set*<String!>!
  //   prop+  => Set+<String!>!
  //   prop   => Set*<String!>!
  // PropertyValue ::= String! | Set*<String!>!
  public function /*PropertyValue?*/evalPropertyExpression( 
      /*RDFId*/ $objecturi,
      /*ProperyExpression*/ $pexpr ) {
    /*PropertyDescription!*/ $propdescr = $this->parsePropertyExpression($pexpr) ;
    // build the query according to the fact that the property is direct or inverse
    if ($propdescr['inverse']) {
      $query = 'SELECT DISTINCT ?x WHERE { ?x '.$propdescr['property'].' '.$objecturi.' }' ;
    } else {
      $query = 'SELECT DISTINCT ?x WHERE { '.$objecturi.' '.$propdescr['property'].' ?x }' ;
    }
    $rows = $this->selectQuery($query) ;
    if (count($rows) == 0) {
      // the result is empty
  
      if (isset($propdescr['card']) && !$propdescr['optional']) {
        // the property has been explicitely defined as not-optional. Fail
        die("The expression $pexpr($objecturi) do not return any value") ;
  
      } elseif (isset($propdescr['card']) && $propdescr['optional']) {
        return $propdescr['multiple'] ? array() : NULL ;
  
      } else {
        // the cardinality of the property is not specified, returns always an array
        return array() ;
      }
    } elseif (count($rows)==1 && isset($propdescr['card']) && !$propdescr['multiple']) {
  
      // there is one result and the property has been specified as single
      // this is ok, return this very single value
      // 'x' is the variable used in the sparql query
      return $rows[0]['x'] ;
  
    } elseif (count($rows)>=2 && isset($propdescr['card']) && !$propdescr['multiple'] ) {
  
      // various values have been found, but the property has been declared as single
      // log a warning and return the sigle value
      // 'x' is the variable used in the sparql query
      $this->log("The expression $pexpr($objecturi) returns more than one value") ;
      return $rows[0]['x'] ;
    } else {
      $result = array() ;
      foreach ($rows as $row) {
        // 'x' is the variable used in the sparql query
        $result[] = $row['x'] ;
      }
      return $result ;
    }
  }

  // die if the object is not existing or one of the property isn't correct
  public function /*Map*<PropertyExpression!,PropertyValue!>!*/doEvalPropertySetExpression(
      /*RDFId*/ $objectrdfid,
      /*ProperySetExpression*/ $psexpr ) {
    /*Map*<PropertyExpression!,PropertyDescription!>!*/ $propdescrmap =
    $this->parsePropertySetExpression($psexpr) ;
    $result=array() ;
    foreach( $propdescrmap as $propexpr => $propdescr) {
      // actually, the property expressions are parse twice, but this is not so important
      $r = $this->evalPropertyExpression($objectrdfid,$propexpr) ;
      // optional attributes that has null value, are not put in the resulting map
      if ($r!=NULL) {
        $result[$propexpr] = $r ;
      }
    }
    return $result ;
  }

  // check first if the object is of the specified type, and if this is the case
  // eval the set of propery expression
  // return NULL if there is no object of this type. Die if a property is not correct.
  public function /*Map*<PropertyExpression!,PropertyValue!>?*/ tryEvalPropertySetExpression( 
      /*RDFId*/ $objectrdfid,
      /*RDFId*/ $typerdfid,
      /*ProperySetExpression*/ $psexpr ) {
    if ($this->isOfType($objectrdfid,$typerdfid)) {
      return $this->doEvalPropertySetExpression($objectrdfid,$psexpr) ;
    } else {
      return NULL ;
    }
  }


  //-------------------------------------------------------------------------------
  // Support for SparqlEndpoint
  //-------------------------------------------------------------------------------
  
  /**
   * Start a SPARQL Endpoint
   */
  public function startSparqlEndpoint() {
    $arc2config = $this->configuration->getARC2Config() ;
    $ep = ARC2::getStoreEndpoint($arc2config);
    if (!$ep->isSetUp()) {
      $ep->setUp(); /* create MySQL tables */
    }
    $ep->go();
  }
  

  
  //-------------------------------------------------------------------------------
  // Construction and initialization
  //-------------------------------------------------------------------------------
  
  /**
   * Open or create an RDF Store. 
   * @param RDFStoreConfiguration! $configuration
   * @param Logger? $logger An optional existing logger or null if no log should be created.
   */
  public function __construct(RDFStoreConfiguration $configuration,$logfileOrLogger=null) {
    $this->logger = toLogger($logfileOrLogger) ;
    $this->configuration = $configuration ;
    
    // Initialize the RDF Store
    $arc2config = $configuration->getARC2Config() ;
    $this->arc2store = ARC2::getStore($arc2config);
    $this->checkErrors("Cannot get the RDF Store") ;
    if (!$this->arc2store->isSetUp()) {
      $this->arc2store->setUp();
      $this->checkErrors("Cannot set up the RDF Store") ;
    }

    // Create a resource placeholder (see ARC2 wiki)
    // This space will be used by the various methods to access to the store
    $this->currentResource = ARC2::getResource($arc2config) ;
    $this->currentResource->setStore($this->arc2store) ;
  }
}




/**
 * An introspector computing usage of the ontology for a given store.
 * @status earlyDraft
 */
class RDFStoreIntrospector {

  public $QUERIES = array(
      'type_count' => '
SELECT ?type count(?type) AS ?count WHERE {
?x rdf:type ?type
}
GROUP BY ?type
',

      'property_count' => '
SELECT ?property count(?property) AS ?count WHERE {
?x ?property ?y
}
GROUP BY ?property
',

      'property_sourcetype_rangetype' => '
SELECT DISTINCT ?property ?sourcetype ?rangetype WHERE {
?x ?property ?y.
?x rdf:type ?sourcetype  .
?y rdf:type ?rangetype .
}
' ) ;

  /**
   * @var The rdf store that is introspected
   */
  public $rdfstore ;

  public function /*arrayMap*/ introspect($queryName) {
    return $this->rdfstore->selectQuery($this->QUERIES[$queryName]) ;
  }
  
  public function __construct($rdfstore) {
    $this->rdfstore = $rdfstore ;    
  }
}









/**
 * 
 */
class RDFAsGraphml {
  
  /**
   * @var RDFConfiguration 
   */
  protected $rdfConfiguration ;
  
  
  /**
   * Return the graphml representation of the triple set either as the result or in a file..
   * @param RDFTripleSet! $tripleset The set of triples.
   * @param String? $filename The file in which to save the result or null.
   * @return GraphmlString|Integer|false If no filename is specified return the string generated.
   * Otherwise return either the number of byte written or false in case of an error.
   */  
  public function rdfTripleSetAsGraphml(RDFTripleSet $tripleset, $filename=null) {
    $this->rdfConfiguration = $tripleset->rdfConfiguration ; 
    return $this->rdfTriplesAsGraphml($tripleset->triples,$filename);
  }
  
  /**
   * Return the graphml representation of the triples either as the result or in a file.
   * @param Set*<RDFTriple!>! $triples The triples.
   * @param String? $filename The file in which to save the result or null.
   * @return GraphmlString|Integer|false If no filename is specified return the string generated.
   * Otherwise return either the number of byte written or false in case of an error.
   */
  public function rdfTriplesAsGraphml($triples,$filename=null) {
    $g = new Graphml() ;
    foreach($triples as $triple) {
      $node1 = $triple['s'] ;
      $node1id = $this->rdfConfiguration->prefixed($node1) ;
      $predicate = $triple['p'] ;
      $predicateid = $this->rdfConfiguration->prefixed($predicate) ;
      $otype = $triple['o_type'] ;
      
      if ($this->rdfConfiguration->isTypePredicate($predicate)) {
        $g->addNode(
            $node1id,
            array('type'=>$this->rdfConfiguration->prefixed($triple['o']) )) ;
        $g->addNode($node1id,array('url'=>$node1)) ;
        
      } elseif ($otype=='literal'){
        $g->addNode($node1id,array($predicateid=>$triple['o'])) ;
        
      } elseif ($otype=='uri' || $otype=='bnode') {
        $node2 = $triple['o'] ;
        $node2id = $this->rdfConfiguration->prefixed($node2) ;
        $g->addNode($node1id,array('url'=>$node1)) ;
        $g->addNode($node2id,array('url'=>$node2)) ;        
        $g->addEdge(
            $node1id,
            $node2id,
            array(
                'type'  => $predicateid,
                'url'   => $predicate ));        
      } else {
        die('unexpected type in triple: '.$otype) ;
      }
    }
    $document = $g->graphToString() ;
    if (isset($filename)) {
      return file_put_contents($filename, $document) ;
    } else {
      return $document ;
    }
  }
  

  
  /**
   * @param RDFconfiguration? $configuration
   */
  public function __construct($configuration=null) {
    if (isset($configuration)) {
      $this->rdfConfiguration = $configuration ;
    } else {
      $this->rdfConfiguration = RDFConfiguration::getDefault() ;
    } 
  }
  
}
