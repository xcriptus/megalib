<?php
/**
 * 
 * 
 * http://researchr.org/api/publication/{key} 
 * 
 * * Tag (1433)
 *  
 * * Conference (3 000)
 * ** @html("http://researchr.org/conference/sle-2012") //(- or : or ?)
 * ** proceedings : Set*<Publication!>? 
 * ** description : Text?
 * ** conferenceDate : SSText? 
 * ** callForPapers : Text?
 * *** @html("http://researchr.org/conference/sle-2012/call") 
 * ** program : ???
 * *** @html(http://researchr.org/conference/sle-2012/program")
 * 
 * * ConferenceSeries
 * ** @(html: http://researchr.org/conferenceseries/sle)
 * ** editions : List*<Conference!>?
 * ** aliases : Set*<String!>?
 * 
 * * Journal (1 000)
 * ** @html("http://researchr.org/journal/tse/home")
 * ** description: Text
 * ** ...
 * 
 * * Publication.
 * ** @html("http://researchr.org/publication/LaemmelPS11")
 * ** @json("http://researchr.org/api/publication/LaemmelPS11")
 * ** search(pattern:Pattern!) 
 * *** @html("http://researchr.org/search/publication/software+linguistics")
 *  
 * * Author (1 000 000)
 * ** @html("http://researchr.org/alias/ralf-l%C3%A4mmel")
 * ** @json("http://researchr.org/api/person/ralflammel") // ?? on people
 * ** search(pattern:Pattern!)
 * *** @html("http://researchr.org/search/author/"ralf lämmel") ;
 * * Profile (2 255)
 * *** 
 * ** http://researchr.org/search/publication/linguistics+software
 * 
 * * Group (40)
 * ** @html("http://researchr.org/usergroup/ttc2010")
 * ** 
 * * Bibliography.
 * ** @html("http://researchr.org/bibliography/systematic-reviews")
 * ** @json("http://researchr.org/api/bibliography/systematic-reviews")
 */

define('RSHR_URL','http://researchr.org') ;

define('RSHR_PUBLICATON_HTML',RSHR_URL.'/publication/');
define('RSHR_PUBLICATON_API',RSHR_URL.'/api/publication/') ;
    
define('RSHR_PERSON_HTML',RSHR_URL.'/api/person/') ;
define('RSHR_PERSON_API',RSHR_URL.'/api/alias/') ;

define('RSHR_BIBLIOGRAPHY_API',RSHR_URL.'/api/bibliography/') ;

