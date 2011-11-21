<?php
/**
 * CMS calendar creator 
 * 29r.net/jacketTags.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and 
 * returns the date as a PDF
 * jacketTags.php?judge=allen&start=09%2F12%2F2011&last=09%2F16%2F2011&contact=Scott+Brenner+x5106&casetype=1
* 
 */
require('../fpdf17/fpdf.php');


// =======================
// = Build the SQL Query =
// =======================

//  Get rowids from uri paramaters.
$query  = explode('&', $_SERVER['QUERY_STRING']);
$params = array();

foreach( $query as $param )
{
  list($name, $value) = explode('=', $param);
  $params[urldecode($name)][] = urldecode($value);
}

// Build the query.
$query = "SELECT case_number, caption, NAC, NAC_date, judge, location FROM nextActions WHERE ";


foreach ($params[r] as $value){
	$query = $query . ' NA_id=' . $value . ' or';
}
// remove the last "or"
$query = substr($query, 0, -3);

// append order by
$query = $query . "	ORDER BY NAC_date ASC;";



// print_r( $query );


// Create the contact line 
$contact = "___________________________ ";
if(isset($_GET["contact"])){
    $contact = htmlspecialchars($_GET["contact"]);
}


class PDF extends FPDF {

    // Page footer
    function Footer(){
    	// Position at 1.5 cm from bottom
    	$this->SetY(-15);
    	// Arial italic 8
    	$this->SetFont('Arial','I',8);
    	// Page number
    	$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}


// Function to format the Judge's name and room number
function getJudge( $judge )
{
	$judge = str_replace( "/", ", ", $judge );
	return "J. " .  mb_convert_case( $judge, MB_CASE_TITLE );
}

// Function to format the NAC date
function formatNacDate( $nacDate ){
	$tmpDate = strtotime( $nacDate );
	return date( "D, Y-n-j @ H:i A" , $tmpDate );
}

// Function to format the case caption
function getCaption( $caption )
{
	$caption = mb_convert_case( $caption, MB_CASE_TITLE );
	$caption = str_replace( "State Of Ohio", "Ohio", $caption );
	$vIndex = strpos( $caption, "Vs" );
	if ( strlen ( $caption ) > 33 ) {
		if ( $vIndex > 12 ) {
			// return first 12 of plaintiff and first 12 of def.
			return substr( $caption, 0, 12 ) . "... v. " . substr( $caption, $vIndex + 3, 12 );			
		} else {
			// return all of plaintiff and first 12 of def.
			return substr( $caption, 0, $vIndex ) . "v. " . substr( $caption, $vIndex + 3, 12 );
		}
	}
	return $caption;
}


// Get the sql password from an external file.
require_once("../_ignore_git/dbreader_pswd.php");

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
    // setup PDF
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $counter = 0;
    $num_rows = mysql_num_rows( $result );
    $blanksNeeded = 10 - ( $num_rows % 10 );
    while($row = mysql_fetch_array( $result, MYSQL_ASSOC )){
        // create PDF cells
        $pdf->SetFont('Arial','B',24);
        $pdf->Cell(95,12, $row["case_number"],0,2);
        $pdf->SetFont('Times','I',18);
        $pdf->Cell(95, 5, getCaption( $row["caption"] ), 0,2);
        $pdf->SetFont('Times','',13);
        $pdf->Cell(95,7, getJudge( $row["judge"] ) . " - Room: " . $row["location"], 0,2);
        $pdf->Cell(95,7, formatNacDate( $row["NAC_date"] ), 0, 2 );
        $pdf->Cell(95,7,'  '. mb_convert_case( $row["NAC"], MB_CASE_TITLE ), 0, 2);
        $pdf->Cell(95,7,$contact,0,2);
        $pdf->Cell(95,5,'',0,2);

        // increment counter to keep track of column breaks
        $counter ++;

        // break for columns and pages.  Reset counter.
        if ( $counter == 5 ) $pdf->SetXY(115,10);

        if ($counter == 10){
            $pdf->AddPage();
            $counter = 0;
        }
    }
    
    for ($i = 1; $i <= $blanksNeeded; $i++){
        // break for columns and pages.  Reset counter.
        
        if ( $counter == 5 ) {
            $pdf->SetXY(115,10);
            // $pdf->Cell(95,12, "blanks needed, counter =" . $blanksNeeded . $counter ,0,2);
        }
        $pdf->SetFont('Arial','B',24);
        $pdf->Cell(95,12, "Case #: __________",0,2);
        $pdf->SetFont('Times','I',13);
        $pdf->Cell(95, 5, "Caption: ___________________________",0,2);
        $pdf->SetFont('Times','',13);
        $pdf->Cell(95,7, "Judge & Room #: ____________________",0,2);
        $pdf->Cell(95,7,"Action Date: ________________________",0,2);
        $pdf->Cell(95,7,'  '."Set for: ___________________________",0,2);
        $pdf->Cell(95,7,$contact,0,2);
        $pdf->Cell(95,5,'',0,2);
        $counter ++;
    }
    
  //print "{\"aaData\":" . $lastDate . substr($row["NAC_date"],0,11) . "}";
  $pdf->Output();
}
else
{
  die($error);
}

?>