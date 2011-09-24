<?php
/**
 * CMS calendar creator 
 * 29r.net/calendar.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and returns an ical.
 */
require_once 'iCalcreator.class.php';

// initiate new CALENDAR
$v = new vcalendar( array( 'unique_id' => 'Court Schedule' ));

// required of some calendar software
$v->setProperty( 'method', 'PUBLISH' );

// required of some calendar software
$v->setProperty( "X-WR-TIMEZONE", "America/New_York" );

//  Get dates from uri paramater.  If none given set to first 1 days ago. Last to 5 days out.
//  Will take almost any date/time string.  See http://php.net/manual/en/function.strtotime.php
if(isset($_GET["start"]))
    $firstDateReq = date("Y-m-d",strtotime(htmlspecialchars($_GET["start"])));
else
    $firstDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d")-1,date("Y")));

if(isset($_GET["last"]))
    $lastDateReq = date("Y-m-d",strtotime(htmlspecialchars($_GET["last"])));
else
    $lastDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d")+5,date("Y")));


// Build the query.
$query = "SELECT * FROM nextActions WHERE NAC_date > '{$firstDateReq}' and NAC_date < '{$lastDateReq}' ";

//  If counsel is given: try to find counsel's name in either prosecutor or defense.
if(isset($_GET["counsel"])){
    $counsel = htmlspecialchars($_GET["counsel"]);
    $query = $query . " AND prosecutor like '%$counsel%' or defense like '%$counsel%'";
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

/*** connect to SQLite database ***/
try 
{
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
// $result = mysql_query($query) or die(mysql_error());
$result = mysql_unbuffered_query($query) or die(mysql_error());
$row = mysql_fetch_array( $result );

if($result = mysql_query($query))
{
    
    if( $_GET["output"] == 3 ){ $events = array(); }  // json data
    
    while($row = mysql_fetch_array( $result, MYSQL_ASSOC ))    {
    
        if( $_GET["output"] == 3 ){ // json data
            // add the url as the list item in array
            $row["case_number"] =  "<a class ='popup' href = 'http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . str_replace (" ", "", $row["case_number"]) . "' target='_blank'>" . $row["case_number"] . "</a>"; 
            //echo $row["case_number"];
            $row["judge"] = $row["judge"] . "<br />" . $row["location"];
            
            $NACdate = new DateTime($row["NAC_date"]);
            $row["NAC_date"]= $NACdate->format('Y-m-d H:i');
            
            $row["caption"] =  $row["case_number"] . "<br />" . $row["caption"] . "</a>"; 
            $row["counsel"] = "&pi;: " . $row["prosecutor"] . "<br />&Delta;: " . $row["defense"];
            
            $events[] = $row;
        }
        
        // Build Summary
        $summary = "{$row["NAC"]}-{$row["case_number"]}-{$row["caption"]}";
        
        // Build Description
        $caseNumber = str_replace(" ", "", $row["case_number"]);
        $NAC_URI = "http://www.courtclerk.org/case_summary.asp?sec=history&casenumber={$caseNumber}"; 
        
        $description = "\nPlaintiffs Counsel:" . $row["prosecutor"] . "\nDefense Counsel:" . $row["defense"] .  "\n" . $row["cause"]  . "\n" . $NAC_URI;

        // Build stateTimeDate
        $year = substr ( $row["NAC_date"] , 0 , 4);
        $month = substr ( $row["NAC_date"] , 5 , 2);
        $day = substr ( $row["NAC_date"] , 8 , 2);
        $hour = substr ( $row["NAC_date"] , 11 , 2);
        $minutes = substr ( $row["NAC_date"] , 14 , 2);
        $seconds = "00";

        // Build UID
        $UID = strtotime("now") . $caseNumber . $row[4] . "@cms.halilton-co.org";

        // Create the event object
        $e = & $v->newComponent( 'vevent' );                 // initiate a new EVENT
        $e->setProperty( 'summary', $summary );              // set summary-title
        $e->setProperty( 'categories', 'Court_dates' );      // catagorize
        $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, 00 );     // 24 dec 2006 19.30
        $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
        $e->setProperty( 'description', $description );     // describe the event
        $e->setProperty( 'location', $row["location"] );             // locate the event
  }
    // Cal Name
    $calName = ucfirst($judgeReq) . " - " . $caseTypeWord . " - " . $firstDateReq;
    $v->setProperty( "x-wr-calname", $calName );

    // required of some calendar software
    $v->setProperty( "X-WR-CALDESC", "This is the calendar for Hamilton County Common Pleas Court covering " . $firstDateReq . " through " . $lastDateReq .". It only includes setting for judge " . ucfirst($judgeReq) );
  
}
else
{
  die($error);
}

switch ($_GET["output"]) {
    case 0:
        $v->returnCalendar();           // generate and redirect output to user browser
        break;
    case 1:
        $str = $v->createCalendar();    // generate and get output in string, for testing?
        echo $str;
        echo "<br />\n\n";
        break;
    case 2:
        echo $query;                    // with query
        echo "<br />\n\n                                                    ";
        $str = $v->createCalendar();    // generate and get output in string, for testing?
        echo $str;
        echo "<br />\n\n";
        break;
    case 3:     //JSON Data
        print "{\"aaData\":" . json_encode($events) . "}";
        break;
}

function NACDuration( $NAC ){
    echo "I am b.";
}

?>