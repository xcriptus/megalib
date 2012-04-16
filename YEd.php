<?php
require_once 'GraphML.php' ;


/**
 * Deals with the HTML file exported by the yEd editor. 
 */
class YEdHTML {
  const REGEXPR_AREA_LINE = '#<area shape="([a-z]*)" coords="([0-9,]*)" (href=".*" )?alt="".* onmouseover="showTooltip\(\'(.*)\'\)#' ;
  //  "$D/ -> ../repo
  //  target="_blank"  ->  target="detail"
  public static function getImageAreas($html) {
    $areaMap = array() ;
    preg_match_all(self::REGEXPR_AREA_LINE,$html,$matches,PREG_SET_ORDER) ;
    foreach ($matches as $match) {
      if (preg_match('/href="(.*)"/',$match[3],$urlmatch)) {
        $url=$urlmatch[1] ;
      } else {
        $url="" ;
      }
      $areaMap[$match[4]] = array(
          "shape" => $match[1],
          "coords" => $match[2],
          "url" => $url) ;
    }
    return $areaMap ;
  }
}




/**
 * Describes a Yed palette.
 * The corresponding file is saved in the user application directory
 * (e.g. C:\Documents and Settings\<theUser>\Application Data\)
 * in the palette directory ((e.g. yWorks\yEd\palette) as a regular
 * graphml file.
 */
class YedPalette extends GraphML {

}

/**
 * Describes a Yed palette set.
 * The corresponding file is saved in the user application directory
 * (e.g. C:\Documents and Settings\<theUser>\Application Data\)
 * in the palette directory as palette_info.xml
 */
class YedPaletteSet {


}

class YedPropertyMapper {

}