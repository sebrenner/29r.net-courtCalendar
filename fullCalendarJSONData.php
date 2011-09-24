<?php
/**
 * CMS calendar creator 
 * 29r.net/calendar.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and 
 * returns the date as a JSON string.
 */

//  Get dates from uri paramater.  If none given, sert firstDate to
//  5 days ago. Last to 10 days out.
//  Expects 2011-03-22 format
if(isset($_GET["earliestDate"]))
    $firstDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["earliestDate"])));
else
    $firstDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d"),date("Y")));

if(isset($_GET["lastDate"]))
    $lastDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["lastDate"])));
else
    $lastDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d") + 8,date("Y")));

// Build the query.
$query = "SELECT case_number, NAC, NAC_date FROM nextActions WHERE NAC_date BETWEEN '{$firstDateReq}' and '{$lastDateReq}' ";

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
    // $dbh = new PDO("sqlite:the_nacs.db");
    $dbh = mysql_connect('localhost', 'todayspo_ctDbRdr', '4W(Rn*aLgdXi') or die(mysql_error());
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
    $counter = 1;
  while($row = mysql_fetch_array( $result, MYSQL_ASSOC ))
  {
        // add the url as the list item in array
        // build Id, title, endtime, and URL
        $title = $row["case_number"] . " - ". substr($row["NAC"],0,12);
        $startTime = date($row['NAC_date']);
        $endTime = date($row['NAC_date']);
        $url=  "http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . str_replace (" ", "", $row["case_number"]); 
      
        $event  = array(
                'id' => $counter,
                'title' => $title,
                'start' => $startTime,
                'end' => $endTime,
                'url' => $url,
                'allDay' => false
        		);

      $counter++;
      $events[] = $event;
  }
  echo json_encode($events);
}
else
{
  die($error);
}

?>