<?php
require_once 'main.config.local.php' ;

require_once '../Rules.php' ;
$rulefile='data/input/sourceDirectoryMatchingRulesNew.rules.json' ;

echo '<h2>Reading rules from '.$rulefile.'</h2>' ;
$rules = new RuleList($rulefile) ;
echo $rules->getRulesNumber().' rules<br/>' ;
$rulelisteval = new RuleListEvaluation($rules,'test/x/toto.java','file') ;
var_dump($rulelisteval->getRuleEvaluationCountPerStatus()) ;
echo htmlAsIs($rulelisteval->toJson()) ;