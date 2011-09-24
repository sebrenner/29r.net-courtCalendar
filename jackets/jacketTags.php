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

//  Get dates from uri paramater.  If none given, sert firstDate to
//  5 days ago. Last to 10 days out.
//  Expects 2011-03-22 format
if(isset($_GET["start"]))
    $firstDateReq = date("Y-m-d", strtotime(htmlspecialchars($_GET["start"])));
else
    $firstDateReq = date("Y-m-d", mktime(0, 0, 0, date("m"),date("d"),date("Y")));

if(isset($_GET["last"])){
    $tempDate = new dateTime(htmlspecialchars($_GET["last"]));
    // set ende date time to be the end of the day entered by adding ~24 hours
    $tempDate->setTime(23, 59, 59);
    $lastDateReq = $tempDate->format("Y-m-d H:i:s");
    }
else
    $lastDateReq = date("Y-m-d", mktime(23, 59, 59, date("m"),date("d") + 8,date("Y")));

// Build the query.
$query = "SELECT case_number, caption, NAC, NAC_date, judge, location, prosecutor, defense FROM nextActions WHERE NAC_date BETWEEN '{$firstDateReq}' and '{$lastDateReq}' ";

// Add a contact line 
$contact = "___________________________ ";
if(isset($_GET["contact"])){
    $contact = htmlspecialchars($_GET["contact"]);
}

//  If type is specified
if($_GET["casetype"] == 1){
    $query = $query . " AND case_number like '%B %' ";    
}
    
if($_GET["casetype"] == 0){
    $query = $query . " AND case_number not like '%B %' ";   
}


//  If counsel is given: try to find counsel's name in either prosecutor or defense.
if(isset($_GET["counsel"])){
    $counsel = htmlspecialchars($_GET["counsel"]);
    $query = $query . " AND (prosecutor like '%$counsel%' or defense like '%$counsel%')";
}

// If judge is given append and ... to the query
//echo $_GET["judge"];
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

//echo $query . "\n\n";
class PDF extends FPDF
{

// Page footer
function Footer()
{
	// Position at 1.5 cm from bottom
	$this->SetY(-15);
	// Arial italic 8
	$this->SetFont('Arial','I',8);
	// Page number
	$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
}
}

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
    // setup PDF
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $counter = 0;
  while($row = mysql_fetch_array( $result, MYSQL_ASSOC ))
  {
        // initialize  $lastDate
        if ($counter == 0 )
             $lastDate = substr($row["NAC_date"],0, 11);
        
        if ($lastDate != substr($row["NAC_date"],0, 11)){
            $blanksNeeded = 10 - $counter;
            for ($i = 1; $i <= $blanksNeeded; $i++){
                if ($counter == 5) 
                    $pdf->SetXY(115,10);
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
            $pdf->AddPage();
            $counter = 0;
            }
        
        
        // create PDF cells
        $pdf->SetFont('Arial','B',24);
        $pdf->Cell(95,12, $row["case_number"],0,2);
        $pdf->SetFont('Times','I',13);
        $pdf->Cell(95, 5, substr($row["caption"],0, 34),0,2);
        $pdf->SetFont('Times','',13);
        $pdf->Cell(95,7, $row["judge"] . " ----- " . $row["location"],0,2);
        $pdf->Cell(95,7,$row["NAC_date"],0,2);
        $pdf->Cell(95,7,'  '.$row["NAC"],0,2);
        $pdf->Cell(95,7,$contact,0,2);
        $pdf->Cell(95,5,'',0,2);
        
        // increment counter to keep track of coloumn breaks
        $counter ++;
        
        // break for columns and pages.  Reset counter.
        if ($counter == 5) 
            $pdf->SetXY(115,10);

        if ($counter == 10){
            $pdf->AddPage();
            $counter = 0;}
        
        $lastDate = substr($row["NAC_date"],0, 11);
    }
  //print "{\"aaData\":" . $lastDate . substr($row["NAC_date"],0,11) . "}";
  $pdf->Output();
}
else
{
  die($error);
}

?>