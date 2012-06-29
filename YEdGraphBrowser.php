<?php defined('_MEGALIB') or die("No direct access") ;
require_once 'Strings.php' ;
require_once 'SourceFileSystem.php' ;
require_once 'YEd.php' ;

class YEdGraphBrowserGenerator {
  
  /**
   * @var NAGraph The graph for which to produce the area map
   */
  protected $graph;
  /**
   * @var DirectoryName! The directory where all files will be generated
   */
  protected $targetDirectory ;
  /**
   * @var FullFileName! The full name of the yedGraph file (with probably ".graphml" extension).
   */
  protected $yedGraphFullFileName ;
  
  
  protected $sourceDefinitions ;
  /**
   * @var The directory where original/raw source files are.
   */
  protected $sourceFileDirectory ;
  
  /**
   * @var The name of the frame in which node or links url will go
   */
  protected $targetFrameName = "detail" ;
  
  public function getYedGraphFullFileName() {
    return $this->yedGraphFullFileName ; 
  }
  
  public function getYedGraphCoreName() {
    return withoutOptionalSuffix(basename($this->yedGraphFullFileName),'.graphml') ;
  }
  public function getYedGraphExportedImageFullFileName() {
    return withoutOptionalSuffix($this->yedGraphFullFileName,'.graphml').'1_1.png' ;
  }
  
  public function getYedGraphExportedHtmlFullFileName() {
    return withoutOptionalSuffix($this->yedGraphFullFileName,'.graphml').'.html' ;
  }
  
  
  //---- generated highlighed version of source files with corresponding fragments
  
  protected function generateAHighlightedSourceFileAndItsFragments($filename,$language,$fragments=null) {
    echo "processing $filename as $language </br>" ;
    $sourceFile=
      new SourceFile(
          $this->sourceFileDirectory.'/'.$filename,
          null,
          null,
          $language) ;
    $sourceFile->generate($this->targetDirectory,$fragments)   ;  
  }

  protected function generateAllHighlightedSourceFilesAndTheirFragments() {
    foreach ($this->sourceDefinitions as $file) {
      $filename = $file[0] ;
      $language = $file[1] ;
      @ $fragments = $file[2] ;
      $this->generateAHighlightedSourceFileAndItsFragments($filename,$language,$fragments) ;
    }
  }
  
  
  //--- edge processing ----------------------------------------------------

  protected function processEdge($edgenumber,$edgeid,$sourceurl,$targeturl) {
    echo "Creating file e$edgenumber.html</br>" ;
  
    file_put_contents($this->targetDirectory.'/'."e".$edgenumber.".html",'
        <html>
        <frameset cols="48%,48%">
        <frame src="'.$sourceurl.'" />
        <frame src="'.$targeturl.'" />
    </frameset>
    </html>
    ') ;
  
  }
  
  protected function processAllEdges() {
    $edgenumber = 0 ;
    foreach($this->graph->getEdges() as $edgeid) {
      $source = $this->graph->getEdgeSource($edgeid) ;
      $target = $this->graph->getEdgeTarget($edgeid) ;
      $sourceinfo=$this->graph->getNodeAttributes($source) ;
      $targetinfo=$this->graph->getNodeAttributes($target) ;
      $this->processEdge($edgenumber,$edgeid,$sourceinfo['d7'],$targetinfo['d7']) ;
      $edgenumber++ ;
    }
  }
  
  
  //---- generate Yed Graph Map Files and copy the image

  /**
   * Get the image area map from the Yed Generated export. 
   * @return ImageAreaMap! 
   */
  protected function getOriginalImageAreaMap() {
    $exportedHtmFile = $this->getYedGraphExportedHtmlFullFileName() ;
    $exportedHtml = file_get_contents($exportedHtmFile) ;
    if ($exportedHtml === false) {
      die ("YEdGraphBrowser: cannot find exported file $exportedHtmFile.".
           "The YEdGraph should be exported as HTML ImageMap (use Ctrl-E)") ;
    }
    return YEdHTML::getImageAreaMap($exportedHtml) ;
  }
  
  /**
   * Add some elements to each area and add $mapid__ as a prefix
   * to each id of areas.
   * ExtendedImageAreas == ImageAreas + Map{
   *     "graphKind" => 'node'|'edge',
   *     "target"    => String!,
   *     "urlKind"   => 'local'|'absolute',
   *     "tooltip"   => String!
   *   }
   * @return ExtendedImageAreas
   */ 
  
  protected function getEnhancedImageAreaMap($mapid) {
    $areas = $this->getOriginalImageAreaMap() ;
    // process each image area in order to
    // (1) set the target of the url to the appropriate value
    // (2) if the id of a zone starts with 'e' then this it is an edge so create an url
    $newmap = array() ;
    foreach($areas as $id => $area) {
      $newid = $mapid.'__'.$id ;
      $newarea = $area ;
      $newarea['target']=$this->targetFrameName ;
      if ($newarea["href"]==="") {
        unset($newarea["href"]) ;
      }
      if (startsWith($id,'e')) {
        $newarea["href"]=$id.'.html' ;
        $newarea["graphKind"] = 'edge' ;
      } else {
        $newarea["graphKind"] = 'node' ;
      }
      $newarea["tooltip"] = "$id is a <b>".$newarea["graphKind"].'</b>' ;
      $newarea["id"] = $newid ;
      $newmap[$newid] = $newarea ;
    }
    
    return $newmap ;
  }
  
  protected function generateJsonMap($mapid,$areamap,$image,$outputFilename) {
    echo "generate $outputFilename<br/>" ;
    $html = YEdHTML::imageAreaMapAsJson($mapid,$areamap) ;
    file_put_contents($this->targetDirectory.'/'.$outputFilename,$html) ;
  }
  
  protected function generateHtmlMap($mapid,$areamap,$image,$outputFilename) {
    echo "generate $outputFilename<br/>" ;
    // create the html image map + the reference to the image
    $htmlmap = YEdHTML::imageAreaMapAsHTML($mapid,$areamap) ;
    $html='
    <html>
    '.$htmlmap.'
    <img src="'.$image.'" usemap="#'.$mapid.'" />
    </html>
    ' ;
    file_put_contents($this->targetDirectory.'/'.$outputFilename,$html) ;
  }
  
  protected function generateMapsAndCopyImage() {
    $mapid = 'map' ;
    $areamap = $this->getEnhancedImageAreaMap($mapid) ;
    $areaMapAsHTML = $this->getYedGraphCoreName().'.map.html' ;
    $areaMapAsJson = $this->getYedGraphCoreName().'.map.json' ;
    $yedGraphImage = $this->getYedGraphCoreName().'.png' ;
    
    $this->generateHtmlMap($mapid,$areamap,$yedGraphImage,$areaMapAsHTML) ;
    $this->generateJsonMap($mapid,$areamap,$yedGraphImage,$areaMapAsJson) ;
       
    // copy the image into the target directory
    echo "generate $yedGraphImage</br>" ;
    copy($this->getYedGraphExportedImageFullFileName(),$this->targetDirectory.'/'.$yedGraphImage); 
  }
  
  
  //---- generate index and init files
  
  protected function generateGlobalFiles() {
    $indexShortFileName = 'index.html' ;
    $yedGraphAsHtml = $this->getYedGraphCoreName().'.map.html' ;
    $initShortFileName = 'init-details.html' ;
    echo "generate $indexShortFileName<br/>" ;
    file_put_contents($this->targetDirectory.'/'.$indexShortFileName,'
        <html>
        <frameset rows="48%,48%">
        <frame src="'.$yedGraphAsHtml.'" />
        <frame src="'.$initShortFileName.'" name="'.$this->targetFrameName.'">
        </frameset>
        </html>
        '      ) ;
  
    echo "generate $initShortFileName<br/>" ;
    file_put_contents($this->targetDirectory.'/'.$initShortFileName,'
        <html>
        Click on a node or edge to see the details.
        </html>
        '     ) ;
  
  }
  
  
  protected function generateAll() {
    $this->generateAllHighlightedSourceFilesAndTheirFragments() ;
    $this->processAllEdges() ;
    $this->generateMapsAndCopyImage() ;
    $this->generateGlobalFiles() ;
  }
  
  
  public function __construct($yedGraphFullFileName,$sourceFileDirectory,$sourceDefinitions,$targetDirectory) {
    $this->yedGraphFullFileName = $yedGraphFullFileName ;
    $this->sourceFileDirectory = $sourceFileDirectory ;
    $this->sourceDefinitions = $sourceDefinitions ;
    $this->targetDirectory = $targetDirectory ;
    
    $yedgraphxml = file_get_contents($this->yedGraphFullFileName) ;
    if ($yedgraphxml === false) {
      die("YEdGraphBrowser: cannot read YEdGraphFile ".
          $this->yedGraphFullFileName) ;
    }
    $graphMLReader = new GraphMLReader($yedgraphxml) ;
    $this->graph = $graphMLReader->getGraph() ;
    
    $this->generateAll();
  
  }
}


