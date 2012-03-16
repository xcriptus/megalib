<?php

require_once 'RDF.php' ;
require_once 'Graph.php' ;

/**
 * Converter from RDF to Graphml
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
