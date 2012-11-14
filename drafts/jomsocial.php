<?php
require_once '../tests/main.config.local.php' ;

require_once '../CSV.php' ;
define('SEP',',') ;
define('CSV_FIELDS','C:\\DOWNLOADS\\mega_community_fields.csv') ;

define("FIELD_ID",1) ;
define("FIELD_TYPE",2) ;
define("FIELD_NAME",14) ;



define('DBCSV','C:\\DOWNLOADS\\mega_community_fields_values.csv') ;

define('NEWCSV','C:\\_WWW\\JOOMLA_PLANET_SL\\SOTESOLA\mega_community_fields_values.csv') ;


$csv_fields = getAndShow(CSV_FIELDS,false,',',true) ;
foreach ($csv_fields->getListOfMaps() as $map) {
  // ignore group (they don't have field name)
  if (isset($map[FIELD_NAME])) {
    $jomsocial_field[$map[FIELD_NAME]]=$map[FIELD_ID] ;
    echo "(".$map[FIELD_ID].")  ".$map[FIELD_NAME]." : ".$map[FIELD_TYPE] ."</br>";
  }
}

echo "<h1>END OF TESTS </h1>" ;


function getAndShow($filename,$hasheader=false,$separator=",",$show=false) {
  $csv=new CSVFile() ;
  $csv->load($filename,$hasheader,null,$separator) ;
  if ($show) {
    echo "<li>Filename: ". $filename ;
    echo "<li>Headers: " ;
    echo implode ('   ',$csv->getHeader())."<br/>" ;
    echo "<li>Keys: " ;
    echo implode ('   ',$csv->getAllRowKeys())."<br/>" ;
    echo "<h4>Content</h4>" ;
    var_dump($csv->getListOfMaps()) ;
  }
  return $csv ;
}