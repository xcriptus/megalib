<?php defined('_MEGALIB') or die("No direct access") ;
require_once('configs/Environment.config.php') ;
require_once('Strings.php') ;

/**
 * Execute a system (wrapping) command and return the output generated.
 * @param String! $command The (wrapping) command to execute. 
 * This (wrapping) command must start with ABSPATH_ENV_WRAPPERS_BIN/php-.
 * The list line of the output the wrapper generates should be the string
 * '-OK-' and its first parameter should be always 'PHP'.
 * @param List*(String!)! $parameters
 * @param &Integer $exitcode  
 * -1 if the $command is not a string.
 * -2 if $command does not start with ABSPATH_ENV_WRAPPERS_BIN
 * -3 if a parameter is not a string or is empty
 * the exitcode of the unix command executed otherwise
 * @param 'string'|'lines'|String $mode the format of the result. Default to 'lines'.
 * 'string' return the output as one string with $separator separating the lines of the output.
 * 'lines' return an array of string corresponding to the output lines
 * 'table' the lines are split according to $separator and an array of array is returned.
 * @param String? $separator the separator used either for spliting the lines into fields
 * in the case of the 'table' mode, or the output line separator in the 'string' mode.
 * This parameter is not used in lines mode. Default to "\t"
 * @return String!|List*(String!)|List*(List*(String!)|null 
 */
function systemGetOutput($command,$parameters,&$exitcode,$mode='lines',$separator="\t") {
  if (! is_string($command)) {
    $exitcode = -1 ;
    return null ;
  } else {
    if (!startsWith($command,ABSPATH_ENV_WRAPPERS_BIN.'php-')) {
      $exitcode = -2 ;
      return null ;
    }
  }
  $cmd = escapeshellcmd($command) ;
  foreach($parameters as $parameter) {
    if (! is_string($parameter) || $parameter==="") {
      $exitcode = -3 ;
      return null ;
    }
    $cmd .= ' '.escapeshellarg($parameter) ;
  }
  if (DEBUG>5) echo "Executing $cmd ..." ;
  exec($cmd,$output,$exitcode) ;
  $n = count($output) ;
  if ($n>=1 && $output[$n-1]==='-OK-') {
    unset($output[$n-1]) ;
    switch ($mode) {
      case 'string':
        return implode($separator,$output) ;
        break ;
      case 'lines':
        return $output ;
        break ;
      default:
        $result = array() ;
        foreach ($output as $line) {
          $result[] = explode($separator,$line) ;
        }
        return $result;
    }
  } else {
    return null ;
  }
}
