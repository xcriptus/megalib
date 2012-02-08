<?php
/**
 * Graphmml support.
 * This class helps to create a graph that can generate the graphml file.
 * @author jeanmariefavre
 * @see http://graphml.graphdrawing.org/ for the graphml format
 * @see http://www.yworks.com/products/yed/ of the yed free graphml editor 
 * @status draft
 */
class Graphml {
  const GRAPH_HEADER = '<?xml version="1.0" encoding="UTF-8"?>
<graphml xmlns="http://graphml.graphdrawing.org/xmlns"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">
' ;
  const GRAPH_FOOTER = '</graphml>' ;

  /* type NodeId==String! */
  /* type NodeIdPair==String! (Separated by a tab)*/
  /* type AttributeId==String! */
  /* type AttributeValues==Map*<AttributeId!,Mixed!>!*/
  /* type AttributeType=='string'|'double'|'xml'
  /* type Schema==Map*<'node'|'edge', Map*<AttributeId!,AttributeInfo>!>!*/
  /* type AttributeInfo==Map< 'name'  => AttributeId!,
   *                          'type'  => AttributeType!,
   *                          'default' => Mixed? >
   */
  protected $graphName = 'G' ;
  protected $edgeDefault = 'directed' ;
  protected /*Map*<NodeId!,AttributeValues!>*/     $nodes = array() ;
  protected /*Map*<NodeIdPair!,AttributeValues!>*/ $edges = array() ;
  protected /*Schema!*/ $schema = array() ;
  
  public function setGraphName(/*String!*/ $graphname) {
    $this->graphName = $graphname ; 
  }
  
  public function /*void*/ addAttributeType(
      /*'node'|'edge'*/$kind,
      /*AttributeId!*/$attname,
      /*Type?*/$type = 'string', 
      /*Mixed?*/$default = null ) {
    $this->schema[$kind][$attname]=array();
    $this->schema[$kind][$attname]['name'] = $attname ;
    $this->schema[$kind][$attname]['type'] = $type ;
    if (isset($default)) {
      $this->schema[$kind][$attname]['default'] = $default ;
    }   
  }
  
  protected function /*String!*/ inferGraphmlTypeFromValue($value) {
    if (is_double($value)) {
      return 'double' ;
    } elseif (is_string($value)) {
      return 'string' ;
    } elseif (is_bool($value)) {
      return 'boolean' ;
    } elseif (is_int($value)) {
      return 'int' ;
    } else {
      die('type not supported') ;
    }
  }
  
  protected function /*void*/ addAttributeTypesIfNecessary(
      /*'node'|'edge'*/$kind,
      /*AttributeValues!*/$attributeValues ) {
    foreach($attributeValues as $attname=>$value) {
      if (! isset($this->schema[$kind][$attname])) {
        $this->schema[$kind][$attname] = array() ;
        $type = $this->inferGraphmlTypeFromValue($value) ;
        $this->addAttributeType($kind,$attname,$type) ;
      }
    }
  }
  
  public function /*void*/ addNode(
      /*NodeId!*/ $nodeid, 
      /*AttributeValues?*/ $attributeValues = array()) {
    $attvals = $attributeValues ;
    $this->addAttributeTypesIfNecessary('node',$attvals) ;
    if (isset($this->nodes[$nodeid])) {
      $attvals = array_merge($attvals,$this->nodes[$nodeid]) ;
    }
    $this->nodes[$nodeid] = $attvals ;
  }

  protected function /*NodeIdPair!*/ nodeIdPair(
      /*NodeId!*/$source, 
      /*NodeId!*/$target) {
    return $source . "\t".$target ;
  }
  
  protected function /*Array[2]<NodeId!>!*/ nodesIdFromPair($pair) {
    $array = explode("\t",$pair) ;
    assert('count($array)==2') ;
    return $array ;
  }
  
  /*
   * Add an edge with some edge attributes if specified
   * Note that the source and target node are added (with no attributes) if not existing
   */
  public function /*void*/ addEdge(
      /*NodeId!*/ $sourceid, 
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
    $this->edges[$pair] = (isset($attributeMap) ? $attributeMap : array() ) ;
  }
  

  public function /*GraphmlString!*/ schemaToString() {
    $out = '' ;
    foreach($this->schema as $kind=>$attdef) {
      foreach($attdef as $attname => $attributeinfo) {
        die('TODO ') ;
      }
    }
  }
    
  public function /*GraphmlString!*/ attributeMapToString(/*AttributeValues!*/ $attvalues) {
    $out = '' ;
    foreach ($attvalues as $attribute => $value) {
      $out .= '      <data key="'.$attribute.'">'.$value.'</data>'."\n" ;
    }
    return $out ;
  }
  

  
  public function /*GraphmlString!*/ nodeToString(/*NodeId!*/ $nodeid) {
    $out = '    <node id="'.$nodeid.'"' ;
    if (count($this->nodes[$nodeid]) == 0) {
      $out .= "/>\n" ;
    } else {
      $out .= ">\n" ;
      $out .= $this->attributeMapToString($this->nodes[$nodeid]) ;
      $out .= "\"    </node>\n" ;
    }
    return $out ;
  }
  
  public function /*GraphmlString!*/ edgeToString(/*NodeId!*/ $sourceid, /*NodeId!*/$targetid) {
    $pair = $this->nodeIdPair($sourceid,$targetid) ;
    $out = '    <edge source="'.$sourceid.'" target="'.$targetid.'"' ;
    if (count($this->edges[$pair] == 0)) {
      $out .=  "/>\n" ;
    } else {
      $out .= ">\n" ;
      $out .= $this->attributeMapToString($this->nodes[$pair]) ;
      $out .= "\"    </edge>\n" ;
    }
    return $out ;
  }
  

  
  public function /*GraphmlString!*/ graphToString() {
    $str = self::GRAPH_HEADER ;
    $str .= $this->schemaToString() ;
    $str .= '  <graph id="'.$this->graphName
    .'" edgedefault="'.$this->edgeDefault."\">\n" ;
    foreach($this->nodes as $nodeid => $map) {
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
  
  public function /*void*/ graphToFile($filename) {
    $str = $this->graphToString() ;
    file_put_contents( $filename,$str) ;    
  }
  
  public function __construct($graphname = null) {
    if (isset($graphname)) {
      $this->setGraphName($graphname) ;
    }
  }
  
}



