<?php defined('_MEGALIB') or die("No direct access") ;

require_once 'Json.php' ;

function array_frequencies($array) {
  foreach($array as $x) {
    if (!isset($freq[$x])) {
      $freq[(string)$x] = 1 ;
    } else {
      $freq[(string)$x] = $freq[(string)$x]+1 ;
    }
  }
  return $freq ;
}

/**
 * type Frequency(X) == Map(X,Integer>=0) 
 * 
 * Merge a Set of Frequencies
 * @param Set*(Frequency(X))!
 * @return Frequency(X)!
 */
function merge_frequencies($frequencies) {
  $n = count($frequencies) ;
  if ($n===0) {
    return array() ;
  } elseif ($n===1) {
    return $frequencies[0] ;
  } else {
    $fr = array_shift($frequencies) ;
    $f2 = merge_frequencies($frequencies) ;
    foreach($f2 as $x=>$n) {
      @ $fr[$x] += $n ;
    }
    return $fr ;
  }
}



// interpolated value between begin and end with nbSteps
function interpolate($value,$max,$begin=0,$end=100,$toInteger=false) {
  if ($begin < $end) {
    return (($end-$begin)*($value/$max))+$begin;
  } else {
    return (($begin-$end)*(1-($value/$max)))+$end;
  }
}


/*----------------------------------------------------------------------------------
 *     Summary
*----------------------------------------------------------------------------------
*/

/**
 * Create a summary of a map of map. That is create a structure
 * with cardinalities, domains, ranges, etc.
 * type MapOfMapSummary == Map{
 *   'kind' => 'mapOfMap',
 *   'domain1Card' => Integer,
 *   'domain1'     => Set*(Scalar) ?, // only if $returnSets
 *   'domain2'     => Set*(Scalar) ?, // only if $returnSets
 *   'domain2Card' => Map{
 *     'min' => Integer,
 *     'max' => Integer,
 *     'sum' => Integer,
 *     'unique' => Integer,
 *     'map' => Map(Scalar => Integer) ?  // only if $returnMaps
 *   }
 *   'range'       => Set*(Scalar) ?, // defined if $returnSets
 *   'rangeCard'   => Integer
 * }
 *
 * @param Map(Scalar,Map(Scalar,Value)) $mapmap A map of map
 *
 * @param Any? $valueIfEmpty If specified this value returned if the map
 * is empty. Default to null, so if nothing is provided, the summary
 * will be performed as usual but cardinalities will be 0, sets will be
 * empty, etc.

 * @param Boolean! $returnKind indicated if the kind attribute should be
 * returned.
 *
 * @param Boolean! $returnSets indicates if domains and range should be
 * returned. These may contains many values. Default is false.
 *
 * @param Boolean! $returnMaps indicates if the domain2Card map is returned.
 *
 * @return MapOfMapSummary|$valueIfEmpty
 */
function mapOfMapSummary($mapmap,$valueIfEmpty=null,$returnKind=false,$returnSets=false,$returnMaps=false) {
  if (count($mapmap)===0 && isset($valueIfEmpty)) {
    return $valueIfEmpty ;
  } else {
    $r = array() ;
    if ($returnKind) {
      $r['kind']='mapOfMap' ;
    }
    $r['domain1card']=count($mapmap) ;
    $r['domain1']=array_keys($mapmap) ;
    $r['domain2']=array() ;
    $r['range']=array() ;
    $r['domain2Card']=array() ;
    $r['domain2Card']['sum']=0 ;
    foreach($mapmap as $key1 => $map2) {
      $n = count($map2) ;
      if ($returnMaps) {
        $r['domain2Card']['map'][$key1] = count($map2) ;
      }
      if (!isset($r['domain2Card']['min']) || ($n < $r['domain2Card']['min'])) {
        $r['domain2Card']['min'] = $n ;
      }
      if (!isset($r['domain2Card']['max']) || ($n > $r['domain2Card']['max'])) {
        $r['domain2Card']['max'] = $n ;
      }
      $r['domain2Card']['sum'] += $n ;
      $r['domain2']=union($r['domain2'],array_keys($map2)) ;
      $r['range']=union($r['range'],array_values($map2)) ;
    }
    $r['domain2Card']['unique'] = count($r['domain2']) ;
    $r['rangeCard']=count($r['range']) ;
    if (!$returnSets) {
      unset($r['domain1']) ;
      unset($r['domain2']) ;
      unset($r['range']) ;
    }
    return $r ;
  }
}



/**
 * Create a summary of a map. For map of map it may be better to use
 * mapOfMapSummary as it provides more information.
 *
 * type MapSummary == Map{
 *   'kind' => 'map' ?,              // only if $returnKind
 *   'domain'     => Set*(Scalar) ?, // defined if $returnSets
 *   'domainCard' => Integer,
 *   'range'       => Set*(Scalar) ?, // defined if $returnSets
 *   'rangeCard'   => Integer
 * }
 *
 * @param Map*(Scalar,Any!) $map a map
 *
 * @param Any? $valueIfEmpty If specified this value returned if the map
 * is empty. Default to null, so if nothing is provided, the summary
 * will be performed as usual but cardinalities will be 0, sets will be
 * empty, etc.
 *
 * @param Boolean! $returnKind indicated if the kind attribute should be
 * returned.
 *
 * @param Boolean! $returnSets indicates if domain and range should be
 * returned. These may contains many values.
 *
 * @return MapSummary|$valueIfEmpty
 *
 */
function mapSummary($map,$valueIfEmpty=null,$returnKind=false,$returnSets=false) {
  if (count($map)===0 && isset($valueIfEmpty)) {
    return $valueIfEmpty ;
  } else {
    $r = array() ;
    if($returnKind) {
      $r['kind']='map' ;
    }
    $range=array_unique(array_values($map)) ;
    if ($returnSets) {
      $r['domain'] = array_keys($map);
      $r['range']=$range ;
    }
    $r['domainCard']=count($map) ;
    $r['rangeCard']=count($range) ;
    return $r ;
  }
}





/**
 * @param Any? $value a value or null
 */
function mixedValueSummary($value,$valueIfEmpty=null,$returnKind=false) {
  $valueToReturn = $value
    ? $value
    : (isset($valueIfEmpty)?$valueIfEmpty : $value) ;
  if (!$returnKind) {
    return $valueToReturn ;
  } else {
    $r = array() ;
    $r['kind']=typeOf($valueToReturn) ;
    $r['value']=$valueToReturn ;
    return $r ;
  }
}

/**
 * Return a summary for a given value according to its type.
 */
function valueSummary($value,$valueIfEmpty=null,$returnKind=false,$returnSets=false,$returnMaps=false) {
  if (is_map_of_map($value)) {
    return mapOfMapSummary($value,$valueIfEmpty,$returnKind,$returnSets,$returnMaps) ;
  } elseif (is_array($value)) {
    return mapSummary($value,$valueIfEmpty,$returnKind,$returnSets) ;
  } else {
    return mixedValueSummary($value,$valueIfEmpty,$returnKind) ;
  }
}



/*----------------------------------------------------------------------------------
 *     Synthesis of trees of maps
*----------------------------------------------------------------------------------
*/


class Synthesizer {

  /*----------------------------------------------------------------------------------
   *     Aggregating functions.
  *----------------------------------------------------------------------------------
  */


  /**
   * @param Fun:List*(Any1)->Any2 $aggregator
   * @param unknown_type $rootKey
   * @param unknown_type $value
   * @param unknown_type $childValues
   */
  public static function aggregate($aggregator,$rootKey,$value,$childValues) {
    $values = array($value) ;
    foreach ($childValues as $childId => $childValue) {
      $values[] = $childValue ;
    }
    return $aggregator($values) ;
  }

  public static function count($rootKey,$value,$childValues) {
    return self::aggregate('count',$rootKey,$value,$childValues);
  }

  public static function sum($rootKey,$value,$childValues) {
    return self::aggregate('array_sum',$rootKey,$value,$childValues) ;
  }

  public static function product($rootKey,$value,$childValues) {
    return self::aggregate('array_product',$rootKey,$value,$childValues) ;
  }

  public static function concat($rootKey,$value,$childValues) {
    return self::aggregate('array_concat',$rootKey,$value,$childValues) ;
  }

  public static function min($rootKey,$value,$childValues) {
    return self::aggregate('min',$rootKey,$value,$childValues) ;
  }

  public static function max($rootKey,$value,$childValues) {
    return self::aggregate('max',$rootKey,$value,$childValues) ;
  }

  public static function avg($rootKey,$value,$childValues) {
    return self::aggregate('array_avg',$rootKey,$value,$childValues) ;
  }

  public static function mergeAll($rootKey,$value,$childValues) {
    return self::aggregate('array_merge_all',$rootKey,$value,$childValues) ;
  }

  public static function replaceAll($rootKey,$value,$childValues) {
    return self::aggregate('array_replace_all',$rootKey,$value,$childValues) ;
  }

  public static function fusionAll($rootKey,$value,$childValues) {
    return self::aggregate('array_fusion_all',$rootKey,$value,$childValues) ;
  }

  public static function countAll($rootKey,$value,$childValues) {
    return self::aggregate('array_count_all',$rootKey,$value,$childValues);
  }



  /*----------------------------------------------------------------------------------
   *
  *----------------------------------------------------------------------------------
  */

  public static function prefixAll($rootKey,$map,$childMaps,$separator='/') {
    $results = $map ;
    foreach ($childMaps as $childId => $childMap) {
      foreach ($childMap as $key => $value) {
        $results[$childId.$separator.$key] = $value ;
      }
    }
    return $results ;
  }

}


function synthesizeMap($rootMap,$childMaps,$attibuteSynthesizer) {
  $result=array() ;
  foreach($rootMap as $rootKey => $rootValue) {
    $childValues = array() ;
    foreach($childMaps as $childId => $childMap) {
      $childValues[$childId]=$childMap[$rootKey] ;
    }
    $newmap = $attibuteSynthesizer($rootKey,$rootValue,$childValues) ;
    $result=array_fusion($result,$newmap) ;
  }
  return $result ;
}
