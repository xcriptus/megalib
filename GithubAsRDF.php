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
   * @return RDFTripleSet!
   */
  public function getTripleSet() {
    return $this->tripleSet ;
  }
  
  
  /**
   * Add a repository to the set of triples.
   * @param GithubRepository $repository
   * @param Set*<String!*>? $branchesToFollow Names of the branches to follow.
   */
  public function /*void*/ githubRepositoryAsTriples(
      GithubRepository $repository,
      $branchesToFollow=array('master')) {
    
    //---- basic information ----
    $username = $repository->getUsername() ;
    $reponame = $repository->getRepositoryName() ;
    $sourceuri = $username.'/'.$reponame ; 
    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type','Repository') ;
    $this->tripleSet->addTriple('data',$sourceuri,'username',$username) ; 
    $this->tripleSet->addTriple('data',$sourceuri,'repositoryName',$reponame) ;
    
    
    //---- contributors -----
    foreach ($repository->getContributors() as $contributor){
      //echo "Adding contributor ".$contributor->getId().'</br>' ;
      $contributoruri = $this->githubContributorAsTriples($contributor) ;
      $this->tripleSet->addTriple('link',$sourceuri,'hasContributors',$contributoruri) ;
    }
    //---- branches -----
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
   * Add a contributor to the triples
   * @param GithubContributor! $contributor
   * @return URI! the URI of the contributor
   */
  public function /*void*/githubContributorAsTriples(
      GithubContributor $contributor) {
    $sourceuri=$contributor->getId() ;
    $type = 'contributor' ;
    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type',$type) ;
    $this->tripleSet->addMapAsTriples('data',$sourceuri,$contributor->info) ;
    return $sourceuri ;
  }
  
  
  /**
   * @param GithubTree $tree
   */
  public function githubTreeAsTriples(GithubTree $tree){
    echo '<li><b>'.$tree->getFullName().'</b></li>' ;
    $sourceuri = $tree->getSha() ;
    
    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type','Tree') ;
    if ($tree->getInfo()!==null) {
      $this->tripleSet->addMapAsTriples('data', $sourceuri, $tree->getInfo()) ;
    }
    foreach ($tree->getBlobList() as $blob) {
      $this->githubBlobAsTriples($blob) ;
      $this->tripleSet->addTriple('link',$blob->getSha(),'parent',$sourceuri) ;
    }
    foreach ($tree->getTreeList($tree) as $subtree) {
      $this->githubTreeAsTriples($subtree) ;
      $this->tripleSet->addTriple('link',$subtree->getSha(),'parent',$sourceuri) ;
    }
  }
  
  
  /**
   * @param GithubBlob $blob
   */
  public function githubBlobAsTriples(GithubBlob $blob ){
    echo '<li>'.$blob->getFullName().'</li>' ;
    $sourceuri =$blob->getSha() ;

    $this->tripleSet->addTriple('type',$sourceuri,'rdf:type','Blob') ;
    if ($blob->getInfo()!==null) {
      $this->tripleSet->addMapAsTriples('data', $sourceuri, $blob->getInfo()) ;
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