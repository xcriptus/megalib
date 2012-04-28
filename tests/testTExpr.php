<?php
require_once 'main.config.local.php' ;
require_once '../TExpr.php' ;
require_once '../HTML.php' ;



$mapcontrib= array(
    "CSharp" => array("type"=>"Language","name"=>"C#"),
    "WrongMap" => array("type"=>"toto","whatever"=>"blabla"),
    "xsdClasses" => array("type"=>"Implementation","name"=>"xsdClasses")
) ;

$testCases = array(
//-------------------------------
    array(
        'jn' =>'"this ${is} the ${first} example with an ${unmatch}"',
        'sn' =>'this ${is} the ${first} example with an ${unmatch}',
        'map'=>array("is"=>"IS","first"=>"NB 1","nada"=>"NOTHING")),
//-------------------------------
    array(
        'jn' =>'"one with ${various} ${times} the variable ${times}"',
        'sn' =>'one with ${various} ${times} the variable ${times}',
        'map'=>array("is"=>"IS","various"=>'${two}',"two"=>"2",'times'=>'times')),
//-------------------------------
    array(
        'jn' =>'"in ${_}, ${one} is ${nb1}."',
        'sn' =>'in ${_}, ${one} is ${nb1}.',
        'map'=>array()
    ),
//-------------------------------
    array(
        'jn' =>'
        ["MATCH","${type}",
        "Language","pink",
        "Implementation","blue",
        "Technology","grey",
        "*","none"]
        ',

        'sn' =>'
        ??? ${type}
        Language pink
        Implementation blue
        Technology grey
        * none
        .
        ',

        'map'=>$mapcontrib),
//-------------------------------
    array(
        'jn' =>'
        [ "MATCH" ,"${type}",
        "/Lang.*/","circle" ]
        ',

        'sn' =>'
        MATCH ${type} /Lang.*/ circle END
        ',

        'map'=>$mapcontrib),
//-------------------------------
    array(
        'jn' =>'
        ["MATCH","${type}",
        "Language",{ "color":"pink", "shape":"box", "label":"${name}:${type}" },
        "Implementation",{"color":"blue","shape":"circle"}
        ]
        ',

        'sn' =>'
        ??? ${type}
          Language {
            color pink
            shape box
            label ${name}:${type}
          }
          Implementation {
            color blue
            shape circle
          }
        .
        ',

        'map'=>$mapcontrib),
//-------------------------------
    array(
        'jn' =>'
        { "color":
            ["MATCH","${type}",
              "Language","pink",
              "Implementation","blue",
              "Technology","grey",
              "*","none"],
          
          "shape":
            [ "MATCH" ,"${type}","/Lang.*/","circle" ]
        }',

        'sn' =>'
        { color
            ??? ${type}
              Language pink
              Implementation blue
              Technology grey
              * none
            .
          shape
            ??? ${type} /Lang.*/ circle .
        }
        ',

        'map'=>$mapcontrib),
//-------------------------------
    array(
        'jn' =>'
        ["CONCAT",
          {"font":"italic"},
          {"color":
            ["MATCH","${type}",
                "Language","pink",
                "Implementation","blue",
                "Technology","grey",
                "*","none"],
           "shape":
            ["MATCH" ,"${type}","/Lang.*/","circle"]
          },
          ["MATCH","${type}",
            "Language",{ "color":"pink", "shape":"box", "label":"${name}:${type}" },
            "Implementation",{"color":"blue","shape":"circle"}
          ]
        ]
        ',

        'sn' =>'
        @@@
          { font italic }
          { color
              ??? ${type}
                Language pink
                Implementation blue
                Technology grey
                * none
              .
            shape
              ??? ${type} /Lang.*/ circle .
          }
          ??? ${type}
            Language {
              color pink
              shape box
              label ${name}:${type}
            }
            Implementation {
              color blue
              shape circle
            }
          .
        .
        ',
        'map'=>$mapcontrib)
) ;
//-------------------------------




testEvalTExpr($testCases) ;

/* TODO: Here an example of a short syntax not implemented.
 * The other syntax is json.
 */

function testEvalTExpr($testCases) {
  echo "<h2>Testing EvalTemplate</h2>" ;
  
 
  foreach($testCases as $testCase) {
    echo "<hr/>" ;
    echo "TEMPLATE in json <br/>".htmlAsIs($testCase["jn"])."<br/>" ;
    echo "TEMPLATE in short notation </br>".htmlAsIs($testCase["sn"])."<br/>" ;
    echo "MAPPING:  ".
        (is_map_of_map($testCase["map"])
          ? mapOfMapToHTMLTable($testCase["map"])
          : mapToHTMLList($testCase["map"])) ;
    $evaluator=new TExprEvaluator() ;
    $r = $evaluator->doEvalJson($testCase["jn"],$testCase["map"]) ;
    echo "RESULT:   " ;
    var_dump($r) ;
    echo "TRACE:    " ;
    echo htmlAsIs($evaluator->getTrace()) ;
  }
}


