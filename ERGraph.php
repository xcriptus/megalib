<?php defined('_MEGALIB') or die("No direct access") ;
/**
 * Basic support for Entity Relationship (ER) Graphs with explicit ER schema.
 */

require_once 'Strings.php' ;

/**
 * A very simple hand-craft entity-relationship schema structure.
 * Schemas can both be constructed via the API or expressed in
 * a very little ad-hoc textual language for fast experimentation 
 * and scripting. 
 * 
 * Should be improved but in the mean time...
 * 
 * Here is an example of schema definition with 4 types of entities 
 * (Feature,Implementation,...) and various attributes definitions with 
 * the following tags
 * 
 * Feature {
 *   name:string@;description:string?;implementations:implementation*
 * }
 * Implementation {
 *   name:string@; 
 *   motivation:string!; 
 *   features:Feature*; 
 *   technologies:Technology*;
 *   anyEntities:Entity*   // a polymorphic list of references.
 * }
 * Language{name:string@;implementations:Implementation*}
 * Technology{name:string@;implementations:Implementation*}
 * 
 * 
 * The syntax of the language is here
 * 
 * SchemaExpression ::= ( EntityKind '{' AttributeSetExpression '}' )*
 * AttributeSetExpression ::=  | AttributeSetExpression ';' AttributeExpression
 * AttributeExpression ::= AttributeName [ ':' Type ] Tag
 * 
 * Note that ALL spaces,tabs,and line feeds are TOTALLY and brutally removed
 * 
 * Polymorphic references can be done with 
 * 
 * type Tag ==
 *     '@'  // means that the attribute is a string attribute which serves as the key.
 *   | '?'  // means that the attribute is a scalar attribute which is optional.
 *   | '!'  // means that the attribute is a scalar attribute which is mandatory.
 *   | '*'  // means that the attribute is a set of references.
 * 
 * TODO: currently cardinality serves to makes differences between references
 * and scalar attributes. This is no reason do to that. It would be much better
 * to have basic types and entity types declarations.
 * 
 * In the latter case the value is a list of keys of the target entity type.
 * 
 */
class ERSchema {
  
  const GENERIC_ENTITY_TYPE = 'Entity' ;

  protected $defaultAttributeType = 'string' ;
  
  /**
   * type SchemaDescription == Map(EntityKind!,AttributeSetDescription)
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
   * @param SchemaExpression! $expr
   * @result SchemaExpression! the string cleaned
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
 * A simple Entity/Relationship (ER) Graph based on an explicit schema definition.
 * The structure of the graph is directly exposed in a white box manner as an
 * indexed structure.
 * type ERAttributeValue  == ScalarValue | List*(ReferenceValue)
 * type ReferenceValue == Map*('type'=>EntityKind!,'id'=>String!)
 * type ERGraphData == Map*(EntityKind!,
 *                         Map(EntityId!,
 *                             Map!(AttributeName!,ERAttributeValue>!)!)!
 * That is for each entity kind, for a given entity id, and a given attribute
 * the structure return either a scalar value or a list of references to other entities.
 */
class ERGraph {
  
  /**
   * @var Schema! Schema of the graph. This schema defines the type of entities as well
   * as the type of attributes and references.
   */
  public $SCHEMA ;

  /**
   * @var ERGraphData! The graph represented by and nested arrays. 
   * Here is an example of usage.
   * $erg->DATA['feature']['cut']['name'] = 'The feature cut makes it ...' 
   *              ^  'feature' is the kind of entity, 
   *                        ^ 'cut' is the entity id
   *                                 ^ 'name' is the name of the attribute
   *                                           ^ this is the value of the attribute
   * The whole expression refers to the value of this attribute for that entity of this type.
   * This value can either be a scalar value or an array of references.
   */
  public $DATA ;
  
  /**
   * Indicates if the value is a reference
   * @param Attribute $value
   * @return Boolean 
   */
  public function isReference($value) {
    return is_array($value) && isset($value['type']) ;
  }
  
  /**
   * Return the type of an actual reference
   * @param ReferenceValue $reference
   * @return EntityKind! the entity kind
   */
  public function getReferenceType($reference) {
    return $reference['type'] ;
  }
  
  /**
   * Return the id of the entity refered by an actual reference
   * @param ReferenceValue $reference
   * @return EntityId! the id entity of the entity
   */
  public function getReferenceKey($reference) {
    return $reference['id'] ;
  }
  
   
  /**
   * Return a undefined value compatible with the given tag.
   * @param Tag! $tag
   * @return ERAttributeValue!
   */
  public function ghostValue($tag) {
    if ($tag=='*') {
      return array() ;
    } else {
      return "UNDEFINED" ;
    }
  } 
  
  /**
   * Create a new "empty" entity with all fields initalized to a ghost value.
   * @param EntityKind! $entitykind
   * @param EntityId! $entityid
   * @return EntityId! The newly created entity.
   */
  public function addGhostEntity($entitykind,$entityid) {
    $this->DATA[$entitykind][$entityid]=array() ;
    foreach ($this->SCHEMA->getAttributeDescriptions($entitykind) as $attributename=>$attinfo) {
      $this->DATA[$entitykind][$entityid][$attributename]=$this->ghostValue($attinfo['tag']) ;
    }
    return $entityid ;
  }
  
  
  /**
   * Check a reference value against a declaration. 
   * Die if this is not a reference or if the type is not correct.
   * If the entity does not exist then create it as a ghost entity.
   * * @param EntityId! $sourceEntityKey The source entity that contains the reference
   * @param EntityKind! $sourceEntityKind The kind of source entity
   * @param AttributeName! $sourceAttributeName The name of the attribute that contains the reference
   * @param EntityKindç $declaredTargetType The type of the attribute as declared. Could be 'Entity'
   * @param Mixed $reference The reference to check
   * @return Boolean true if there was no problem, false means that a ghost entity has been created
   */
  protected function checkReference(
      $sourceEntityKey,
      $sourceEntityKind,
      $sourceAttributeName,
      $declaredTargetType,
      $reference) {
    $msg="checkReference: $sourceEntityKind($sourceEntityKey).$sourceAttributeName ";
    if (! $this->isReference($reference)) {
      die($msg.': not a reference') ;
    }
    $actualReferenceType = $this->getReferenceType($reference) ;
    $actualReferenceKey = $this->getReferenceKey($reference) ;
    $isPolymorphicTarget = ($declaredTargetType === ERSchema::GENERIC_ENTITY_TYPE) ;
    if (! $isPolymorphicTarget && $actualReferenceType !==$declaredTargetType) {
      // TODO here should go a inheritance check if needed in future version
      die($msg." is of type $actualReferenceType while it must be $declaredTargetType") ; 
    }
    if (! isset($this->DATA[$actualReferenceType][$actualReferenceKey])) {
      if (DEBUG>10) echo "<li><b> $msg = $actualReferenceType($actualReferenceKey). This object was not existing. Created now";
      $this->addGhostEntity($actualReferenceType,$actualReferenceKey) ;
      return false ;
    } else {
      return true ;
    }
  }
  
  /**
   * Check that all references refers to an existing entity of a proper type
   * according to the schema. See checkReference above.
   * The collection of ghosts is returned by
   * sorted by entity kind.
   * @return Set*(ReferenceValue) the set of ghosts entities that have been added because
   * of dangling references.array
   */
  public function checkReferentialConstraints() {
    $ghostsAdded = array() ;
    foreach ($this->SCHEMA->getEntityKinds() as $entitykind) {

      if (isset($this->DATA[$entitykind])) {
        // check all reference attributes
        foreach ($this->SCHEMA->getReferenceDescriptions($entitykind) as $attributename => $attinfo) {
          // get the type as declared in the schema. Could be a polymorphic declaration.
          $targettype=$attinfo['type'] ;
          
          // check all values that are in the 
          foreach ($this->DATA[$entitykind] as $entitykey => $entityinfo) {
            if (isset($entityinfo[$attributename])) {
              $targets = $entityinfo[$attributename]  ;
              foreach ($targets as $target) {
                if ($this->checkReference($entitykey,$entitykind,$attributename,$targettype,$target)==false) {
                  $ghostsAdded[] = $target ;                  
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
   * @param Schema! $schema
   */
  public function __construct($schema) {
    $this->DATA = array() ;
    $this->SCHEMA = $schema ;
  }
}

