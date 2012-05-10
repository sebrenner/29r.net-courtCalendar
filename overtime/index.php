<?php
/**
 * Calendar histogram creator 
 * 29r.net/calendarHistogram.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and 
 * returns the date as a PDF histogram
 *  
 */
require('../fpdf17/fpdf.php');
require('../iCalcnv-2.0/iCalcnv/iCal2csv.php');
date_default_timezone_set('America/America/New_York');


class PDF extends FPDF
{
	// Class properties
	protected $caseNumbers = array();
	// Page header
	function Header()
	{
	    // Arial bold 15
	    $this->SetFont('Arial','B',10);
	    // Move to the right
	    //$this->Cell(80);
	    $month = date('F');
    	    $year = date ('Y');
	    $title = "Judge " . ucfirst( $this->judge ) . " - Criminal Cases that will be over time as of $month $year.";
	    $memo = "These cases, if not resolved this month, will appear as overtime on the S. Ct. Form A for $month $year.";
	    $this->Cell(array_sum($w),5,$title,0,1,'C');
	    $this->Ln(2);
	    $this->Cell(array_sum($w),5,$memo,0,0,'C');
	    // Line break
	    $this->Ln(5);
	}

	function Footer()
	{
	    $this->SetFont('Arial','',8);
	    $notes = "Cases set for Expungement, PV, Judicial Release, and Post Conviction relief are not included.";
	    $this->Ln();
	    $this->Cell(200,5,$notes,0,0,'C');
	}

    	function setJudge($judge){
        	$this->judge = $judge;
    	}

	
	// Colored table
	function FancyTable( $header, $data )
	{
	    // Colors, line width and bold font
	    $this->SetFillColor(255,0,0);
	    $this->SetTextColor(255);
	    $this->SetDrawColor(128,0,0);
	    $this->SetLineWidth(0);
	    $this->AddFont('Andale', '', 'Andale Mono.php');
            $this->SetFont('Andale','', 10);
	    
	    // Header--Creates header based on the widths described in $w.
	    $w = array(28, 59, 26, 78);
	    for($i=0; $i < count( $header ); $i++){
	        $hBorder = 1;
	        if ($i == 2) {$hBorder = 'LRTB';}
	        if ($i == 1) {$hBorder = 'LRTB';}
	        $this->Cell($w[$i],4,$header[$i],$hBorder,0,'C',true);
	    }
	    $this->Ln();
	    
	    // Color and font restoration
	    $this->SetFillColor(224,235,255);
	    $this->SetTextColor(0);
	    
	    // Data
	    $fill = false;
	    $counter = 0;
	    $lineHeight = 5;
	    foreach( $data as $row ){
	        
	        if ( $counter > 0){
		        // if the date is different add a think border.
		        if ( $lastDate == substr ($row["NAC_date"],0,10)){ $border = '';}
		        else{ $border = 'T'; }
		}
	    	
		// case_number,	Defendant, NAC_date, NAC
		$caseNumberURL = "http://www.courtclerk.org/case_summary.asp?sec=sched&casenumber=" . $row["case_number"];
		$this->Cell($w[0],$lineHeight,$row["case_number"],'L' . $border  ,0,'L',$fill, $caseNumberURL );	            
	        $this->Cell($w[1],$lineHeight,ucwords( strtolower( substr ($row["caption"],17))),'L' . $border  ,0,'L',$fill);	            
	        $this->Cell($w[2],$lineHeight,substr ($row["NAC_date"],0,10),'L' . $border ,0,'L',$fill);
	        $this->Cell($w[3],$lineHeight,ucwords( strtolower( $row["NAC"])),'LR' . $border ,0,'L',$fill);
	        $this->Ln();
	        $fill = !$fill;
	        $lastDate = substr ($row["NAC_date"],0,10);
	        $counter++;
	        if ( $counter == 50){
	        	$this->Cell(array_sum($w),0,'','T');
	        	$this->AddPage();
	        }
	        // for counting case over time
	        $this->caseNumbers[] = $row["case_number"];
	    }

	    // Closing line
	 $this->caseNumbers = array_unique( $this->caseNumbers );
	 $this->Cell(array_sum($w),0,'','T');
	 $this->Ln(3);
	 $this->SetFont('Andale','', 14);

	 $freshness = "Total cases likely to be over time: approx. "
	    	. count( $this->caseNumbers )
	    	. ". \nPrepared on: " 
	    	. date('l F j') . ".";
	    	
	// MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])

	 $this->MultiCell(150,5,$freshness, 0, 'l', False );

	}
}

class caseOvertimeList
{
    //  Class variables

	    
	// Contruct object
	function __construct() {
	    if (isset($_GET["judge"])){
	        if ($_GET["judge"] == "all"){
	            $judgesArray = self::getJudgesNames();
	        }
	        else{
	        $judgesArray[] = htmlspecialchars($_GET["judge"]);
	        }
	    }
	    else{
	        $judgesArray[] = "Allen";
	    }
	    
	    $header = array('Case Number', 'Defendant','NAC Date', 'Setting');
	    $pdf = new PDF();

	    //	Loop through judges add a page for each.
	    foreach ($judgesArray as $key => $judge) {
	        $pdf->setJudge( $judge );
            	$overTimeCases = self::getOverTimeCases( $judge );
            	// echo "Printing overtime cases array right before passing to FancyTable:\n";
            	// print_r($overTimeCases);       	
		$pdf->AddPage();
    		$pdf->FancyTable( $header, $overTimeCases );
	    }
	   $pdf->Output();
	}
		
	protected function getOverTimeCases( $judgeName ){
		// Build the query.
		$today = date("Y-m-d");
		$sentinelCaseNum = self::getSentinelCaseNum();
		$query = "SELECT
				case_number,
				caption,
				NAC_date,
				NAC
			FROM nextActions
			WHERE 	judge like \"%$judgeName%\"
				and NAC_date >= \"$today\" 
				and case_number like \"%B%\"
				and case_number <= \"$sentinelCaseNum\" 
				and not NAC like \"%PROBATION%\"
				and not NAC like \"%PV%\"
				and not NAC like \"%CONVICTION%\"
				and not NAC like \"%EXPUNGEMENT%\"
				and not NAC like \"%JUDICIAL%\"
				and not NAC like \"%PROBABLE%\"
				and not NAC like \"%COMMUNITY%\"
			order by NAC_date";
	       		
		/*** connect to MySql database ***/
		include( "../passwordfiles/dbreader_pswd.php" );
		try 
		{
		    $dbh = mysql_connect('localhost', $dbuser, $dbpassword) 
		    or die(mysql_error());
		    mysql_select_db("todayspo_courtCal2") or die(mysql_error());
		}
		catch(PDOException $e)
		{
		    echo $e->getMessage();
		    echo "<br><br>Database -- NOT -- loaded successfully .. ";
		    die( "<br><br>Query Closed !!! $error");
		}

		// Query the database and loop through the results.
		$overTimeCases = array();				
		if( $result = mysql_unbuffered_query( $query )){
			while($row = mysql_fetch_array( $result, MYSQL_ASSOC )){
				//echo "dumping result.\n";
				$overTimeCases[] = $row;
				//print_r ( $row);
				// return $result;
			}
		}
		// print_r( $overTimeCases );
		return $overTimeCases;
	}

    protected function getJudgesNames(){
        return array("ALLEN/NADINE",
        "BURKE/KIM/WILSON",
        "COOPER/ETHNA/M",
        "DEWINE/PAT",
        "HELMICK/DENNIS/S",
        "KUBICKI JR/CHARLES/J",
        "LUEBBERS/JODY/M",
        "MARSH/MELBA/D",
        "MARTIN/STEVEN/E",
        "METZ/JEROME/J",
        "MYERS/BETH/A",
        "NADEL/NORBERT/A",
        "RUEHLMAN/ROBERT/P",
        "WEST/JOHN/ANDREW",
        "WINKLER/RALPH",
        "WINKLER/RALPH/E",
        "WINKLER/ROBERT/C");
    }
    
    protected function getSentinelCaseNum( ){
    	$month = date('m');
    	$year = date ('Y');
    	$result = strtotime( "{$year}-{$month}-01" );
    	
    	$endOfSentinelDay = strtotime('-1 second', strtotime('-5 month', $result));
 	$startOfSentinelDay = strtotime('- 23 hours', $endOfSentinelDay );
    	 	
    	// Query Db for DSCs on $result with highest case number. 
    	// If no DSC found look at 1 day earlier
    	$query = "SELECT case_number FROM nextActions "
	    . " WHERE"
	    . " NAC like \"%DSC%\""
	    . " and"
	    . " NAC_date between \"" 
	    . date( "Y-m-d H:i:s", $startOfSentinelDay) 
	    . "\" and \""
	    . date( "Y-m-d H:i:s", $endOfSentinelDay ) 
	    . "\" ORDER BY case_number DESC "
	    . " LIMIT 1";
    	require_once( "../passwordfiles/dbreader_pswd.php" );
	try 
	{
	    $dbh = mysql_connect('localhost', $dbuser, $dbpassword) 
	    or die(mysql_error());
	    mysql_select_db("todayspo_courtCal2") or die(mysql_error());
	}
	catch(PDOException $e)
	{
	    echo $e->getMessage();
	    echo "<br><br>Database -- NOT -- loaded successfully .. ";
	    die( "<br><br>Query Closed !!! $error");
	}

	// Query the database and loop through the results.
		
	if( $result = mysql_unbuffered_query( $query )){
		while($row = mysql_fetch_array( $result, MYSQL_ASSOC )){
			return $row["case_number"];
		}
	}
}
}
$myReport = new caseOvertimeList();
?>