<?php defined('_MEGALIB') or die("No direct access") ;
/**
 * Basic interface for accessing Github in an oo style rather than api procedural style
 * This interface is based on the php-github-api available from github.
 * @author jeanmariefavre
 * @status underDevelopment
 * 
 * TODO add support for hooks (@see http://help.github.com/post-receive-hooks/)
 */

/**
 * See the content of the file below to configure your system and use this library.
 */
require_once 'configs/Github.config.php' ;
require_once 'Files.php' ;



/*-----------------------------------------------------------------
 *    Repositories
 *-----------------------------------------------------------------
 */

/**
 * Provides on-demand access to a github repository.
 * This class is to be used with GithubBlob, GithubDirectory, 
 * GitubContributor, etc.
 */
class GithubRepository {
  protected $github ;
  /**
   * @var String the owner of the repository, that is an github account.
   */
  protected $username ;
  /**
   * @var String the name of the repository.
   */
  protected $reponame ;
  
  
  /**
   * @var Map*(String!,GithubContributor!)? The list of contributors.
   * Includes contributors without account but also github users.
   * Created on demand but all contributors are loaded at once. 
   */
  protected $contributorsCachedAtOnce = NULL ;
  /**
   * @var Map+(TagName!,Sha!)? The sha of each tagname.
   * Created on demand but loaded at once
   */
  protected $tagsInfoCachedAtOnce = NULL ;
  
  /**
   * @var Map*(Sha!,GithubObject!)!
   */
  protected $objectMapCache = array() ;
  
  
  
  
  /**
   * Direct access to the php Github api.
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
   * Returns the list of contibutors (some being users).
   * @return List*<ContributorInfo!> 
   */
  public function getContributors() {
    if (!isset($this->contributorsCachedAtOnce)) {
      $infos =  
        $this->github->getRepoApi()
          ->getRepoContributors($this->username,$this->reponame, true);
      
      $this->contributorsCachedAtOnce = array();
      foreach ($infos as $info) {
        
        if (isset($info['type']) && $info['type']=='User') {
          $contributor = new GithubUser($info) ;
        } else {
          $contributor = new GithubContributor($info) ;
        }
        $this->contributorsCachedAtOnce[$contributor->getId()] = $contributor ;
      }
    }
    return $this->contributorsCachedAtOnce ;
  }
  
  /**
   * Return a contributor or user given a account name or a name in "".
   * If no such contributor exist then return null.
   * @param String! $id the login of the github user of the name between quotes of a 
   * contributor
   * @return GithubContributor|GithubUser|? null if there is no such contributor or user. 
   */
  public function getContributor($id) {
    $contributors = $this->getContributors() ;
    if (isset($contributors[$id])) {
      return $contributors[$id] ;
    } else {
      return null ;
    }
  }

  
  
  /**
   * Information about tags (that is branch)
   * TODO should be renamed and most probably it would make sense to revert the array
   * TODO check what is the difference between a tag and a branch
   * @return Map+<TagName!,Sha!>!
   */
  public function getTagsInfo() {
    if (! isset($this->tagsInfoCachedAtOnce)) {
      $this->tagsInfoCachedAtOnce = 
        $this->github->getRepoApi()
          ->getRepoBranches($this->username,$this->reponame) ;
    }
    return $this->tagsInfoCachedAtOnce ;      
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
   * @return GithubTree? The tree if the tagname is valid. Null otherwise.
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
 *-----------------------------------------------------------------
 *    Contributors and users
 *-----------------------------------------------------------------
 *
 * Github users have an account on github. They are particular cases
 * of "contributors", some of them being just referenced somehow in
 * the commits. With github there is no id for contibutors, but with
 * this API we build this notion by using the 'login' as Id for users,
 * and for contributors that are not user, we use __<name>__ with __ 
 * enclosing the name. For instance __maria joe__  
 *
 * The following types may be incompleted.
 * They have been inferred from some tests.
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
 *   
 * type UserInfo == ContributorInfo
 *      where type=='User'
 */


class GithubContributor {
  /**
   * @var ContributorInfo!
   */
  public $info ;
  
  /**
   * This method is overloaded for users, so here this will be
   * the default for contributors that are not users.
   * @return String!
   */
  public function getId() {
    return '__'.$this->info['name'].'__' ;
  }
  
  
  /**  
    * @param Contributorinfo! $info The information about the user.
    * GithubUser::__constructor should be used to create github users.
    */  
  public function __construct($info) {
    $this->info = $info ;
  }
}



/**
 * GitUsers are the contributors that have a github account.
 * They therefore have a login.
 */
class GithubUser extends GithubContributor {
  
  /**
   * Return the login of the user. This method overloads the method
   * in the super class.
   * @return String!
   */
  public function getId() {
    return $this->getLogin() ;
  }
  
 
  /**
   * Return the username of a github user.
   * @return String! the login of the github user. 
   */
  public function getLogin() {
    return $this->info['login'] ;
  }
  
  
  /**
   * @param UserInfo $userinfo The information about the user. 
   * The 'type' field has to be "User"
   */
  public function __construct($userinfo) {
    parent::__construct($userinfo) ;
    assert('$this->info["type"]=="User"') ;
  }
  
}




/*-----------------------------------------------------------------
 *    Trees
*-----------------------------------------------------------------
*/


/**
 * Either a GithubTree (directory) or a GithubBlob (file).
 * Note that "blobs" are mere "content"
 * 
 * type ObjectInfo == Map{
 *   type:'blob'|'tree',
 *   name:String!,
 *   size:Integer!,
 *   sha:SHA!,
 *   mode:String!, 
 *   mime_type: String!
 *   fullname:String!    // computed. Not in the original api
 *   extension:String!   // idem. Allways empty for directories.
 * }
 * 
 */
abstract class GithubObject {
  protected /*GithubRepository!*/ $repository ;
  protected /*Sha!*/ $sha ;
  protected /*GithubObjectInfo?*/ $info=NULL ;  /*May not be known*/
  protected /*GithubTree?*/ $parent = NULL ;
  protected /*String!*/ $fullname = NULL ;
  
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
  
  public function getFullName() {
    return $this->info['fullname'] ;
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
    if (isset($info)) {
      $this->info['fullname'] = 
          (isset($this->parent) ? $this->parent->getFullName().'/' : '')
          . $this->info['name'] ;
      $this->info['extension'] = fileExtension($this->info['name']);
    } 
  } 
}


/**
 * Represents a github plain file.
 */
class GithubBlob extends GithubObject {
  
  /**
   * @param GithubRepository! $repository
   * @param Sha! $sha
   * @param GithubObjectInfo? $info Information about the object if known.
   * @param GithubTree? $parent Parent (tree) of the object if any and if known.
   */
  public function __construct($repository,$sha,$info=NULL,GithubTree $parent=NULL) {
    parent::__construct($repository,$sha,$info,$parent) ;
    
  }
}


/**
 * Represents a github directory.
 */
class GithubTree extends GithubObject {
  protected /*Map*<Sha!,GithubObject!>?*/ $objectMapCachedAtOnce = NULL ;
  
  public /*Map*<Sha!,GithubObject!>?*/ function getObjectMap() {
    if (! isset($this->objectMapCachedAtOnce)) {
      $objectsinfo = 
        $this->repository->getGithubClient()->getObjectApi()
          ->showTree($this->repository->getUsername(), 
                     $this->repository->getRepositoryName(), 
                     $this->sha);
      //echo arrayMapToHTMLTable($objectsinfo,'---') ;
      
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
      $this->objectMapCachedAtOnce = $objectmap ;
    }
    return $this->objectMapCachedAtOnce ;
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
    parent::__construct($repository,$sha,$info,$parent) ;
    $this->objectListCache = NULL ;
  }
  
}


/**
 * @author jmfavre
 *
 */
class GithubFile {
  /**
   * @var HTML $html a html representation of the file with syntax coloring
   */
  protected $html ;
  
  protected $name ;
  
  public function getHighlighted() {
    return $this->html ;
  }
  public function __construct($url) {
    $this->name = $url ;
    $file = file_get_contents($url) ;
    if ($file === false) {
      $this->html = null ;
    } else {
      $this->html = $file ;
    }
  }
}


function getFile($url) {
  
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  $fp = fopen("data/generated/example_homepage.txt", "w");
  curl_setopt($ch, CURLOPT_FILE, $fp);  
  curl_exec($ch);
  curl_close($ch);
  fclose($fp);
}