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

// Get the sql password from an external file.
require_once("_ignore_git/reader_pswd.php");

//  Get dates from uri paramater.  If none given, sert firstDate to
//  5 days ago. Last to 10 days out.
//  Expects 2011-03-22 format
if(isset($_GET["start"]))
    $firstDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["start"])));
else
    $firstDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d"),date("Y")));

if(isset($_GET["last"]))
    $lastDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["last"])));
else
    $lastDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d") + 8,date("Y")));

// Build the query.
$query = "SELECT case_number, caption, NAC, NAC_date, judge, location, prosecutor, defense FROM nextActions WHERE NAC_date BETWEEN '{$firstDateReq}' and '{$lastDateReq}' ";

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
// If cnum is given pull all date for that case number
//  Get cnum: return cal only for this case
if(isset($_GET["cnum"])){
    $cnum = htmlspecialchars($_GET["cnum"]);
    $query = "SELECT * FROM nextActions WHERE case_number = '{$cnum}' ";
}

// echo $query . "\n\n";

/*** connect to SQLite database ***/
try 
{
    $dbh = mysql_connect('localhost', $dbuser, $dbpassword) or die(mysql_error());
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
    //$checkbox =  '<input type="checkbox" name="' . "case_number" . '" value="' . $row["case_number"] . '" />'; 
   
    $row["case_number"] =  "<a class ='popup' href = 'http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . str_replace (" ", "", $row["case_number"]) . "' target='_blank'>" . $row["case_number"] . "</a>"; 
    
    $row["judge"] = $row["judge"] . "<br />" . $row["location"];
    
    $date = new DateTime($row["NAC_date"]);
    // $row["NAC_date_formatted"]=  $checkbox . $date->format('D m/d/y  g:iA');
    $row["NAC_date_formatted"]=  $date->format('D m/d/y  g:iA');
    
    $row["caption"] = $row["case_number"] . "<br />" . $row["caption"] . "</a>"; 
    $row["counsel"] = "&pi;: " . $row["prosecutor"] . "<br />&Delta;: " . $row["defense"];
      
    $events[] = $row;
      //print json_encode($rows);
  }
  print "{\"aaData\":" . json_encode($events) . "}";
}
else
{
  die($error);
}

?>