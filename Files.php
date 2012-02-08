<?php
/*
 * Basic function for FileSystem manipulations
 */

require_once("Strings.php") ;

function /*String!*/ fileExtension($url) {
  $dotpos = strrpos($url,'.') ;
  if ($dotpos===false) {
    $extension = "" ;
  } else {
    $extension = substr($url,$dotpos+1) ;
  }
  return $extension ;
}

function /*String!*/ fileCoreName($url) {
  $name = basename($url) ;
  $dotpos = strrpos($name,'.') ;
  if ($dotpos===false) {
    $corename = $name ;
  } else {
    $corename = substr($name,0,$dotpos) ;
  }
  return $corename ;
}

function /*Boolean!*/ isFile($url) {
  if (startsWith($url,"http:") || startsWith($url,"file:")) {
    assert("false") ; // not implemented.
  } else {
    @ $type = filetype($url) ;
    return $type==="file" ;
  }
}

function /*Boolean!*/ isReadableFile($url) {
  @ $handle = fopen($url,"r") ;
  if ($handle === false) {
    return false ;
  } else {
    fclose($handle);
    return true ;
  }
}

function /*Boolean!*/ isReadableDirectory($url) {
  if (startsWith($url,"http:") || startsWith($url,"file:")) {
    assert("false") ; // not implemented. We should read the content of the directory
  } else {
    @ $type = filetype($url) ;
    return $type==="dir" ;
  }
}

// return NULL for the / directory
function /*String?*/ parentDirectory(/*Path!*/ $url) {
  if (startsWith($url,"http:") || startsWith($url,"file:")) {
    assert("false") ; // not implemented. We should read the content of the directory
  } else {
    if ($url==='.') {
      return ".." ;
    } else if ($url==="/") {
      return NULL ;
    } else {
      // if the path ends with a / remove it first
      if (preg_match('/\/$/',$url)) {
        $path = substr($url,0,-1) ;
      } else {
        $path = $url ;
      }
      // get the position of the the last /
      $pos = strrpos($path,'/') ;
      if ($pos === FALSE) {
        // this is a relative path
        return "." ;
      } else {
        return addToPath("",substr($path,0,$pos)) ;
      }      
    }
  } 
}

function /*String!*/ addToPath($path,$path2) {
  return 
    $path 
    . (endsWith($path,'/') ? "" : "/")
    . (startsWith($path2,'/') ? substr($path2,1) : $path2) ;
}

// regexpr should be of the form /xxx/
function /*Set*<String!>?*/ listFileNames($url,
                                          $typefilter="dir|file",
                                          $nameregexpr=NULL,
                                          $ignoredot=TRUE,
                                          $prefixWithDirectory=TRUE) {
  if ($dh = opendir($url)) {
    $paths = array() ;
    $allowedtypes=explode('|',$typefilter) ;
    while (($file = readdir($dh)) !== false) {
      $type = filetype(addToPath($url,$file)) ;
      $selected = in_array($type,$allowedtypes) 
                  && ($ignoredot!==TRUE || substr($file,0,1)!='.')
                  && (!isset($nameregexpr) || preg_match($nameregexpr,$file)) ; 
      if ($selected) {
        $paths[] = ($prefixWithDirectory ? addToPath($url,"") : "") .$file ;
      }
    }
    return $paths ;
  } else {
    return NULL ;
  }
}

// regexpr should be of the form /xxx/
// List all files|directories under the current file|directory including this one.
// By contrast to listFileNames allways return an array with at least the root
function /*Set*<String!>!*/ listAllFileNames($url,$typefilter="dir|file",$nameregexpr=NULL,$ignoredot=TRUE) {
  $results = array() ;
  if (isFile($url)) {
// echo "file: $url<br>" ;
    return array($url) ;
  } else if (! isReadableDirectory($url)) {
    return array() ;
  } else {
    // this is a directory
    $results[] = $url ;
//echo "directory: $url" ;
    $children = listFileNames($url,$typefilter,$nameregexpr,$ignoredot) ;
    if ($children === NULL) {
// echo " is empty<br>" ;
      return $results ;
    }
// echo " has ".count($children)." children<br>" ;

    foreach ($children as $child) {
      $childforest = listAllFileNames($child,$typefilter,$nameregexpr,$ignoredot) ;
      // don't care if elements cannot be read (this is the case if the rool is a file)
      // print_r($childforest) ;
      if ($childforest !== NULL) {
        $results = array_merge($results,$childforest) ;
      }
    }
    return $results ;
  }
}


