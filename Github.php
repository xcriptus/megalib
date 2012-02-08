<?php
/**
 * Basic interface for accessing Github in an oo style rather than api procedural style
 * This interface is based on the php-github-api available from github.
 * @author jeanmariefavre
 * @status underDevelopment
 */

/**
 * See the content of the file below to configure your system and use this library.
 */
require_once 'config/configGithub.php' ;


/**
 * Provides on-demand access to a github repository.
 */
class GithubRepository {
  protected $github ;
  protected $username ;
  protected $reponame ;
  
  protected /*Map+<TagName!,Sha!>?*/ $tagsInfoCache = NULL ;
  protected /*Map*<Sha!,GithubObject>!*/ $objectMapCache = array() ;
  
  /**
   * Direct access to the Github api.
   * @return Github_Client!
   */
  public function getGithubClient() {
    return $this->github ;
  }
   
  /**
   * Owner of the repository.
   * @return String!
   */
  public function getUsername() {
    return $this->username ;
  }
  
  /**
   * Name of the repository.
   * @return String!
   */
  public function getRepositoryName() {
    return $this->reponame ;
  }
  
  
  /**
   * TODO currently unused
   * @param GithubObject $gitobject
   */
  public function addObjectToCache(GithubObject $gitobject) {
    $this->objectMapCache[$gitobject->getSha()] = $gitobject ;
  }
  
  /**
   * TODO currently unused
   */
  public function getProperties() {
    
  }
  
  /**
   * The following type may be incompleted. 
   * It has been inferred from some tests.
   * 
   * type ContributorInfo == Map{
   *     contributions:Integer!,
   *     gravatar_id:String?,
   *     type:'User'?,
   *     login:String?,
   *     name:String?,
   *     company:String?
   *     location:String?
   *     blog:String?
   *     email:String? 
   *   }
   */
  
  /**
   * @param Boolean $all should all contributors be returned.
   * In this case contributors are not necessarily Github user
   * and may have only a few property defined.
   * @return List*<ContributorInfo!> 
   */
  public function getContributorsInfo($all=false) {
    return 
      $this->github->getRepoApi()
        ->getRepoContributors($this->username,$this->reponame, $all);
  }
  
  /**
   * Information about tags (that is branch)
   * TODO should be renamed and most probably it would make sense to revert the array
   * TODO check what is the difference between a tag and a branch
   * @return Map+<TagName!,Sha!>!
   */
  public function getTagsInfo() {
    if (! isset($this->tagsInfocache)) {
      $this->tagsInfocache = 
        $this->github->getRepoApi()
          ->getRepoBranches($this->username,$this->reponame) ;
    }
    return $this->tagsInfocache ;      
  }
  
  /**
   * Return the Sha of a given tag or null if this tag does not exist.
   * TODO check what is the difference between a tag and a branch
   * @param String! $tagname
   * @return Sha?
   */
  public function /*Sha?*/ getTagSha($tagname) {
    $tagsinfo=$this->getTagsInfo() ;     
    if (isset($tagsinfo[$tagname])) {
      return $tagsinfo[$tagname] ;
    } else {
      return NULL ;
    }
  }
  
  /**
   * Tree associated with a branch.
   * TODO check what is the difference between a tag and a branch
   * @param String? $tagname tag name ('master' by default)
   * @return GithubTree?
   */
  public function getBranchTree($tagname='master') {
    $sha = $this->getTagSha($tagname) ;
    if ($sha==NULL) {
      return NULL ;
    } else {
      return new GithubTree($this,$sha) ;
    }
  }
  
    
  /**
   * @param String! $username
   * @param String! $reponame
   */
  public function __construct($username,$reponame) {
    $this->github = new Github_Client();
    $this->username = $username ;
    $this->reponame = $reponame ;
  }
}



/**
 * Either a GithubTree (directory) or a GithubBlob (file).
 */
abstract class GithubObject {
  protected /*GithubRepository!*/ $repository ;
  protected /*Sha!*/ $sha ;
  protected /*GithubObjectInfo?*/ $info=NULL ;  /*May not be known*/
  protected /*GithubTree?*/ $parent = NULL ;
  
  /**
   * @return Sha! Sha of the current object.
   */
  public function getSha() {
    return $this->sha ;
  }
  // TODO the method below may return null. It depends on how the object have been
  // created. It would be nice to find some way to get the information instead of
  // returning NULL, but is there a mean in the API to do this?
  public function /*?*/ getInfo() {
    return $this->info ;
  }

  /**
   * @return String! Raw data describing the current object.
   */
  public function getRawData() {
    return 
      $this->repository->getGithub()->getObjectApi()
        ->getRawData( $this->repository->getUsername(), 
                      $this->repository->getRepositoryName(), 
                      $this->sha);
  }
  /**
   * @param GithubRepository! $repository The containing repository.
   * @param Sha! $sha Sha of the object.
   * @param GithubObjectInfo? $info Information about the object if known.
   * @param GithubTree? $parent Parent (tree) of the object if any and if known.
   */
  public function __construct(GithubRepository $repository,$sha,$info=NULL,GithubTree $parent=NULL) {
    $this->repository = $repository ;
    $this->sha = $sha ;
    $this->info = $info ;
    $this->parent = $parent ;
  } 
}

/**
 * Basically represents a plain file in a github tree.
 */
class GithubBlob extends GithubObject {
  
  /**
   * @param GithubRepository! $repository
   * @param Sha! $sha
   * @param GithubObjectInfo? $info Information about the object if known.
   * @param GithubTree? $parent Parent (tree) of the object if any and if known.
   */
  public function __construct($repository,$sha,$info=NULL,GithubTree $parent=NULL) {
    parent::__construct($repository,$sha,$info) ;
  }
}


/**
 * Basically represents a directory in github.
 */
class GithubTree extends GithubObject {
  protected /*Map*<Sha!,GithubObject!>?*/ $objectMapCache = NULL ;
  
  public /*Map*<Sha!,GithubObject!>?*/ function getObjectMap() {
    if (! isset($this->objectMapCache)) {
      $objectsinfo = 
        $this->repository->getGithub()->getObjectApi()
          ->showTree($this->repository->getUsername(), 
                     $this->repository->getRepositoryName(), 
                     $this->sha);
      $objectmap = array() ;
      foreach($objectsinfo as $objectinfo) {
        $type = $objectinfo['type'] ;
        $sha = $objectinfo['sha'] ;
        if ($type == 'blob') {
          $objectmap[$sha] = new GithubBlob($this->repository,$sha,$objectinfo,$this) ;
        } elseif ($type == 'tree') {
          $objectmap[$sha] = new GithubTree($this->repository,$sha,$objectinfo,$this) ;
        } else {
          assert('false') ;
        }
      }
      $this->objectMapCache = $objectmap ;
    }
    return $this->objectMapCache ;
  }
  
  public /*List*<GithubObject!>?*/ function getObjectList() {
    return array_values($this->getObjectMap()) ;
  }
  
  public /*List*<GithubTree!>?*/ function getTreeList() {
    $trees = array() ;
    foreach ($this->getObjectList() as $object) {
      if ($object instanceof GithubTree) {
        $trees[] = $object ;
      }
    } 
    return $trees ;
  }

  public /*List*<GithubTree!>?*/ function getBlobList() {
    $blobs = array() ;
    foreach ($this->getObjectList() as $object) {
      if ($object instanceof GithubBlob) {
        $blobs[] = $object ;
      }
    }
    return $blobs ;
  }
    
  // TODO change this to a blob object
  public function getBlob($blobpath) {
    return
      $this->repository->getGithub()->getObjectApi()
        ->showBlob($this->repository->getUsername(),
                   $this->repository->getRepositoryName(),
                   $this->sha,
                   $blobpath);
  }
  
  
  public function getAllBlobsInTree() {
    return
      $this->repository->getGithub()->getObjectApi()
        ->listBlobs($this->repository->getUsername(),
                    $this->repository->getRepositoryName(),
                    $this->sha);
  }
  
  public function __construct($repository,$sha,$info=NULL,GithubTree $parent=NULL) {
    parent::__construct($repository,$sha,$info) ;
    $this->objectListCache = NULL ;
  }
  
}
