<?php
/**
 * CMS calendar creator 
 * 29r.net/calendar.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court dates and returns an ical file.
 */
require_once 'iCalcreator.class.php';
require_once( "libs/UnitedPrototype/autoload.php" );
// This command will cause the script to serve any output compressed with either gzip or deflate if accepted by the client.
ob_start('ob_gzhandler');
// seconds, minutes, hours, days
$expires = 60*60*2;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
//header('Content-type: text/calendar');

function createAbbreviatedSetting($setting){
	// create an associative array mapping setting to abv
	$abreviations = array(
	    "NAC" => "Abreviation",
	    "PLEA OR TRIAL SETTING" => "PTS",
	    "CMC INITIAL CASE MANAGEMENT" => "CMC",
	    "ARRAIGNMENT" => "ARGN",
	    "CASE MANAGEMENT CONFERENCE" => "CMC",
	    "SCHEDULING CONFERENCE" => "CMC",
	    "SENTENCE" => "SENT",
	    "SENTENCING" => "SENT",
	    "REPORT" => "RPT",
	    "STATUS REPORT" => "RPT",
	    "DSC/DISPOSITION SCHEDULING CON" => "DSC",
	    "JURY TRIAL" => "JT",
	    "PROBATION VIOLATION" => "PV",
	    "TELEPHONE REPORT" => "TELE. RPT",
	    "CIVIL PROTECTION ORDER HEARING" => "CPO HRG",
	    "ENTRY" => "FE",
	    "FINAL ENTRY" => "FE",
	    "MOTION FOR SUMMARY JUDGMENT" => "MSJ",
	    "PRE-TRIAL" => "PT",
	    "BENCH TRIAL" => "BT",
	    "PLEA" => "PLEA",
	    "DISP SCHEDULING CONFERENCE" => "DSC",
	    "POST-CONVICTION WARRANT RETURN" => "WRNT RTN",
	    "MEDIATION CONFERENCE" => "MEDIATION",
	    "EXPUNGEMENT" => "EXPNG",
	    "PROBABLE CAUSE HEARING" => "PC/PV",
	    "IN PROGRESS, JURY TRIAL" => "JT",
	    "MOTION FOR JUDGMENT DEBTOR" => "J DEBT",
	    "MOTION TO SUPPRESS" => "MOT SUPP",
	    "MOTION" => "MOT",
	    "PROBABLE CAUSE HEARING, PROBATION VIOLATION" => "PC/PV",
	    "TELEPHONE SCHEDULING CONF" => "TELE RPT",
	    "ORDR OF APPRNCE/JDGMNT DEBTOR" => "J DEBT",
	    "DSC/PLEA OR TRIAL SETTING" => "PTS",
	    "CASE MANAGEMENT CONFERENCE, INITIAL" => "CMC",
	    "HEARING" => "HRG",
	    "DSC/PRETRIAL" => "PT",
	    "DECISION" => "DECISION",
	    "DSC/TRIAL SETTING" => "DSC",
	    "COMMUNITY CONTROL VIOLATION" => "PV",
	    "REPORT, COMMERICAL CASE" => "RPT",
	    "FORMAL PRE-TRIAL" => "PT",
	    "TRIAL OR DISMISSAL" => "TR-DISM",
	    "TELEPHONE REPORT, DEFAULT" => "TELE RPT",
	    "PROBATION VIOLATION, SENTENCE" => "PV",
	    "ENTRY OF DISMISSAL" => "FE",
	    "SETTLEMENT ENTRY" => "FE",
	    "REPORT OR ENTRY" => "RPT",
	    "REPORT, COMMERCIAL DOCKET" => "RPT",
	    "PROBABLE CAUSE HEARING, COMMUNITY CONTROL VIOLATION" => "PC/PV",
	    "RE-SENTENCING" => "SNTC",
	    "TRIAL, TO COURT" => "BT",
	    "MOTION TO DISMISS" => "MOT DISM",
	    "CASE MANAGEMENT CONFERENCE, COMMERCIAL DOCKET" => "CMC",
	    "REPORT, ON MEDIATION" => "RPT",
	    "GARNISHMENT HEARING" => "GARNISH",
	    "DECISION DUE" => "DECISION DUE",
	    "MOTION FOR JUDICIAL RELEASE" => "J. REL.",
	    "CASE MANAGEMENT CONFERENCE, ON CROSS CLAIM" => "CMC",
	    "RECEIVER'S REPORT" => "RCVR RPT",
	    "FORFEITURE HEARING" => "FORFT HRG",
	    "MOTIONS" => "MOT",
	    "COMPETENCY HEARING" => "COMP HRG",
	    "IN PROGRESS, BENCH TRIAL" => "BT",
	    "TRIAL SETTING" => "TR SETTING",
	    "PRE-CONVICTION CAPIAS RETURN, PLEA OR TRIAL SETTING" => "PTS",
	    "MOTION FOR SUMMARY JUDGMENT, OR DISMISSAL" => "MSJ",
	    "MOTION TO SUPPRESS, & JURY TRIAL" => "MOT SUPP",
	    "DISCOVERY" => "DSCVY"
	);
	if (array_key_exists ($setting , $abreviations)){
	    return $abreviations[ $setting ];
	}
	return $setting;
}

// initiate new CALENDAR
$v = new vcalendar( array( 'unique_id' => 'MyCourtDates.com' ));

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

if(isset($_GET["reminders"]))
	$reminders = false;
else
 	$reminders = true;

// Build the query.
$query = "SELECT * FROM nextActions WHERE NAC_date > '{$firstDateReq}' and NAC_date < '{$lastDateReq}' ";

//  If counsel is given: try to find counsel's name in either prosecutor or defense.
if(isset($_GET["counsel"])){
    $counsel = htmlspecialchars($_GET["counsel"]);
    $query = $query . " AND prosecutor like '%$counsel%' or defense like '%$counsel%'";
}

//  If prosecutor is given.
if(isset($_GET["prosecutor"])){
    $prosecutor = htmlspecialchars($_GET["prosecutor"]);
    $query = $query . " AND prosecutor like '%$prosecutor%'";
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

/*** connect to MySql database ***//*** connect to MySQL database ***/

// Get the sql password from an external file.
require_once("passwordfiles/dbreader_pswd.php");

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
// $result = mysql_query($query) or die(mysql_error());
$result = mysql_unbuffered_query($query) or die(mysql_error());
//$row = mysql_fetch_array( $result );

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
        
  	// Build Description
        $caseNumber = str_replace(" ", "", $row["case_number"]);
        $NAC_URI = "http://www.courtclerk.org/case_summary.asp?sec=history&casenumber={$caseNumber}"; 
        
        $description =    "$caseNumber\n" . $row["caption"] .  "\n" . $row['NAC']
        		. "\n\nPlaintiffs' Counsel: " . $row["prosecutor"]
        		. "\nDefense Counsel: " . $row["defense"] 
        		. "\n\n" . $row["cause"]  . "\n\n" . $NAC_URI 
        		. "\n\nAs of " . $row["freshness"];
  	
  	// Build Summary
        $caption  = ucwords(strtolower($row['caption']));
        $caption  = str_replace("Vs", "vs", $caption);
        
        // Build Summary
        // $summary  = ucwords(strtolower($row['NAC']));
        if (substr ( $row['case_number'] , 0 , 1) == 'B'){
        	// if crim case, only include Defendant's name
        	$summary  = createAbbreviatedSetting($row['NAC']);
        	$vIndex   = strpos ( $caption, 'vs');
        	$summary .= ' - ' . substr ( $caption, $vIndex + 3);
        	$summary .= ' - ' . $row['case_number'];
        }else{
            $summary  = createAbbreviatedSetting($row['NAC']);
        	$summary .= ' - ' . $row['case_number'];
        	$summary .= ' - ' . $caption;
        }

        // Build stateTimeDate
        $year = substr ( $row["NAC_date"] , 0 , 4);
        $month = substr ( $row["NAC_date"] , 5 , 2);
        $day = substr ( $row["NAC_date"] , 8 , 2);
        $hour = substr ( $row["NAC_date"] , 11 , 2);
        $minutes = substr ( $row["NAC_date"] , 14 , 2);
        $seconds = "00";
        
        // Create the event object
        $e = & $v->newComponent( 'vevent' );                 // initiate a new EVENT
        $e->setProperty( 'summary', $summary);              // set summary-title
        $e->setProperty( 'categories', 'Court_dates' );      // catagorize
        $e->setProperty( 'dtstart', $year, $month, $day, $hour, $minutes, 00 );     // 24 dec 2006 19.30
        $e->setProperty( 'duration', 0, 0, 0, 15 );         // 3 hours
        $e->setProperty( 'description', ucwords(strtolower($description)));     // describe the event
        $e->setProperty( 'location', ucwords(strtolower($row["location"])));             // locate the event
        
	    // create an event alarm
	    $valarm = & $e->newComponent( "valarm" );
	    
	    // reuse the event description
	    $valarm->setProperty("action", "DISPLAY" );
	    $valarm->setProperty("description", $e->getProperty( "summary" ));
	    
	    // set alarm to 60 minutes prior to event.
	    $valarm->setProperty( "trigger", "-PT60M" );
  }
    // Cal Name
    $calName = ucfirst($judgeReq) . " - " . $caseTypeWord;
    $v->setProperty( "x-wr-calname", $calName );

    // required of some calendar software
    $v->setProperty( "X-WR-CALDESC", "This is the calendar for Hamilton County Common Pleas Court covering " . $firstDateReq . " through " . $lastDateReq . ". It was created at " . date("F j, Y, g:i a") );
  
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
        // echo "<br />\n\n";
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

use UnitedPrototype\GoogleAnalytics;

// Initilize GA Tracker
$tracker = new GoogleAnalytics\Tracker('UA-124185-5', '29r.net');

// Assemble Visitor information
// (could also get unserialized from database)
$visitor = new GoogleAnalytics\Visitor();
$visitor->setIpAddress($_SERVER['REMOTE_ADDR']);
$visitor->setUserAgent($_SERVER['HTTP_USER_AGENT']);
$visitor->setScreenResolution('1024x768');

// Assemble Session information
// (could also get unserialized from PHP session)
$session = new GoogleAnalytics\Session();

// Assemble Page information
$pageName=$userName['lName'] . "-" . $userId;
$page = new GoogleAnalytics\Page("/ics/$pageName");
$page->setTitle( $pageName );

// Track page view
$tracker->trackPageview($page, $session, $visitor);
   
?>
