<?php
/**
 * Simple typed graph based on an explicit entity-relationship schema representation.
 */

require_once 'Strings.php' ;

/**
 * A very simple hand-craft schema structure with an little ad-hoc textual language for
 * fast experimentation and scripting. 
 * Should be improved but in the mean time...
 * 
 * Here is an example of schema definition with 4 types of entities (feature,implementation,...)
 * and various attributes definitions with the following tags
 * 
 * feature {
 *   name:string@;description:string?;implementations:implementation*abstract
 * }
 * implementation {
 *   name:string@; 
 *   motivation:string!; 
 *   features:feature*; 
 *   technologies:technology*
 * }
 * language{name:string@;implementations:implementation*}
 * technology{name:string@;implementations:implementation*}
 * 
 * 
 * SchemaExpression ::= ( EntityKind '{' AttributeSetExpression '}' )*
 * AttributeSetExpression ::=  | AttributeSetExpression ';' AttributeExpression
 * AttributeExpression ::= AttributeName [ ':' Type ] Tag
 * 
 * Note that all spaces,tabs,and line feeds are TOTALLY removed
 * 
 * type Tag ==
 *     '@'  // means that the attribute is the key.
 *   | '?'  // means that the attribute is optional.
 *   | '!'  // means that the attribute is mandatory.
 *   | '*'  // means that the attribute is a set of references to another type.
 * In the latter case the value is a list of keys of the target entity type.
 * 
 */
class SimpleSchema {

  protected $defaultAttributeType = 'string' ;
  
  /**
   * type SchemaDescription == Map(EntityKind!,AttributeDescription)
   * type AttributeSetDescription == Map(AttributeName!,AttributeDescription)
   * type AttributeDescription == Map{'name':String!, 'type':EntityKind!, 'tag':Tag! })) '
   * 
   * @var SchemaDescription!
   */
  protected $schemaDescription ;
  
  
  //------------------------------------------------------------------------------
  //      Declaration addition and parsing
  //------------------------------------------------------------------------------
    
  /**
   * Remove all comments of the form //, but also all spaces, tabs and new lines
   * @param String! $expr
   * @result String! the string cleaned
   */
  protected function removeCommentAndSpaces($expr) {
    $s = removeComments($expr) ;
    $s = preg_replace('/[ \t\n]/','',$s) ;
    return $s ;
  }
  
  /**
   * Add a set of entity declarations to the schema. 
   * Note that existing declarations are kept so this is indeed a merge of entity types
   * and attribute definitions. 
   * @param SchemaExpression! $attributeSetExpr
   * @return void
   */
  public function addSchemaExpression($schemaExpression) {
    $s = $this->removeCommentAndSpaces($schemaExpression) ;    
    // the } serve as a delimiter between entities
    $entityKindExpressions=explode('}',$s) ;
    
    // foreach entityExpr of the form  entityKind{attributeSetExpression
    foreach($entityKindExpressions as $entityExpr) {
      if ($entityExpr!='') {
        
        $x=explode('{',$entityExpr) ;
        
        assert('count($x)==2') ;
        $entityKind = $x[0] ;
        $attributeSetExpr =  $x[1] ;
        
        // add the attribute set
        $this->addAttributeSetExpression($entityKind,$attributeSetExpr) ;
      }
    }    
  }
  
  /**
   * Add a set of attribute declaration to the schema. 
   * Note that existing declarations are kept so this is indeed a merge of 
   * attribute definitions. 
   * @param $entityKind The kind of entity on which the attributes are defined
   * @param AttributeSetExpression! $attributeSetExpr
   * @return void
   */
  public function addAttributeSetExpression($entityKind,$attributeSetExpr) {
    // clean the expression (necessary if this function is called directly)
    $cleanAttributeSetExpr = $this->removeCommentAndSpaces($attributeSetExpr) ;    
        
    // create the entity kind if not already existing
    if (! isset($this->schemaDescription[$entityKind])) {
      $this->schemaDescription[$entityKind] = array() ;
    }

    $attributeExprs = explode(';',$cleanAttributeSetExpr) ;
    foreach($attributeExprs as $attributeExpr) {
      $this->addAttributeExpression($entityKind,$attributeExpr) ;
    }
  }
  
  
  /**
   * Add an attribute to the schema overriding a previous declaration if any.
   * @param  $entityKind The kind of entity on which the attribute is defined
   * @param AttributeExpression! $attributeExpr
   * @result void
   */
  public function addAttributeExpression($entityKind,$attributeExpr) {
    // clean the expression (necessary if this function is called directly)
    $cleanAttributeExpr = $this->removeCommentAndSpaces($attributeExpr) ;
    if ($cleanAttributeExpr!='') {
      // separate the tag from the rest
      $tag=substr($cleanAttributeExpr,-1,1) ;      
      $rest=substr($cleanAttributeExpr,0,strlen($cleanAttributeExpr)-1) ;
      $segments=explode(':',$rest) ;
      
      assert('count($segments)<=2') ;
      $name=$segments[0] ;
      $type=(isset($segments[1]) ? $segments[1] : $this->defaultAttributeType) ; 
      $this->schemaDescription[$entityKind][$name]=array() ;
      $this->schemaDescription[$entityKind][$name]['name']=$name ;
      $this->schemaDescription[$entityKind][$name]['type']=$type ;
      $this->schemaDescription[$entityKind][$name]['tag']=$tag ;
    }
  }
  
  //------------------------------------------------------------------------------
  //      Accessors
  //------------------------------------------------------------------------------
  
  /**
   * Return the entity kinds defined in the schema
   * @return Set*<EntityKind>!
   */
  public function getEntityKinds() {
    return array_keys($this->schemaDescription) ;
  }
  
  
  /**
   * Return the all attributes description for a given entity.
   * @param String! $entitykind
   * @param Tag? $onlyWithThisTag If specified only the attribute with this tag will be returned.
   * @return Map*(AttributeName!,Map{'name':String?,'tag':Tag?,'type':String?}!)!
   */
  public function getAttributeDescriptions($entitykind, $onlyWithThisTag=NULL) {
    assert('isset($this->schemaDescription[$entitykind])') ;
    if ($onlyWithThisTag==null) {
      return $this->schemaDescription[$entitykind] ;
    } else {
      $result = array() ; 
      foreach ($this->schemaDescription[$entitykind] as $attname => $attdescr) {
        if ($attdescr['tag']==$onlyWithThisTag) {
          $result[$attname] = $attdescr ;
        }
      }
    }  
    return $result ;    
  }
  
  /**
   * Return the all attributes description for a given entity kind.
   * @param String! $entitykind
   * @return Set*<AttributeName!>!
   */
  public function getReferenceDescriptions($entitykind) {
    return $this->getAttributeDescriptions($entitykind,'*') ;
  }
  
  /**
   * Return the name of the key attribute for a given entity kind.
   * @param EntityKind! $entitykind
   * @return AttributeName!
   */
  public function /*AttributeName!*/ getKeyAttribute($entitykind) {
    $attibutenames = array_keys($this->getAttributeDescriptions($entitykind,'@')) ;
    assert('count($attibutenames)==1') ;
    return $attibutenames[0] ;
  }
  
  /**
   * Create a schema from a given SchemaExpression or create an empty schema.
   * @param String? $schemaExpression An schema expression to initialize the schema add.
   * If nothing is provided then the schema will be empty.
   * @param String? $defaultAttributeType
   */
  public function __construct($schemaExpression=null, $defaultAttributeType='string') {
    $this->schemaDescription = array() ;
    $this->defaultAttributeType = $defaultAttributeType ;
    if (isset($schemaExpression)) {
      $this->addSchemaExpression($schemaExpression) ;
    }
  }
}





/**
 * A simple graph based on some nested array structure and with a explicit schema definition.
 * The structure of the graph is directly exposed in a white box manner:
 * Map*<EntityKind!,Map<EntityId!,Map!<AttributeName!,Mixed>!)!)!
 */
class SimpleGraph {
  
  /**
   * @var SimpleSchema! Schema of the graph. This schema defines the type of entities as well
   * as the type of attributes and references.
   */
  public $SCHEMA ;

  /**
   * @var Map*<EntityKind!,Map<EntityId!,Map!<AttributeName!,Mixed>!)!)! The graph represented 
   * by and nested array. For instance in the following expression
   * $DATA['feature']['cut']['name'] = 'The feature cut makes it ...' 
   * 'feature' is the kind of entity, 'cut' is the entity id, 'name' is the name of the attribute
   * and the whole expression refers to the value of this attribute for that entity of this type.
   * This value can either be a scalar value or an array of references.
   */
  public $DATA ;
  
   
  /**
   * @param unknown_type $tag
   * @return multitype:|string
   */
  public function ghostValue($tag) {
    if ($tag=='*') {
      return array() ;
    } else {
      return "UNDEFINED" ;
    }
  } 
  
  /**
   * @param unknown_type $entitykind
   * @param unknown_type $entityid
   * @return unknown
   */
  public function /*EntityId!*/ addGhostEntity($entitykind,$entityid) {
    $this->DATA[$entitykind][$entityid]=array() ;
    foreach ($this->SCHEMA->getAttributeDescriptions($entitykind) as $attributename=>$attinfo) {
      $this->DATA[$entitykind][$entityid][$attributename]=$this->ghostValue($attinfo['tag']) ;
    }
    return $entityid ;
  }
  
  /**
   * Check that all references refers to an entity that exist. If this is not the case
   * then create a 'ghost' entity of the target type with the key value corresponding
   * to the value of the reference. At the end of this function all references are therefore
   * defined although some ghost entities were added. The collection of ghosts is returned by
   * sorted by entity kind.
   * @return Map*(EntityKind!,Set*(EntityId)!)! the list of ghosts entities that have been added
   */
  public function checkReferentialConstraints() {
    $ghostsAdded = array() ;
    foreach ($this->SCHEMA->getEntityKinds() as $entitykind) {

      if (isset($this->DATA[$entitykind])) {
        // check all reference attributes
        foreach ($this->SCHEMA->getReferenceDescriptions($entitykind) as $attributename => $attinfo) {
          $targettype=$attinfo['type'] ;
          
          // check all values that are in the 
          foreach ($this->DATA[$entitykind] as $entitykey => $entityinfo) {
            if (isset($entityinfo[$attributename])) {
              $targets = $entityinfo[$attributename]  ;
              foreach ($targets as $target) {
                if (! isset($this->DATA[$targettype][$target])) {
                  if (DEBUG) echo "<li><b>Undefined reference target:</b> ".$entitykey." --".$attributename.':'.$targettype.'--> '.$target ;
                  $this->addGhostEntity($targettype,$target) ;
                  $ghostsAdded[$targettype][] = $target ;
                }
              }
            }
          }
        }
      }
    }
    return $ghostsAdded ;
  }
  
  /**
   * Check the constraints on the graph.
   * This function will display errors but will also attempt to fix them.
   * TODO currently check only referential constraints, 
   * but domain constraints should be also added
   */
  public function checkConstraints() {
    $this->checkReferentialConstraints() ;
  }
  
  /**
   * Construct an empty graph with a given schema.
   * @param SimpleSchema! $schema
   */
  public function __construct($schema) {
    $this->DATA = array() ;
    $this->SCHEMA = $schema ;
  }
}

