<?php
require_once 'main.config.local.php' ;
require_once '../Environment.php' ;
require_once '../HTML.php' ;
echo '<h2>grepdir files . a </h2>' ; 
$out = systemGetOutput(ENV_GREPDIR_CMD,array('files','.','a'),$errcode,'string',"\n") ;

echo htmlAsIs($out) ;
var_dump($errcode) ;



