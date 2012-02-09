<?php
/**
 * RDF support. Provides helpers for arc2 rdf store.
 */

/**
 * See the content of the file below to configure your system and use this library.
 */
require_once 'config/configRDF.php' ;
require_once 'Database.php' ;

/**
 * Helper class to create arc2 triples as defined in https://github.com/semsol/arc2/wiki/Internal-Structures .
 * This class helps in the burden of creating arc2 RDF triples with 
 * always specifying prefixes, datatypes, etc. 
 * with default current values for both data prefix and schema prefix.
 * This are public properties that can be accessed directly.
 * 
 * In arc2 triples are represented as the php structure specified below.
 *
 * @See https://github.com/semsol/arc2/wiki/Internal-Structures for the 
 * documentation of triple internal structure.
 * 
 * type RDFTriple == Map{ 
 *    's' : String!  // the subject value (a URI, Bnode ID, or Variable)
 *    's_type' : 'uri'|'bnode'|'var'
 *    'p' : String!  // the property URI (or a Variable)
 *    'o' : String!  // the subject value (see below)
 *    'o_type' : 'uri'|'bnode'|'literal'|'var'
 *    'o_datatype' : URI?
 *    'o_lang' : String?   // a language identifier, e.g. ("en-us")
 *   }
 */   
class RDFTripleSet {
  
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
   * @var Set*(RDFTriple!)!
   * The resulting triples. This property can be manipulated directly
   * if necessary, but a priori it should be just read and reset to array()
   */
  public $triples ;
  
  /**
   * @param String! $string
   * @return Boolean!
   */
  public function isFullURI($string) {
    return preg_match('/^[a-z]+:\/\//',$string) !=0 ;
  }
  
  /**
   * @param String! $string
   * @return Boolean!
   */
  public function isPrefixedName($string) {
    return !$this->isFullURI($string) && strpos($string,':') ;
  }


  /**
   * Create a prefix either for 'data' or 'schema' or if a string is provideo
   * adds a ':' at the end if this is not an URI and there is no ':'
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
   * @param unknown_type $source
   * @param unknown_type $predicate
   */
  protected function _makePartialTriple($source,$predicate) {
    $triple = array() ;
    $triple['s'] = $this->makeURI($stource,'data') ;
    $triple['s_type'] = 'uri' ;
    $triple['p'] = $this->mareURI($predicate,'schema') ;
  }

  /**
   * Add a triple of a given kind (data, link or type).
   * Link types must have 'rdf:type' as predicate.
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
        assert('$predicate=="rdf:type"') ;
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
    foreach($map as $predicate => $value) {
      $this->addTriple($triplekind,$source,$predicate,$array) ;
    }
  }

  /**
   * FIXME check what to do if no prefix are given as a parameter
   * @param String? $dataPrefix
   * @param String? $schemaPrefix
   */
  public function __construct($dataPrefix=null,$schemaPrefix=null) {
    $this->currentDataPrefix = $dataPrefix ;
    $this->currentSchemaPrefix = $schemaPrefix ;
    $this->triples = array() ;
  }
}


/**
 * Wrapper for an arc2 configuration. This is the root of a hierarchy
 * which makes it easier to understand which parameters in the configuration
 * should be set. This class provide the most simplified one.
 */
class RDFConfiguration {

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
   * @param Map*(String!,URI!)? $additionalPrefixes A list of prefix to define (without :). 
   * Default to an empty array.  xsd,rdf,rdfs,owl  are always defined.
   */
  public function __construct($additionalPrefixes=array()) {
    $this->arc2config = array() ;
  
    /* stop after 100 errors */
    $this->arc2config['max_errors'] = 100 ;
    
    // Compute the list of prefixes available
    $defaultprefixes = array(
        'xsd'      => 'http://www.w3.org/2001/XMLSchema#',
        'rdf'      => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs'     => 'http://www.w3.org/2000/01/rdf-schema#',
        'owl'      => 'http://www.w3.org/2002/07/owl#'
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
   * No inference is done. Just look for the corresponding rdf:type link. 
   * @param RDFId! $subject
   * @param RDFId! $type
   * @return boolean
   */
  public function /*boolean*/ isOfType($subject,$type ) {
    return $this->isItFact($subject,'rdf:type',$type) ;
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
  public function loadDocument($url) {
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


