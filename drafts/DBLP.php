<?php
define('DBLP_SERVER','http://dblp.uni-trier.de') ;
define('DBLP_ALL_AUTHORS',DBLP_SERVER.'db/indices/AUTHORS') ;
define('DBLP_PUBLICATION_PREFIX',DBLP_SERVER.'/rec/bibtex/') ;
define('DBLP_SEARCH_AUTHOR_PREFIX',DBLP_SERVER.'/search/author?xauthor=') ;
define('DBLP_PERSON_PUBLICATIONS_PREFIX',DBLP_SERVER.'/rec/pers/') ;


/**
 * Interface to DBLP.
 * @See http://dblp.uni-trier.de/xml/docu/dblpxmlreq.pdf for the details of the
 * XML api.
 */
class DBLP {
  /**
   * Get a publication associated with a given PublicationId
   * @param PublicationId! $publicationId
   * @return Publication? a valid publication object if the publication exist or null otherwise
   */
  public function getPublication($publicationId) {
    $publication = new Publication($publicationId) ;
    return $publication->isValid ? $publication : NULL ; 
  }
  
  /**
   * Search for authors.
   * @param unknown_type $authorQuery
   * @return Set*<Author!>? If the query produce an error return NULL.
   * Otherwise return an array that can be empty or not depending
   * of the query.
   */
  public function searchAuthors($authorQuery) {
    @$xml = simplexml_load_file(DBLP_SEARCH_AUTHOR_PREFIX.$authorQuery) ;
    if ($xml === false) {
      echo 'KO' ;
      return NULL ;
    } else {
      echo 'OK' ;
      $authors = array() ;
      foreach ($xml->author as $authornode) {
        $id = (string)$authornode['urlpt'] ;
        // FIXME: this does not seems to work.
        $name = html_entity_decode((string)$authornode,ENT_NOQUOTES,'ISO-8859-1') ;
        $authors[] = new Author($id,$name) ;
      }
      return $authors ;
    }    
  }
}

/**
 * A publication, that is any kind of bibliographic entry.
 *
 */
class Publication {
  /**
   * @var Boolean Is the publication valid? 
   */
  public /*Boolean*/ $isValid ;
  /**
   * @var PublicationId? The id of the publication or undefined if
   * the publication is not valid.
   */
  public $id ;
  /**
   * @var List*(String!)? List of author names (as strings) or undefined if
   * the publication is not valid.
   */
  public $authors ;
  public $title ;
  public $url ;
  public /*?*/$electronicEdition ;
  // TODO complete the list of attribute available, may be with subclasses
  public $xml ;
  public function __toString() {
    if ($this->isValid) {
      return implode(', ',$this->authors).', "<b>'.$this->title.'"</b>' ;
    } else {
      return '<InvalidPublication>' ;
    }
  } 
  /**
   * @param PublicationId! $publicationId
   */
  public function __construct($publicationId) {
    @ $this->xml = simplexml_load_file(DBLP_PUBLICATION_PREFIX.$publicationId.'.xml') ;
    $this->isValid = ($this->xml !== false) ;
    if ($this->isValid) {
      $this->id = $publicationId; 
      $this->electronicEdition = $this->xml->xpath('//ee') ;
      $this->url = $this->xml->xpath('//url') ;
      $this->authors = $this->xml->xpath('//author') ;
      $titles = $this->xml->xpath('//title') ;
      $this->title=$titles[0] ;
    }
  }
}

/**
 * Author of publications.
 * Authors are identified by strings like m/Meier_0004:Michael
 * type AuthorId == String /[a-z]\/[a-zA-Z:=_0-9&]/
 * 
 */
class Author {
  
  /**
   * @var AuthorId
   */
  public $id ;
  
  /**
   * @var UTF8String $name
   */
  public $name ;
  
  /**
   * @var List*(PublicationId!)?  
   */
  protected  $publicationIdsCache ;
  
  /**
   * 
   */
    
  /**
   * @return List*(PublicationId!)? The list of publications ids by the author
   * or null if the request produces an error for some reason.
   */
  public function getPublicationIds() {
    // TODO JUST A DRAFT
    // Should add support for homonyms and home page
    // In fact return various kind of information
    $url = DBLP_PERSON_PUBLICATIONS_PREFIX.$this->id.'/xk' ;
    @ $xml = simplexml_load_file($url) ;
    if ($xml !== false) {
      echo 'OK' ;
      var_dump($xml) ;
    } else {
      echo 'KO '.$url ;
      return NULL ;
    }
  }
  
  public function getCoauthorIds() {
    // TODO JUST A DRAFT
    
    $url = DBLP_PERSON_PUBLICATIONS_PREFIX.$this->id.'/xc' ;
    @ $xml = simplexml_load_file($url) ;
    if ($xml !== false) {
      echo 'OK' ;
      var_dump($xml) ;
    } else {
      echo 'KO '.$url ;
      return NULL ;
    }
  }
  
  public function __construct($id,$name) {
    $this->id = $id ;
    $this->name = $name ;
    $this->publicationIdsCache = NULL ;
  }
}



$dblp = new DBLP() ;

echo '<?html version="1.0" encoding="UTF-8"?>' ;
$publication = $dblp->getPublication('journals/acta/BayerM72') ;
echo isset($publication) ? $publication : "<p>no such publication</p>" ;
 var_dump($publication->xml);


$authors = $dblp->searchAuthors('ralf lammel') ;
var_dump($authors) ;
foreach ($authors as $author) {
  echo "<p>Loading information for ".$author->name ;
  $author->getPublicationIds() ;
  $author->getCoauthorIds() ;
}

