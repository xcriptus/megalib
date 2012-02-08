<?php
/**
 * RDF representation of Github.
 * Assume that the ARC2 library is installed (see github.com/semsol/arc2)
 * @author jeanmariefavre
 * @status underDevelopment
 */
require_once 'RDF.php' ;


class GithubAsRDF {
  
  protected /*RDFTripleSet!*/ $tripleSet ;
  
  /**
   * @return Set*<RDFTriple!>!
   */
  public function getTriples() {
    return $this->tripleSet->triples ;
  }
  
  /**
   * Add a repository to the set of triples.
   * @param GithubRepository $repository
   * @param Set*<String!*>? $branchesToFollow Names of the branches to follow.
   */
  public function /*void*/ githubRepositoryAsTriples(
      GithubRepository $repository,
      $branchesToFollow=array('master')) {
    
    $username = $repository->getUsername() ;
    $reponame = $repository->getRepositoryName() ;
    $sourceuri = $username.'/'.$reponame ; 
    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type','Repository') ;
    $this->tripleSet->addTriple('data',$sourceuri,'username',$username) ; 
    $this->tripleSet->addTriple('data',$sourceuri,'repositoryName',$reponame) ;
    
    if ($branchesToFollow) {
      foreach($branchesToFollow as $branchname) {
        /*GithubTree?*/ $tree = $repository->getBranchTree($branchname) ;
        if (isset($tree)) {
          // add triples to model explicitly the branch
          $branchsha=$tree->getSha() ;
          $this->tripleSet->addTriple('link',$sourceuri,'branch',$branchsha) ;
          $this->tripleSet->addTriple('data',$branchsha,'name',$branchname) ;
          
          // add triples for the tree
          $this->githubTreeAsTriples($tree) ;
        }
      }
    }
  }
  
  /**
   * @param GithubTree $tree
   */
  public function githubTreeAsTriples(GithubTree $tree ){
    echo '<br/>#' ;
    $sourceuri =$tree->getSha() ;
    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type','Tree') ;
    if (isset($tree->info)) {
      $this->tripleSet->addMapAsTriples('data', $sourceuri, $tree->info) ;
    }
    foreach ($tree->getBlobList() as $blob) {
      $this->githubBlobAsTriples($blob) ;
    }
    foreach ($tree->getTreeList($tree) as $subtree) {
      $this->githubTreeAsTriples($subtree) ;
    }
  }
  
  
  /**
   * @param GithubBlob $blob
   */
  public function githubBlobAsTriples(GithubBlob $blob ){
    echo '.' ;
    $sourceuri =$blob->getSha() ;
    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type','Blob') ;
    if (isset($blob->info)) {
      $this->tripleSet->addMapAsTriples('data', $sourceuri, $blob->info) ;
    }
  }
  
  /**
   * @param String! $dataprefix
   * @param String! $ontologyprefix
   */
  public function __construct($dataprefix,$ontologyprefix) {
    $this->tripleSet = new RDFTripleSet($dataprefix,$ontologyprefix) ;
  }
}