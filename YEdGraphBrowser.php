<?php
require_once 'Strings.php' ;
require_once 'SourceCode.php' ;
require_once 'YEd.php' ;

class YEdGraphBrowserGenerator {
  
  /**
   * @var Graph The graph for which to produce the area map
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
    SourceCode::generateHighlightedSource(
        $this->sourceFileDirectory.'/'.$filename,
        $language,
        $this->targetDirectory,
        $fragments,
        "L") ;
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
   * Get the image areas from the Yed Generated export. 
   * @return ImageAreas
   */
  protected function getOriginalImageAreas() {
    $exportedHtmFile = $this->getYedGraphExportedHtmlFullFileName() ;
    $exportedHtml = file_get_contents($exportedHtmFile) ;
    if ($exportedHtml === false) {
      die ("YEdGraphBrowser: cannot find exported file $exportedHtmFile.".
           "The YEdGraph should be exported as HTML ImageMap (use Ctrl-E)") ;
    }
    return YEdHTML::getImageAreas($exportedHtml) ;
  }
  
  /**
   * @return ImageAreas
   */ 
  
  protected function processImageAreas() {
    $areas = $this->getOriginalImageAreas() ;
    // process each image area in order to
    // (1) set the target of the url to the appropriate value
    // (2) if the id of a zone starts with 'e' then this it is an edge so create an url
    $newzones = array() ;
    foreach($areas as $area) {
      $newzone = $area ;
      $newzone['target']=$this->targetFrameName ;
      if ($newzone["href"]==="") {
        unset($newzone["href"]) ;
      }
      if (startsWith($newzone['id'],'e')) {
        $newzone["href"]=$newzone['id'].'.html' ;
      }
      $newzones[] = $newzone ;
    }
    return $newzones ;
  }
  
  protected function generateYEdGraphAsHtmlAndCopyImage() {
    $areas = $this->processImageAreas() ;
    
    $yedGraphAsHTML = $this->getYedGraphCoreName().'map.html' ;
    $yedGraphImage=$this->getYedGraphCoreName().'.png' ;
    echo "generate $yedGraphAsHTML<br/>" ;
    // create the html image map + the reference to the image
    $mapid='map' ;
    $htmlmap = YEdHTML::imageAreasAsHTMLMap($mapid,$areas) ;
    $html='
    <html>
    '.$htmlmap.'
    <img src="'.$yedGraphImage.'" usemap="#'.$mapid.'" />
    </html>
    ' ;
    file_put_contents($this->targetDirectory.'/'.$yedGraphAsHTML,$html) ;
    
    // copy the image into the target directory
    echo "generate $yedGraphImage</br>" ;
    copy($this->getYedGraphExportedImageFullFileName(),$this->targetDirectory.'/'.$yedGraphImage); 
  }
  
  
  //---- generate index and init files
  
  protected function generateGlobalFiles() {
    $indexShortFileName = 'index.html' ;
    $yedGraphAsHtml = $this->getYedGraphCoreName().'map.html' ;
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
    $this->generateYEdGraphAsHtmlAndCopyImage() ;
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


