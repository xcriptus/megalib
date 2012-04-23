<?php

require_once 'RDF.php' ;
require_once 'NAGraph.php' ;

/**
 * Converter from RDF to NAGraph
 */
class RDFAsNAGraph {
  

  /**
   * @var RDFConfiguration
   */
  protected $rdfConfiguration ;


  /**
   * Return a NAGraph from a RDFTripleSet.
   * @param RDFTripleSet! $tripleset The triple set.
   * @return NAGraph? The graph generated or null in case of error.
   */
  public function rdfTripleSetAsGraph(RDFTripleSet $tripleset) {
    $this->rdfConfiguration = $tripleset->rdfConfiguration ;
    return $this->rdfTriplesAsGraph($tripleset->triples);
  }

  /**
   * Return a NAGraph from a set of RTDTriples.
   * @param Set*<RDFTriple!>! $triples The set of triples to convert.
   * @return NAGraph? The graph generated or null in case of error.
   */
  public function rdfTriplesAsGraph($triples) {
    $g = new NAGraph() ;
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
    return $g ;
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
