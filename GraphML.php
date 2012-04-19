<?php

require_once 'Graph.php' ;

/**
 * GraphML structure.
 * @see http://graphml.graphdrawing.org/ for the graphml format
 * @see http://www.yworks.com/products/yed/ of the yed free graphml editor
 * Note that GraphML enable to have subgraph inside node. This level is not
 * modeled in the Graph class. Nodes and edges in the subgraphs are directly
 * own by the parent nodes. Moreover, in graphml it is possible to add attrbutes
 * on these subgraphs. These attributes are not supported with Graph. This
 * means that subgraph attributes values are lost (but toplevel graph values
 * are supported).
 * 
 * GraphML refines some types as following:
 *    type AttributeType==string|double|xml | YedAttributeTypes
 * 
 *    TODO This is yed specific so this should go in principle somewhere else, but in the mean time...
 *    type YedAttributeTypes==resources|portgraphics|portgeometry|portuserdata|nodegraphics|edgegraphics
 * 
 * In graphml attributes types are defined via the key XML element.
 * GraphML 'id' attributes are naturally mapped to 'id' in Graph AttributeInfo.
 * GraphML 'for' attributes are mapped to ElementKind.
 * In GraphML attribute declarations are global 
 * but this does not cause a problem as in Graph 'id' is 
 * supposed to be unique in the context of a given ElementKind 
 * (which is more restrictive).
 * Either  yfiles.type 
 * 
 * The 'id' of attributes is directly mapped to 'id'
 */



class GraphML extends Graph {

  //----------------------------------------------------------------------------
  //------ GraphML Writter -----------------------------------------------------
  //----------------------------------------------------------------------------
  
  const GRAPH_HEADER = '<?xml version="1.0" encoding="UTF-8"?>
  <graphml xmlns="http://graphml.graphdrawing.org/xmlns"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">
  <!-- Created by megalib/GraphML.php -->
' ;
  const GRAPH_FOOTER = '</graphml>' ;

  const NESTED_GRAPH_SUFFIX ='-subgraph' ;

  protected $graphmlNodeId = array() ;
  protected $graphmlEdgeId = array() ;

  // TODO this should go to a yed subclasses
  // In fact the typing system is a strange mixture and we
  // should reworked it because currently some type has been
  // add based on common sense, some from graphml and some yed.
  protected $standardTypeMapping=array(
      'string'       => 'string',
      'float'        => 'double',
      'double'       => 'double',
      'integer'      => 'int',
      'boolean'      => 'bool',
      'xml'          => 'xml'
    ) ;
  
  protected $yEdTypes=array(
      'nodegraphics',
      'edgegraphics',
      'resources',
      'portgraphics',
      'portgeometry',
      'portuserdata'
    ) ;
  
  public function isStandardType($type) {
    return isset($this->standardTypeMapping[$type]) ;
  } 
  
  public function isYEdType($type) {
    return in_array($type,$this->yEdTypes) ;
  }

  /**
   * Return a pair defining the type of an attribute. 
   * It could be either like attr.type='standardtype' for standard types
   * or for yed types it will be like  yfiles.type='yedtype'
   * @param AttributeType $typename
   * @return GraphMLString! A graphml string as stated above.
   */
  protected function attributeTypeToGraphMLTypeAttributePair($typename) {
    if ($this->isStandardType($typename)) {
      return 'attr.type="'.$this->standardTypeMapping[$typename].'"' ;
    } elseif ($this->isYEdType($typename)) {
      return 'yfiles.type="'.$typename.'"' ;
    } else {
      die('GraphML: attribute of type "'.$typename.'" not supported') ;
    }
  }
  
  
  /**
   * Generate a GraphMLString for the value so that value of type 'string'
   * are embedded in a CDATA section.
   * If it is XML or other elements nothing need to be done.
   * @param Mixed $value
   * @param AttributeType $type
   * @return GraphMLString! the string corresponding to the value
   */
  protected function attributeValueToGraphMLString($value,$type) {
    if ($type=='string' && $value!="") {
      return '<![CDATA['.$value.']]>' ;
    } else {
      return $value ;
    }
  }
  
  /**
   * Generate a GraphML "key" element corresponding to an attribute definition.
   * @param AttributeInfo! $attributeInfo
   * @param String! $indent
   * @return GraphMLString! the key XML element.
   */
  protected function attributeDefinitionToGraphMLString($attributeInfo,$indent='') {
    $out = $indent.'<key id="'.$attributeInfo['id']
                      .' for="'.$attributeInfo['kind'].'"' ;
    $type = $attributeInfo['type'] ;
    $out .= ' '.$this->attributeTypeToGraphMLTypeAttributePair($type) ;
    // only attribute with a standard type seems to have the attr.name defined. 
    // In this case print it otherwise totally ignore it.
    if ($this->isStandardType($type)) {
      $out .= ' attr.name="'.$attributeInfo['name'].'"' ;
    }
    if (isset($attributeInfo['default'])) {
      $out .= ">\n" ;
      $out .= $indent.'  <default>' ;
      $out .= $this->attributeValueToGraphMLString($attributeInfo  ['default'],$type) ;
      $out .= '</default>'."\n" ;
      $out .= $indent."</key>" ;
    } else {
      $out .= "/>" ;
    }
    return $out ;
  }

  /**
   * Represents the schema of a graph, that is definition of attributes, in graphml.
   * @param String? optional indentation
   * @return GraphMLString! a list of "key" XML elements.
   */
  protected function schemaToGraphMLString($indent='') {
    $out = '' ;
    foreach($this->schema as $kind=>$attdef) {
      foreach($attdef as $attname => $attributeinfo) {
        $out .= $this->attributeDefinitionToGraphMLString($attributeinfo,$indent)."\n" ;
      }
    }
    return $out ;
  }



  /**
   * Represents a attribute map in graphml with a series of "data" XML elements.
   * @param ElementKind! $kind kind of the element on which the attribut is defined.
   * @param AttributeValues! $attvalues values of the attribute
   * @param String? optional indentation
   * @return GraphMLString! a series of "data" WML elements.
   */
  protected function attributeMapToGraphMLString($kind,$attvalues,$indent='') {
    $out = '' ;
    foreach ($attvalues as $attid => $value) {
      $out .= $indent.'  <data key="'.$attid.'">' ;
      $type = $this->getAttributeType($kind,$attid) ;
      $out .= $this->attributeValueToGraphMLString($value,$type) ;
      $out .= '</data>'."\n" ;
    }
    return $out ;
  }

  /**
   * Represents a node in graphml.
   * @param GraphMLString! $nodeid
   * @param String? optional indentation
   * @return NodeId! a <node> element with the attribute definitions and potentially the subgraph.
   */
  protected function nodeToGraphMLString($nodeid,$indent='') {
    $out = $indent.'    <node id="'.$nodeid.'"' ;
    $attmap = $this->getNodeAttributes($nodeid) ;
    $children = $this->getNodeChildren($nodeid) ;
    if (count($attmap)==0 && count($children)==0) {
      $out .= "/>\n" ;
    } else {
      $out .= ">\n" ;
      if (count($attmap)!=0) {
        $out .= $this->attributeMapToGraphMLString('node',$attmap,$indent.'    ') ;
      }
      if (count($children)!=0) {
        $out .= $indent.'      <graph id="'.$nodeid.self::NESTED_GRAPH_SUFFIX.'">'."\n" ;
        foreach ($children as $child) {
          $out .= $this->nodeToGraphMLString($child,$indent.'    ') ;
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
   * @return GraphMLString! a <edge> element with potentially the list of edge attributes.
   */
  protected function edgeToGraphMLString($edgeid,$indent='') {
    $sourceid = $this->getEdgeSource($edgeid) ;
    $targetid = $this->getEdgeTarget($edgeid) ;
    $attributes = $this->edgeMap[$edgeid]['attributes'] ;
    $out = $indent.'    <edge id="'.$edgeid.'" source="'.$sourceid.'" target="'.$targetid.'"' ;
    if (count($attributes) == 0) {
      echo "" ;
      $out .=  "/>\n" ;
    } else {
      $out .= ">\n" ;
      $out .= $this->attributeMapToGraphMLString('edge',$attributes,$indent.'     ') ;
      $out .= $indent."    </edge>\n" ;
    }
    return $out ;
  }



  /**
   * Represents a graph in graphml.
   * @return GraphMLString! a <graph> element with all nodes and edges.
   */
  public function /*GraphMLString!*/ graphToGraphMLString() {
    $str = self::GRAPH_HEADER ;
    $str .= "\n  <!-- Graph schema -->\n" ;
    $str .= $this->schemaToGraphMLString('  ') ;
    $str .= "\n  <!-- Graph data -->\n" ;
    $str .= '  <graph id="'.$this->graphName
    .'" edgedefault="'.$this->edgeDefault."\">\n" ;
    foreach($this->getRootNodes() as $nodeid) {
      $str .= $this->nodeToGraphMLString($nodeid) ;
    }
    foreach($this->edgeMap as $edgeid => $edgeinfo) {
      $str .= $this->edgeToGraphMLString($edgeid) ;
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
  public function /*void*/ graphToGraphMLFile($filename) {
    $str = $this->graphToGraphMLString() ;
    file_put_contents( $filename,$str) ;
  }

  public function __construct($graphname = null) {
    parent::__construct($graphname) ;
  }

}


class GraphMLReader extends GraphML {
  /**
   * @var SimpleXML the GraphML structure in XML
   */
  protected $simpleXML ;

  //--- deals with schema definition, that is keys ---
  protected function processKeysFromXML($document) {
    foreach($document->key as $key) {
      $this->processKeyFromXML($key) ;
    }
  }
  
  protected function processKeyFromXML($key) {
    $attid = (string) $key['id'] ;
    $kind = (string) $key['for'] ;
    
    // TODO the code below is yed specific and should be move to the yed class
    $type = (string) $key['attr.type'] ;
    if ($type) {
      // this seems to be a regular GraphML attribute definition
      // we assume that attr.name is then defined
      $attname = (string) $key['attr.name'] ;
    } else {
      // try to see if the is a Yed attribute definition. 
      // it seems that this is with yfiles.type and that there is no attribute name 
      $type = (string) $key['yfiles.type'] ;
      $attname = null ;
    }
    if (isset($key->default)) {
      $default = (string) $key->default ;
    }  else {
      $default = null ;
    }
    // if it has been impossible to find the type then do not put the definition at all
    $this->addAttributeType($kind,$attid,$type,$attname,$default) ;
  }
  
  //-- deals with top level
  protected function processTopLevelGraphFromXML($graph) {
    $graphid=(string) $graph['id'] ;
    $this->setGraphName($graphid) ;
    $this->processGraphFromXML($graph,null) ;
  }
  

  
  protected function processGraphFromXML($graph,$parentnodeid=null) {
    $attributes = $this->processDataFromXML('graph',$graph) ;
    if (count($attributes)>=1) {
      if ($parentnodeid===null){
        $this->addGraphAttributes($attributes) ;
      } else {
        die('Graph: in the current version of Graph, and its implementation of GraphML,'
            . ' attributes values are not supported on subgraphs') ;
      }
    }
    foreach($graph->node as $node) {
      $this->processNodeFromXML($node,$parentnodeid) ;
    }
    foreach($graph->edge as $edge) {
      $this->processEdgeFromXML($edge,$parentnodeid) ;
    }
  }
  
  protected function processDataFromXML($kind,$nodeOrEdgeOrGraph) {
    $attributes = array() ;
    foreach($nodeOrEdgeOrGraph->data as $data) {
      $key=(String) $data['key'] ;
      $attributeType=$this->getAttributeType($kind, $key) ;
      switch ($attributeType) {
        case 'string' :
          $value = (String) $data ;
          break ;
        case 'double' :
          // TODO should be converted to a double
          $value = (String) $data ;
          break ;
        case null :
            die("attribute $key is used on a $kind but it has not been defined previously") ;
            break ;  
        default :
          // TODO here we assume that this is xml but an appropriate treatement for Yed types should be implemented
          // echo "$kind, $key : $attributeType ".count($data->xpath("*")) ;
          $value = "" ;
          // because ->children() use the default namespace, and the child can be (are indeed) in another
          // namespace (yed), we use a xparth expression.
          // In practice we should have only one child, but the iteration is necessary anyway.
          foreach($data->xpath("*") as $child) { 
            $value .= $child->asXml()." " ;
          }
          break ;
          
      } 
      $attributes[$key] = $value ;
    }
    return $attributes ;
  }
  
  protected function processNodeFromXML($node,$parentnodeid=null) {
    $nodeid = (string)$node['id'] ;
    $attributes = $this->processDataFromXML('node',$node) ;
    $this->addNode($nodeid,$attributes,$parentnodeid) ;
    foreach($node->graph as $subgraph) {
      $this->processGraphFromXML($subgraph,$nodeid) ;
    }
  }
  
  protected function processEdgeFromXML($edge) {
    $id = (string) $edge['id'] ;
    $source = (string) $edge['source'] ;
    $target = (string) $edge['target'] ; 
    $attributes = $this->processDataFromXML('edge',$edge) ;
    $this->addEdge($source,$target,$attributes,$id) ;    
  }
  /**
   * @param GraphML $graphmlString
   */
  public function __construct($graphmlString) {
    parent::__construct() ;
    // return $simpleXML->xpath('//span[@class="'.$classname.'"]') ;
    $this->document = simplexml_load_string($graphmlString) ;
    if ($this->document===false) {
      die("GraphMLReader: the graphml string passed is not a valid XML") ;
    } else {
      $this->processKeysFromXML($this->document) ;
      $this->processGraphFromXML($this->document->graph) ;
      
    }
  }
}