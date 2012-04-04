<?php

require_once 'Graph.php' ;

/**
 * GraphML structure.
 * @see http://graphml.graphdrawing.org/ for the graphml format
 * @see http://www.yworks.com/products/yed/ of the yed free graphml editor
 */

class GraphML extends Graph {

  const GRAPH_HEADER = '<?xml version="1.0" encoding="UTF-8"?>
  <graphml xmlns="http://graphml.graphdrawing.org/xmlns"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">
  ' ;
  const GRAPH_FOOTER = '</graphml>' ;

  const NESTED_GRAPH_SUFFIX ='-subgraph' ;

  protected $graphmlNodeId = array() ;
  protected $graphmlEdgeId = array() ;

  protected function /*GraphMLString!*/ attributeTypeToString($typename){
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
      case 'null':    // in case where a triple contains a null value
        return 'string';
        break ;
      default:
        die('Graph.php: attribute of type "'.$typename.'" not supported in graphml') ;
    }
  }

  /**
   * Represents the schema of a graph, that is definition of attributes, in graphml.
   * @param String? optional indentation
   * @return GraphMLString!
   */
  protected function /*GraphMLString!*/ schemaToString($indent='') {
    $out = '' ;
    foreach($this->schema as $kind=>$attdef) {
      foreach($attdef as $attname => $attributeinfo) {
        if (DEBUG>15) {
          var_dump($attdef) ;
        }
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
   * @return GraphMLString! a series of <data> elements.
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
   * @param GraphMLString! $nodeid
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
   * @return GraphMLString! a <edge> element with potentially the list of edge attributes.
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
   * @return GraphMLString! a <graph> element with all nodes and edges.
   */
  public function /*GraphMLString!*/ graphToString() {
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
  public function /*void*/ graphToGraphMLFile($filename) {
    $str = $this->graphToString() ;
    file_put_contents( $filename,$str) ;
  }

  public function __construct($graphname = null) {
    parent::__construct($graphname) ;
  }

}


class GraphMLAsHTML {
  const REGEXPR_AREA_LINE = '#<area shape="([a-z]*)" coords="([0-9,]*)" (href=".*" )?alt="".* onmouseover="showTooltip\(\'(.*)\'\)#' ;
  //  "$D/ -> ../repo
  //  target="_blank"  ->  target="detail"
  public static function getImageAreas($html) {
    $areaMap = array() ;
    preg_match_all(self::REGEXPR_AREA_LINE,$html,$matches,PREG_SET_ORDER) ;
    foreach ($matches as $match) {
      if (preg_match('/href="(.*)"/',$match[3],$urlmatch)) {
        $url=$urlmatch[1] ;
      } else {
        $url="" ;
      }
      $areaMap[$match[4]] = array(
          "shape" => $match[1],
          "coords" => $match[2],
          "url" => $url) ;
    }
    return $areaMap ;
  }     
}




/**
 * Describes a Yed palette.
 * The corresponding file is saved in the user application directory
 * (e.g. C:\Documents and Settings\<theUser>\Application Data\)
 * in the palette directory ((e.g. yWorks\yEd\palette) as a regular
 * graphml file.
 */
class YedPalette extends GraphML {
  
}

/**
 * Describes a Yed palette set.
 * The corresponding file is saved in the user application directory
 * (e.g. C:\Documents and Settings\<theUser>\Application Data\)
 * in the palette directory as palette_info.xml
 */
class YedPaletteSet {
  
  
}

class YedPropertyMapper {
  
}