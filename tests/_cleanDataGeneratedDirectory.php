<?php
$directoryToClean='data/generated' ;
if (is_dir($directoryToClean)) {
  echo "<h2>Cleaning $directoryToClean</h2>" ;
  rrmdir($directoryToClean) ;
  mkdir($directoryToClean) ;
  echo "<h2>Directory $directoryToClean cleaned</h2>" ;
}

function rrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        $path = $dir."/".$object ;
        if (filetype($path) == "dir") {
          rrmdir($path); 
        } else {
          echo "<li>removing file $path</li>" ;
          unlink($path);
        }
      }
    }
    reset($objects);
    echo "<h3>Removing dir $dir</h3>" ;
    rmdir($dir);
  }
}