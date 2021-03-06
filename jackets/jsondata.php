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
if(isset($_GET["start"]))
    $firstDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["start"])));
else
    $firstDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d"),date("Y")));

if(isset($_GET["last"]))
    $lastDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["last"])));
else
    $lastDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d") + 8,date("Y")));

// Build the query.
$query = "SELECT case_number, caption, NAC, NAC_date, NA_id 
	FROM nextActions
	WHERE NAC_date BETWEEN '{$firstDateReq}' and '{$lastDateReq}' 
	and judge like '%" . $_GET["judge"] . "%'";


// Append case type
if(isset($_GET["casetype"])){
	// Civl = 0; crim = 1; both = 2;
	switch ( $_GET["casetype"] ) {
	    case 0:
	        $query = $query . " AND case_number  NOT like '%B %'";
	        break;
	    case 1:
	        $query = $query . " AND case_number like '%B %'";
	        break;
	    case 2:
	        break;
	}	
}

//echo $query . "\n\n";

/*** connect to MySQL database ***/

// Get the sql password from an external file.
require_once("../passwordfiles/dbreader_pswd.php");

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
    $row["check_box"] =  '<input type="checkbox" class="case" name="r"' . ' value="' . $row["NA_id"] . '" />'; 
   
    $row["case_number"] =  "<a class ='popup' href = 'http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . str_replace (" ", "", $row["case_number"]) . "' target='_blank'>" . $row["case_number"] . "</a>"; 
    
    $date = new DateTime($row["NAC_date"]);
	$row["NAC_date_formatted"]=  $date->format('D m/d/y  g:iA'); 
	$row["caption"] = $row["case_number"] . " " . $row["caption"] . "</a>"; 
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