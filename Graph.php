<?php

require_once 'Structures.php';

/**
 * Graph support.
 * Helper to create an attributed graph than can latter be used to generate graphml
 * or dot structure. The main benefit of using this class is that declaration of nodes,
 * edges, attributes or nested nodes can come in any sort of order, elements being created
 * transparently on the first occurrence. It is in particular possible to define a node 
 * and then separately different of its attributes. 
 * Similarily nested nodes can be created in an arbirary order. 
 * @author jeanmariefavre
 * @status draft
 */
class Graph {

  /**
   * ----- node and edge ----
   * type NodeId==String! 
   * type NodeIdPair==String!     // Separated by a tab
   * 
   * ----- attribute --------
   * type AttributeId==String! 
   * type AttributeValues==Map*(AttributeId!,Mixed!)!
   * type AttributeType=='string'|'double'|'xml'
   * 
   * ----- schema ------------
   * type Schema==Map*('node'|'edge', Map*(AttributeId!,AttributeInfo)! )!
   * type AttributeInfo==Map{ 'name'  => AttributeId!,
   *                          'type'  => AttributeType!,
   *                          'default' => Mixed? }
   */
  
  protected $graphName = 'G' ;
  protected $edgeDefault = 'directed' ;
  
  /**
   * @var Map*<NodeId!,AttributeValues!>! The set of all nodes with
   * their attributes. If a node have no attribute, the attribute map
   * is simply empty.
   */
  protected $nodes = array() ;
  
  /**
   * @var Map*<NodeId!,NodeId!>! The parent of a node if any. If a node is
   * a root, then its id will not appear in this list. This structure allows
   * to represent nested graphs.
   */
  protected $parents = array() ;
  
  /**
   * @var Map*<NodeIdPair!,AttributeValues!>! The edges and their attributes.
   * Edge ids are represented as node id pairs. If an edge have no attribute,
   * then the attribute map is simple empty.
   */
  protected $edges = array() ;
  
  /**
   * @var Schema! The schema of a graph listing the attributes definitions
   * of both nodes and edges.
   */
  protected $schema = array() ;
  
  
  
  /*----------------------------------------------------------------
   * Accessors
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
   * Attribute management
   *----------------------------------------------------------------
   */
  /**
   * Add an attribute definition to the schema. This function will be
   * automatically called when nodes and edges are defined in the
   * case of missing attribute definitions, but since this process is
   * based on type inference it may be better to declare explicitely
   * the attributes.
   * @param 'node'|'edge' $kind
   * @param AttributeId! $attname
   * @param Type? $type
   * @param Mixed? $default
   */
  public function /*void*/ addAttributeType(
      $kind,
      $attname,
      $type = 'string', 
      $default = null ) {
    $this->schema[$kind][$attname]=array();
    $this->schema[$kind][$attname]['name'] = $attname ;
    $this->schema[$kind][$attname]['type'] = $type ;
    if (isset($default)) {
      $this->schema[$kind][$attname]['default'] = $default ;
    }   
  }
  
  protected function /*String!*/ inferTypeFromValue($value) {
    // This can be more sophisticated if necessary such as
    // recognizing number in string. Is it necessary?
    return typeOf($value) ;
  }
  
  protected function /*void*/ addAttributeTypesIfNecessary(
      /*'node'|'edge'*/$kind,
      /*AttributeValues!*/$attributeValues ) {
    foreach($attributeValues as $attname=>$value) {
      if (! isset($this->schema[$kind][$attname])) {
        $this->schema[$kind][$attname] = array() ;
        $type = $this->inferTypeFromValue($value) ;
        $this->addAttributeType($kind,$attname,$type) ;
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
   * Add an edge with some edge attributes if specified. Note that repeated 
   * edge addition with the same node id pairs will be recorded but the output is
   * implementation dependent. It may considered as a single edge or multiple edge.
   * Note that the source and target node are added (with no attributes) if not existing.
   * Simililarily missing attribute definitions are added.
   *  
   * @param NodeId! $sourceid
   * @param NodeId! $targetid
   * @param AttributeValues? $attributeValues attributes of the edge if any. 
   * Default to no attribute.
   * @return void
   */
  public function /*void*/ addEdge(
      /**/ $sourceid, 
      /*NodeId!*/ $targetid, 
      /*AttributeValues?*/ $attributeValues = array() ) {
    $attvals = $attributeValues ;     
    $this->addAttributeTypesIfNecessary('edge',$attvals) ;
    if (! isset($this->nodes[$sourceid])) {
      $this->addNode($sourceid) ;
    }
    if (! isset($this->nodes[$targetid])) {
      $this->addNode($targetid) ;
    }    
    $pair = $this->nodeIdPair($sourceid,$targetid) ;
    // TODO check whether we want to have id for edges as well
    $this->edges[$pair] = (isset($attvals) ? $attvals : array() ) ;
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
  public function setGraphName( $graphname) {
    $this->graphName = $graphname ;
  }
  
  
  public function __construct($graphname = null) {
    if (isset($graphname)) {
      $this->setGraphName($graphname) ;
    }
  }
}








/**
 * Graphml structure.
  * @see http://graphml.graphdrawing.org/ for the graphml format
  * @see http://www.yworks.com/products/yed/ of the yed free graphml editor
 */

class Graphml extends Graph {
  
  const GRAPH_HEADER = '<?xml version="1.0" encoding="UTF-8"?>
  <graphml xmlns="http://graphml.graphdrawing.org/xmlns"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">
' ;
  const GRAPH_FOOTER = '</graphml>' ;
  
  const NESTED_GRAPH_SUFFIX ='-subgraph' ;
 
  protected $graphmlNodeId = array() ;
  protected $graphmlEdgeId = array() ;
  
  protected function /*GraphmlSting!*/ attributeTypeToString($typename){
    switch($typename) {
      case 'string': 
        return 'string' ;
        break ;
      case 'float':
        return 'double' ;
        break ;
      case 'integer':
        return 'int' ;
        break ;
      case 'boolean':
        return 'bool' ;
        break ;
      default:
        die("attribute of type $typename not supported in graphml") ;
    }
  }
  
  /**
   * Represents the schema of a graph, that is definition of attributes, in graphml.
   * @param String? optional indentation
   * @return GraphmlString! 
   */
  protected function /*GraphmlString!*/ schemaToString($indent='') {
    $out = '' ;
    foreach($this->schema as $kind=>$attdef) {
      foreach($attdef as $attname => $attributeinfo) {
        $out .= $indent.'<key id="'.$kind.".".$attributeinfo['name'].'"'
                . ' for="'.$kind.'" attr.name="'.$attributeinfo['name'].'"'
                . ' attr.type="'.$this->attributeTypeToString($attributeinfo['type']).'"' ;
        if (isset($attributeinfo['default'])) {
          $out .= ">\n" ;
          $out .= $indent.'  <default>'.$attributeinfo['default'].'</default>'."\n" ;
          $out .= $indent."</key>\n" ;
        } else {
          $out .= "/>\n" ;
        }
      }
    }
    return $out ;
  }
    
  
  
  /**
   * Represents a attribute map in graphml.
   * @param 'node'|'edge' $kind kind of attribute
   * @param AttributeValues! $attvalues values of the attributes
   * @param String? optional indentation
   * @return GraphmlString! a series of <data> elements.
   */
  protected function attributeMapToString($kind,$attvalues,$indent='') {
    $out = '' ;
    foreach ($attvalues as $attribute => $value) {
      $out .= $indent.'  <data key="'.$kind.'.'.$attribute.'">'.$value.'</data>'."\n" ;
    }
    return $out ;
  }
  
  /**
   * Represents a node in graphml. 
   * @param GraphmlString! $nodeid
   * @param String? optional indentation
   * @return NodeId! a <node> element with the attribute definitions and potentially the subgraph.
   */
  protected function nodeToString($nodeid,$indent='') {
    $out = $indent.'    <node id="'.$nodeid.'"' ;
    $attmap = $this->getNodeAttributes($nodeid) ;
    $children = $this->getNodeChildren($nodeid) ;
    if (count($attmap)==0 && count($children)==0) {
      $out .= "/>\n" ;
    } else {
      $out .= ">\n" ;
      if (count($attmap)!=0) {
        $out .= $this->attributeMapToString('node',$attmap,$indent.'    ') ;
      }
      if (count($children)!=0) {
        $out .= $indent.'      <graph id="'.$nodeid.self::NESTED_GRAPH_SUFFIX.'">'."\n" ;
        foreach ($children as $child) {
           $out .= $this->nodeToString($child,$indent.'    ') ;
        }
        $out .= $indent."      </graph>\n" ;
      } 
      $out .= $indent."    </node>\n" ;
    }
    return $out ;
  }
  
  /**
   * Represents an edge in graphml. 
   * @param NodeId! $sourceid 
   * @param NodeId! $targetid
   * @param String? optional indentation
   * @return GraphmlString! a <edge> element with potentially the list of edge attributes.
   */
  protected function edgeToString($sourceid,$targetid,$indent='') {
    $pair = $this->nodeIdPair($sourceid,$targetid) ;
    $out = $indent.'    <edge source="'.$sourceid.'" target="'.$targetid.'"' ;
    if (count($this->edges[$pair]) == 0) {
      echo "" ;
      $out .=  "/>\n" ;
    } else {
      $out .= ">\n" ;
      $out .= $this->attributeMapToString('edge',$this->edges[$pair],$indent.'     ') ;
      $out .= $indent."    </edge>\n" ;
    }
    return $out ;
  }
  

  
  /**
   * Represents a graph in graphml.
   * @return GraphmlString! a <graph> element with all nodes and edges.
   */
  public function /*GraphmlString!*/ graphToString() {
    $str = self::GRAPH_HEADER ;
    $str .= $this->schemaToString('  ') ;
    $str .= '  <graph id="'.$this->graphName
    .'" edgedefault="'.$this->edgeDefault."\">\n" ;
    foreach($this->getRootNodes() as $nodeid) {
      $str .= $this->nodeToString($nodeid) ;
    }
    foreach($this->edges as $pair => $map) {
      $nodes=$this->nodesIdFromPair($pair) ;
      $str .= $this->edgeToString($nodes[0],$nodes[1]) ;      
    }
    $str .= "  </graph>\n" ;
    $str .= self::GRAPH_FOOTER ;
    return $str ;
  }
  
  /**
   * Save a graph in a graphml file.
   * @param String! $filename the name of the file to write into.
   * @result void.
   */
  public function /*void*/ graphToGraphmlFile($filename) {
    $str = $this->graphToString() ;
    file_put_contents( $filename,$str) ;    
  }
  
  public function __construct($graphname = null) {
    parent::__construct($graphname) ;
  }
  
}


class DotGraph extends Graph {
  
}

