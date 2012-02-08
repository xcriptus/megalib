<?php
/*
 * Simple typed graph based on an explicit entity-relationship schema representation.
 */

class SimpleSchema {
  protected $schema ;
  public function /*Set*<EntityKind!>!*/ getEntityKinds() {
    return array_keys($this->schema) ;
  }
  
  public function /*Map*<AttributeName!,Tag!>!*/ getAttributeDescriptions($entitykind, $filtertag=NULL) {
    assert('isset($this->schema[$entitykind])') ;
    $attributestr = $this->schema[$entitykind] ;
    $result = array() ;
    foreach(explode(' ',$attributestr) as $attributespec) {
      if ($attributespec != '') {
        $attributeandtype=substr($attributespec,0,strlen($attributespec)-1) ;
        $tag=(substr($attributespec,-1,1)) ;
        if ($filtertag == NULL || $tag==$filtertag ) {
          $segments=explode(':',$attributeandtype) ;
          assert('count($segments)==2') ;
          $attributename=$segments[0] ;
          $attributetype=$segments[1] ;
          $result[$attributename]['name']=$attributename ;
          $result[$attributename]['tag']=$tag ;
          $result[$attributename]['type']=$attributetype ;
        }
      }
    }
    return $result ;
  }
  
  public function /*Set*<AttributeName!>!*/ getReferenceDescriptions($entitykind) {
    return $this->getAttributeDescriptions($entitykind,'*') ;
  }
  
  public function /*AttributeName!*/ getKeyAttribute($entitykind) {
    $attibutenames = array_keys($this->getAttributeDescriptions($entitykind,'@')) ;
    assert('count($attibutenames)==1') ;
    return $attibutenames[0] ;
  }
  
  public function __construct($schemaarray) {
    $this->schema = $schemaarray ;
  }
}



class SimpleGraph {
  public $R ;
  public $schema ;
  
  public function ghostValue($tag) {
    if ($tag=='*') {
      return array() ;
    } else {
      return "UNDEFINED" ;
    }
  }
  
  public function makeEntityId($entitykind,$keyvalue) {
    return $entitykind.':'.strtolower($keyvalue) ;
  }
  
  
  public function /*EntityId!*/ addGhostEntity($entitykind,$entityid) {
    $this->R[$entitykind][$entityid]=array() ;
    foreach ($this->schema->getAttributeDescriptions($entitykind) as $attributename=>$attinfo) {
      $this->R[$entitykind][$entityid][$attributename]=$this->ghostValue($attinfo['tag']) ;
    }
    return $entityid ;
  }
  
  public function /*Set*<EntityId!>!*/ checkReferentialConstraints() {
    $ghostsAdded = array() ;
    foreach ($this->schema->getEntityKinds() as $entitykind) {
      foreach ($this->schema->getReferenceDescriptions($entitykind) as $attributename => $attinfo) {
        $targettype=$attinfo['type'] ;
        foreach ($this->R[$entitykind] as $entitykey => $entityinfo) {
          $targets = $entityinfo[$attributename] ;
          foreach ($targets as $target) {
            if (! isset($this->R[$targettype][$target])) {
              if (DEBUG) echo "<li><b>Undefined reference target:</b> ".$entitykey." --".$attributename.':'.$targettype.'--> '.$target ;
              $this->addGhostEntity($targettype,$target) ;
              $ghostsAdded[] = $target ;
            }
          }
        }
      }
    }
  }
  
  public function checkConstraints() {
    $this->checkReferentialConstraints() ;
  }
  
  public function __construct($schema) {
    $this->R = array() ;
    $this->schema = $schema ;
  }
}

