<?php
/**
 * CMS calendar creator 
 * 29r.net/calendar.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and 
 * returns the date as a JSON string.
 */

// This command will cause the script to serve any output compressed with either gzip or deflate if accepted by the client.
ob_start('ob_gzhandler');

//  Get dates from uri paramater.  If none given, sert firstDate to
//  5 days ago. Last to 10 days out.
//  Expects 2011-03-22 format
if(isset($_GET["start"])){
    $firstDateReq = date('Y-m-d', $_GET["start"]);
}
else{
    $firstDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d"),date("Y")));
}

if(isset($_GET["end"])){
    $lastDateReq = date('Y-m-d', $_GET["end"]);
}
else{
    $lastDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d") + 400,date("Y")));
}

// Build the query.
$query = "SELECT case_number, caption, NAC, NAC_date, judge, location, prosecutor, defense FROM nextActions WHERE NAC_date BETWEEN '{$firstDateReq}' and '{$lastDateReq}' ";

// echo $query;

//  If counsel is given: try to find counsel's name in either prosecutor or defense.
if(isset($_GET["counsel"])){
    $counsel = htmlspecialchars($_GET["counsel"]);
    $query = $query . " AND (prosecutor like '%$counsel%' or defense like '%$counsel%')";
}

// If judge is given append and ... to the query
if(isset($_GET["judge"])){
    $judgeReq = htmlspecialchars($_GET["judge"]);
    $query = $query . " AND judge like '%{$judgeReq}%'";
}

//  If type is specified  0: non-criminal; 1: criminal; 2 || null: all.
switch ($_GET["casetype"]) {
    case 0:     // non-criminal
        $query = $query . " AND case_number not like '%B %' ";
        $caseTypeWord = "Civil";
        break;
    case 1:     // Criminal
        $query = $query . " AND case_number like '%B %' ";
        $caseTypeWord = "Criminal";
        break;
    case 2:     // All
        $caseTypeWord = "All";
        break;
}



// echo $query . "\n\n";

/*** connect to MySlq database ***/
require_once("../_ignore_git/dbreader_pswd.php");
try 
{
    $dbh = mysql_connect( 'localhost', $dbuser, $dbpassword ) or die(mysql_error());
    mysql_select_db("todayspo_courtCal2") or die(mysql_error());
    
}
catch(PDOException $e)
{
    echo $e->getMessage();
    echo "<br><br>Database -- NOT -- loaded successfully .. ";
    die( "<br><br>Query Closed !!! $error");
}

// Query the database and loop through the results.
//$result = mysql_query($query) or die(mysql_error());

if($result = mysql_query($query))
{
    $events = array();
  while($row = mysql_fetch_array( $result, MYSQL_ASSOC ))
  {
      // add the url as the list item in array
      // build Id, title and URL
      $id = $row["case_number"];
      // $id = str_replace (" ", "", $row["NAC_date"]) . "=". str_replace (" ", "", substr($row["NAC"],0,12)) . "-" . str_replace (" ", "", $row["case_number"]);
      $title = $row["case_number"] . "-". substr($row["NAC"],0,12);
      $url=  "http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . 
      str_replace (" ", "", $row["case_number"]); 
      
      $event  = array(
                'id' => $id,
                'title' => $title,
                'start' => $row["NAC_date"],
                'url' => $url,
                'allDay' => false
        		);
      
      $events[] = $event;
  }
  print json_encode($events);
}
else
{
  die($error);
}

?>