<?php
require_once '../GraphML.php' ;
require_once '../HTML.php' ;
define('DEBUG','6') ;
 $file = 'data/input/countries.graphml' ;
$file = 'data/input/mgl1.graphml' ;
$xml = file_get_contents($file) ;
$graphmlReader = new GraphMLReader($xml) ;
$graph = $graphmlReader->getGraph() ;
$graphmlWriter = new GraphMLWriter($graph) ;
echo htmlAsIs($graphmlWriter->graphToGraphString()) ;


/*
<y:ProxyAutoBoundsNode>
<y:Realizers active="0">
<y:GroupNode>
<y:NodeLabel alignment="right" autoSizePolicy="content" borderDistance="0.0" fontFamily="Dialog" fontSize="15" fontStyle="bold" hasBackgroundColor="false" hasLineColor="false" height="22.37646484375" horizontalTextPosition="right" iconData="5" iconTextGap="4" modelName="internal" modelPosition="tl" textColor="#000080" verticalTextPosition="top" visible="true" width="122.35693359375" x="0.0" y="0.0">
Company.xsd
</y:NodeLabel>
<y:Fill color="#9999FF" transparent="false"/>
<y:Geometry height="149.93840195453964" width="143.0" x="764.2798998237802" y="-380.8891872530955"/>
<y:BorderStyle hasColor="false" type="line" width="1.0"/>
<y:Shape type="roundrectangle"/>
<y:State closed="false" closedHeight="50.0" closedWidth="50.0" innerGraphDisplayEnabled="false"/>
<y:NodeBounds considerNodeLabelSize="true"/>
<y:Insets bottom="3" bottomF="3.0" left="3" leftF="3.0" right="3" rightF="3.0" top="3" topF="3.0"/>
<y:BorderInsets bottom="10" bottomF="9.99315817780365" left="18" leftF="18.149169921875" right="5" rightF="5.229248046875" top="9" topF="8.742316750299779"/>
</y:GroupNode>
<y:GenericNode configuration="ShinyPlateNode3">
<y:NodeLabel alignment="left" autoSizePolicy="content" fontFamily="Dialog" fontSize="15" fontStyle="plain" hasBackgroundColor="false" hasLineColor="false" height="22.37646484375" horizontalTextPosition="right" iconData="7" iconTextGap="4" modelName="internal" modelPosition="tl" textColor="#000080" verticalTextPosition="bottom" visible="true" width="88.19677734375" x="4.0" y="4.0">
Company
</y:NodeLabel>
<y:Fill color="#9999FF" transparent="false"/>

<y:Geometry height="30.0" width="112.369140625" x="786.6815111519052" y="-346.7704056590457"/>
<y:BorderStyle hasColor="false" type="line" width="1.0"/>
<y:StyleProperties>
<y:Property class="java.lang.Double" name="ModernNodeRadius" value="5.0"/>
</y:StyleProperties>
</y:GenericNode>
*/
// $simpleXML = simplexml_load_string($this->getHighlightedHTML()) ;
// if ($simpleXML===false) {
// die('error: HMTL is not valid XML') ;
// } else {
// $this->highlightedAsSimpleXML = $simpleXML ;
// }

// $dom = new DomDocument();
// $dom->load("doc.xml");

// $xpath = new DOMXPath($dom);
// $xpath->registerNamespace("g", "http://graphml.graphdrawing.org/xmlns");

// $nodes = $xpath->query("//g:node");

// foreach ($nodes as $node) {
// var_dump($node->nodeValue) ;
// }

// $edges = $xpath->query("//g:edge");

// foreach ($edges as $edge) {
// var_dump($edge->nodeValue) ;
// }