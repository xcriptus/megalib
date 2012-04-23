<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Structures.php';

/**
 * Nested Attributed Graph with nodes, edges, and a schema formed by typed attributes both on 
 * nodes, edges or the top level graph. This structure is a generalization of graph
 * structures used for graph visualization tools sur as GraphML/Yed or GraphViz. 
 * In the current version nodes can be declared inside of a parent node but this is not
 * the case for edges (although graphml support this).
 * Node that the class can work with or without edge id. If no id are provided for an
 * edge then its default to the node pair.
 * This class can be seen as a helper to create an attributed graph than can latter be used 
 * to generate graphml or graphviz structure. 
 * The main benefit of using this class is that declaration of nodes,
 * edges, attributes or nested nodes can come in any sort of order, elements being created
 * transparently on the first occurrence. It is in particular possible to define a node 
 * and then separately different of its attributes. 
 * Similarily nested nodes can be created in an arbirary order. 
 * 
 * The structure of the graphs are defined as follow.
 * TODO: refine this description.
 * 
 * 
 * 
 * ----- attribute --------
 * Attributes can be defined on different kind of elements. Standard elements
 * are 'node', 'edge' and 'graph' but implementation might add others.
 *   type ElementKind='node'|'edge'|'graph'|...
 *   type AttributeId==String! 
 *   type AtrributeName==String!
 *   type AttributeType==String!
 *   type AttributeValues==Map*(AttributeId!,Mixed!)!
 * 
 * ----- schema ------------
 * - Note that 'meta' on schema is used to accomodate graph models
 * with more complex definitions of attribute types. This is the
 * case for instance for graphml and yed.
 * - Note that id is expected to be unique in the scope of ElementKind (and not
 * necessarily on the global scope). That is, two attributes "name" can both be 
 * declared on nodes and edges without being confused.
 * - By contrast to id, the attribute name is an arbitrary string.
 *  
 *   type Schema==Map*(ElementKind!,AttributeDefinition!)!
 *   type AttributeDefinition==Map*(AttributeId!,AttributeInfo!)!
 *   type MetaAttributes==Map*(String!,String!)!
 *   type AttributeInfo==Map{ 'kind'  => ElementKind!,
 *                            'id'    => AttributeId!,
 *                            'type'  => AttributeType!,
 *                            'name'  => AttributeName!
 *                            'meta'  => MetaAttributes!,
 *                            'default' => Mixed? }
 *                            
 *                            
 * ----- nodes  ----
 *   type NodeId==String! 
 *   
 * ----- edges -----  
 *   type EdgeId==String!
 *   type NodeIdPair==String!     // Separated by a tab (used as default id)
 *   type EdgeInfo==Map{ 'source' => NodeId!,
 *                       'target' => NodeId!,
 *                       'attributes' => AttributeValues!
 *                       'meta' => MetaAttributes! }                           
 */
class NAGraph {

  /**
   * @var String the name of the top level graph
   */
  
  protected $graphName = 'G' ;
  /**
   * @var AttributeValues
   */
  protected $graphAttributes = array() ;
  
  protected $edgeDefault = 'directed' ;
  
  /**
   * @var Map*(NodeId!,AttributeValues!)! The set of all nodes with
   * their attributes. If a node have no attribute, the attribute map
   * is simply empty.
   */
  protected $nodes = array() ;
  
  /**
   * @var Map*(NodeId!,NodeId!)! The parent of a node if any. If a node is
   * a root, then its id will not appear in this list. This structure allows
   * to represent nested graphs.
   */
  protected $parents = array() ;
  
  /**
   * @var Map*(EdgeId!,EdgeInfo)! The edges and the corresponding information.
   */
  protected $edgeMap = array() ;
  
  
  
  /**
   * @var Schema! The schema of a graph listing the attributes definitions
   * of both nodes, edges and graph.
   */
  protected $schema = array() ;
  
  /*----------------------------------------------------------------
   * Accessors for the schema
   *----------------------------------------------------------------
   */
  
  
  /**
   * Return the information about an attribute if it exists or null otherwise.
   * @param ElementKind! $elementKind
   * @param AttributeId! $id
   * @return AttributeInfo? the information about the attribute if has been defined.
   * Null otherwise.
   */
  public function getAttributeInfo($elementKind,$id) {
    @ $info = $this->schema[$elementKind][$id] ;
    return  $info;
  }
  
   /**
   * Return the type of an attribute if it exist or null otherwise.
   * @param ElementKind! $elementKind
   * @param AttributeId! $id
   * @return AttributeInfo? the information about the attribute if has been defined.
   * Null otherwise.
   */
  public function getAttributeType($elementKind,$id) {
    $info = $this->getAttributeInfo($elementKind,$id) ;
    if (isset($info)) {
      return $info['type'] ;$this->schema[$elementKind][$id] ;
    } else {
      return null ;
    }
  }

  /**
   * Returh the schema of the graph.
   * @return Schema!
   */
  public function getSchema() {
    return $this->schema ;
  }
  
  /*----------------------------------------------------------------
   * Accessors for nodes
   *----------------------------------------------------------------
   */
    
  /**
   * Get the attribute values of a node or null if the node does not exist.
   * @param NodeId! $nodeid
   * @return AttributeValues?
   */
  public function getNodeAttributes($nodeid) {
    if (isset($this->nodes[$nodeid])) {
      return $this->nodes[$nodeid] ;
    } else {
      return null ;
    } 
  }
  
  
  /**
   * Get the list of all existing nodes.
   * @return Set*<NodeId!>!
   */
  public function getNodes() {
    return array_keys($this->nodes) ;
  }
  
  /**
   * Get the list of root nodes (the nodes that have no nested nodes).
   * @return Set*<NodeId!>!
   */
  public function getRootNodes() {
    return array_diff($this->getNodes(),array_keys($this->parents)) ;
  }
  
  /**
   * Get the children of a given node. 
   * @param NodeId! $nodeid
   * @return Set*<NodeId!>!
   */
  public function getNodeChildren($nodeid) {    
    return array_keys($this->parents,$nodeid) ;
  }

  /*----------------------------------------------------------------
   * Accessors for Edges
  *----------------------------------------------------------------
  */
  
  public function getEdges() {
    return array_keys($this->edgeMap) ;
  }
  
  /**
   * The source of a given edge or null if the edge does not exist.
   * @param EdgeId $edgeid
   * @return NodeId? the source node or null
   */
  public function getEdgeSource($edgeid) {
    if (isset($this->edgeMap[$edgeid])) {
      return $this->edgeMap[$edgeid]['source'] ;
    } else {
      return null ;
    }
  }
  
  
  /**
   * The target of a given edge or null if the edge does not exist.
   * @param EdgeId $edgeid
   * @return NodeId? the target node or null
   */
  public function getEdgeTarget($edgeid) {
    if (isset($this->edgeMap[$edgeid])) {
      return $this->edgeMap[$edgeid]['target'] ;
    } else {
      return null ;
    }
  }  
  
  /**
   * Get the attribute values of an edge or null if the edge does not exist.
   * @param EdgeId! $edgeid
   * @return AttributeValues?
   */
  public function getEdgeAttributes($edgeid) {
      if (isset($this->edgeMap[$edgeid])) {
      return $this->edgeMap[$edgeid]['attributes'] ;
    } else {
      return null ;
    } 
  }
  
  /*----------------------------------------------------------------
   * Accessors for the graph
   *----------------------------------------------------------------
   */
  
  
  /**
   * Get the name of the graph
   * @return string
   */
  public function getGraphName() {
    return $this->graphName ;
  }
  
  /**
   * Get the attributes of the graph
   * @return string
   */
  public function getGraphAttributes() {
    return $this->graphAttributes ;
  }
  
  public function getEdgeDefault() {
    return $this->edgeDefault ;
  }
  
  
  /*----------------------------------------------------------------
   * Attribute management
   *----------------------------------------------------------------
   */
  /**
   * Add an attribute definition to the schema. This function will be
   * automatically called when nodes and edges are defined in the
   * case of missing attribute definitions, but since this process is
   * based on type inference it may be better to declare explicitely
   * the attributes.
   * @param ElementKind $kind The kind of element on which the attribute is defined
   * @param AttributeId! $id The id of the attribute. Should be unique for a given $kind
   * @param Type? $type The type of the attribute. Default to string.
   * @param AttributeName? $name The name of the attribute. If not given then it will be
   * set to the same value as $id.
   * @param Mixed? $default a default value if provided.
   * @param MetaAttributes? $meta Some meta attributes with their values if necessary. 
   * Default to an empty map, that is no meta attributes defined.
   * @return void
   */
  public function addAttributeType($kind,$id,$type='string',$name=null,$default=null,$meta=array()){
    $this->schema[$kind][$id]=array();
    $this->schema[$kind][$id]['kind'] = $kind ;
    $this->schema[$kind][$id]['id'] = $id ;
    $this->schema[$kind][$id]['type'] = $type ;
    $this->schema[$kind][$id]['name'] = isset($name) ? $name : $id ;
    if (isset($default)) {
      $this->schema[$kind][$id]['default'] = $default ;
    }
    $this->schema[$kind][$id]['meta'] = $meta ;
  }
  
  protected function /*String!*/ inferTypeFromValue($value) {
    // This can be more sophisticated if necessary such as
    // recognizing number in string. Is it really necessary?
    return typeOf($value) ;
  }
  
  /**
   * Given a set of attributes values, add their definitions on a given element kind if
   * they are not already defined. In this case the type of the attribute is infered
   * from the actual value of the attribute. 
   * @param ElementKind! $kind
   * @param AttributeValues! $attributeValues
   * @return void
   */
  protected function addAttributeTypesIfNecessary($kind,$attributeValues ) {
    foreach($attributeValues as $attid=>$value) {
      if (! isset($this->schema[$kind][$attid])) {
        $this->schema[$kind][$attid] = array() ;        
        $type = $this->inferTypeFromValue($value) ;
        $this->addAttributeType($kind,$attid,$type) ;
      }
    }
  }

  
  
  /*----------------------------------------------------------------
  * Node management
  *----------------------------------------------------------------
  */
  
  /**
   * Add a node to the graph or add/merge attributes to an existing node. 
   * If the node already exist then the attributes are merged with
   * previous attributes. Add missing attribute definitions if the graph schema.
   * If a parent node is specified it replaces its potential previoous parent. 
   * The parent node is created if not already existing.   
   * If no parent node is specified, then the previous parent, if any, is untouched.
   * @param NodeId! $nodeid node identifier. This identifier is reused by edges.
   * @param AttributeValues? $attributeValues A map of attribute values. Default to no attribute.
   * @param NodeId? $parent parent node of the node if any. This allows to define nested graph.
   * Node parent is the default.
   * @return void
   */
  public function /*void*/ addNode($nodeid,$attributeValues = array(),$parent=null) {
    assert('is_array($attributeValues)') ;
    $attvals = $attributeValues ;   
    $this->addAttributeTypesIfNecessary('node',$attvals) ;    
    if (isset($this->nodes[$nodeid])) {
      $attvals = array_merge($attvals,$this->nodes[$nodeid]) ;
    }
    $this->nodes[$nodeid] = $attvals ;
    
    if (isset($parent)) {
      $this->addNode($parent) ;
      $this->parents[$nodeid]=$parent ;
     }
  }

   
  
  /*----------------------------------------------------------------
   * Edge management
  *----------------------------------------------------------------
  */

  /**
   * Create a node id pair.
   * @param NodeId! $source
   * @param NodeId! $target
   * @return NodeIdPair!
   */
  protected function /*NodeIdPair!*/ nodeIdPair($source,$target) {
    return $source . "\t".$target ;
  }
  
  /**
   * Return the source and target of an nodeidpair.
   * @param NodeIdPair! $pair
   * @return Array[2]<NodeId!>!:
   */
  protected function /*Array[2]<NodeId!>!*/ nodesIdFromPair($pair) {
    $array = explode("\t",$pair) ;
    assert('count($array)==2') ;
    return $array ;
  }
  
  /*
   * Add an edge with some edge attributes if specified. 
   * XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX TO BE UPDATED
   *  *   type EdgeInfo==Map{ 'source' => NodeId!,
 *                       'target' => NodeId!,
 *                       'attributes' => AttributeValues!
 *                       'meta' => MetaAttributes! }                           

   * Note that repeated 
   * edge addition with the same node id pairs will be recorded but the output is
   * implementation dependent. It may considered as a single edge or multiple edge.
   * Note that the source and target node are added (with no attributes) if not existing.
   * Simililarily missing attribute definitions are added.
   *  
   * @param NodeId! $sourceid
   * @param NodeId! $targetid
   * @param AttributeValues? $attributeValues attributes of the edge if any. 
   * @param EdgeId? if provided this is a unique edge id. Otherwise the id will be formed by
   * the sourceid and targetid concatenation.
   * Default to no attribute.
   * @param MetaAttributes 
   * @return void
   */
  public function /*void*/ addEdge($sourceid,$targetid,$attributeValues=array(),$edgeid = null,$meta=array()){
    $attvals = $attributeValues ;     
    $this->addAttributeTypesIfNecessary('edge',$attvals) ;
    if (! isset($this->nodes[$sourceid])) {
      $this->addNode($sourceid) ;
    }
    if (! isset($this->nodes[$targetid])) {
      $this->addNode($targetid) ;
    }
    if (!isset($edgeid)) {    
      $edgeid=$this->nodeIdPair($sourceid,$targetid) ;
    }
    if (!isset($this->edgeMap[$edgeid])) {
      $this->edgeMap[$edgeid] = array() ;
    }
    $this->edgeMap[$edgeid]['source'] = $sourceid ;
    $this->edgeMap[$edgeid]['target'] = $targetid ;
    
    
    $this->edgeMap[$edgeid]['attributes'] = 
      isset($this->edgeMap[$edgeid]['attributes'])
        ? array_merge($this->edgeMap[$edgeid]['attributes'],$attributeValues)
        : $attributeValues ;
    $this->edgeMap[$edgeid]['meta'] =
      isset($this->edgeMap[$edgeid]['meta'])
        ? array_merge($this->edgeMap[$edgeid]['meta'],$meta)
        : $meta ;
  }

  
  /*---------------------------------------------------------------
   * Graph management
  *----------------------------------------------------------------
  */
  
  /**
   * Given a name to the graph.
   * @param String! $graphname
   * @return void
   */
  public function setGraphName($graphname) {
    $this->graphName = $graphname ;
  }
  
  /**
   * Add the attributes to the graph and if they are not defined add their infered type as well
   * @param AttributeValues $attvals
   * @return void
   */
  public function addGraphAttributes($attvals) {
    $this->addAttributeTypesIfNecessary('graph',$attvals) ;
    if (isset($this->graphAttributes)) {
      $attvals = array_merge($attvals,$this->graphAttributes) ;
    }
    $this->graphAttributes = $attvals ;
  }
  
  
  
  public function __construct($graphname = null) {
    if (isset($graphname)) {
      $this->setGraphName($graphname) ;
    }
  }
}


/**
 * Serialize a graph in as a string or a file. 
 * The abstract class should be refined for the different formats.
 */
abstract class NAGraphWriter {
  
  /**
   * @var Graph the graph to be writen.
   */
  protected $g ;
  
  /**
   * Output a node representation.
   * Should be implemented by subclasses. 
   * @param GraphString! $nodeid
   * @param String? optional indentation
   * @return GraphString! The representation of the node.
   */
  protected abstract function nodeToGraphString($nodeid,$indent='') ;
  
  /**
   * Output an edge representation.
   * @param EdgeId! $edgeid
   * @param String? optional indentation
   * @return GraphString! The representation of the edge.
   */
  protected abstract function edgeToGraphString($edgeid,$indent='') ;
    
  /**
   * Output the header of the graph.
   * Should be implemented by subclasses. 
   * @return GraphString!
   */
  protected abstract function headerToGraphString() ;

  /**
   * Output the footer of the graph.
   * Should be implemented by subclasses.
   * @return GraphString!
   */
  protected abstract function footerToGraphString() ;
  
  /**
   * Serialize a graph. This function output 
   * (1) the header
   * (2) all root nodes
   * (3) all edges
   * (4) the footer
   * If this is not appropriated the function could be overloaded
   * in subclasses.
   * @return GraphString! a string representing the whole graph.
   */
  public function /*GraphString!*/ graphToGraphString() {
    $str = $this->headerToGraphString() ;
    foreach($this->g->getRootNodes() as $nodeid) {
      $str .= $this->nodeToGraphString($nodeid) ;
    }
    foreach($this->g->getEdges() as $edgeid) {
      $str .= $this->edgeToGraphString($edgeid) ;
    }
    $str .= $this->footerToGraphString() ;
    return $str ;
  }
  
  /**
   * Save a graph in a graphml file.
   * @param String! $filename the name of the file to write into.
   * @result void.
   */
  public function /*void*/ graphToGraphFile($filename) {
    $str = $this->graphToGraphString() ;
    file_put_contents($filename,$str) ;
  }

  
  public function __construct(NAGraph $graph) {
    $this->g = $graph ;
  }
  
}



