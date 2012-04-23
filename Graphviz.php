<?php

require_once 'Graph.php' ;

/**
 * Graphviz structure.
 */

class GraphvizWriter extends GraphWriter {
  
  protected function quote($nodeid) {
    return '"'.str_replace('"', '\"', $nodeid).'"' ;
  }
    
  protected function attributeMapToGraphString($kind,$attvalues,$indent='') {
    $out = '' ;
    if (count($attvalues) != 0) {
      $out .= '[' ;
      foreach ($attvalues as $attid => $value) {
        $out .= ' '.$this->quote($attid).'='.$this->quote($value) ;
      }      
      $out .= ']' ;
    }
    return $out ;
  }
  
  protected function nodeToGraphString($nodeid,$indent='') {
    $attmap = $this->g->getNodeAttributes($nodeid) ;
    $out = $indent.'    '.$this->quote($nodeid) ;
    $out .= ' '.$this->attributeMapToGraphString("node",$attmap,'    ') ;
    $out .= " ;\n" ;
    return $out ;
  }
  
  protected function edgeToGraphString($edgeid,$indent='') {
    $sourceid = $this->g->getEdgeSource($edgeid) ;
    $targetid = $this->g->getEdgeTarget($edgeid) ;
    $attmap = $this->g->getEdgeAttributes($edgeid) ;
    $out = $indent.'    ' ;
    $out .= $this->quote($sourceid)." -> ";
    $out .= $this->quote($targetid) ;
    $out .= ' '.$this->attributeMapToGraphString("edge",$attmap,'    ') ;
    $out .= " ;\n" ;
    return $out ; 
  }
  
  protected function headerToGraphString() {
    $out = $this->g->getEdgeDefault() === "directed" 
           ? "digraph"
           : "graph" ;
    $out .= " ".$this->g->getGraphName()." {\n" ;
    return $out ;
  }
  
  protected function footerToGraphString() {
    return "}" ;
  }
  
  /**
   * Overload the standard method because currently this version
   * do not support nested node in graphviz (it should in the future)
   * so displaying only root nodes (as in standard method) is not ok.
   * TODO we could add nesting links
   * @return GraphString! a string representing the whole graph.
   */
  public function graphToGraphString() {
    $str = $this->headerToGraphString() ;
    foreach($this->g->getNodes() as $nodeid) {
      $str .= $this->nodeToGraphString($nodeid) ;
    }
    foreach($this->g->getEdges() as $edgeid) {
      $str .= $this->edgeToGraphString($edgeid) ;
    }
    $str .= $this->footerToGraphString() ;
    return $str ;
  }
  
  public function __construct(Graph $graph) {
    parent::__construct($graph) ;
  }

}

