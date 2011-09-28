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
require('fpdf17/fpdf.php');

class PDF extends FPDF
{
	// Page header
	function Header()
	{
	    // Arial bold 15
	    $this->SetFont('Arial','B',10);
	    // Move to the right
	    $this->Cell(80);
	    // Title  Prepared on: " . date('l F j') . ". 
	    $title = "Judge " . ucfirst($this->judge) . " - Criminal Trial Settings Histogram";
	    $memo = "No criminal jury trials on Mondays.  No more that 15 settings per day.";
	    $this->Cell(30,5,$title,0,1,'C');
	    $this->Cell(80);
	    $this->Cell(30,5,$memo,0,0,'C');
	    // Line break
	    $this->Ln(5);
	}

	function Footer()
	{
	    // Arial bold 15
	    $this->SetFont('Arial','',8);
	    // Move to the right
	    $this->Cell(80);
	    // Title
	    print $totalSettings;
	    $title = "Total settings:" . $this->totalSettings . ". Prepared on: " . date('l F j') . ".";
	    $this->Cell(30,7,$title,0,0,'C');
	    // Line break
	    $this->Ln(10);
	}

    function setJudge($judge){
        $this->judge = $judge;
    }

    function setTotal($number){
        $this->totalSettings = $number;
    }
	
	// Colored table
	function FancyTable($header, $data)
	{
	    // Colors, line width and bold font
	    $this->SetFillColor(255,0,0);
	    $this->SetTextColor(255);
	    $this->SetDrawColor(128,0,0);
	    $this->SetLineWidth(0);
	    $this->AddFont('Andale', '', 'Andale Mono.php');
        $this->SetFont('Andale','', 10);
	    
	    // Header
	    $w = array(23, 64, 5, 100);
	    for($i=0; $i < count( $header ); $i++){
	        $hBorder = 1;
	        if ($i == 2) {$hBorder = 'RTB';}
	        if ($i == 1) {$hBorder = 'LTB';}
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
	    foreach($data as $row)
	    {
	        $date = date_create($row[0]);
	        $counter++;
	        if ($counter %5 == 0){
	            $border = 'B';
	        }else{$border = '';}

	        $this->Cell($w[0],$lineHeight,date_format($date, 'D m/d'),'LR' . $border,0,'L',$fill);
	        $this->Cell($w[1],$lineHeight,$row[1],'L' . $border  ,0,'L',$fill);	            
	        $this->Cell($w[2],$lineHeight,$row[2],'R' . $border ,0,'R',$fill);
	        $this->Cell($w[3],$lineHeight,$row[3],'LR' . $border ,0,'C',$fill);
	        $this->Ln();
	        $fill = !$fill;
	    }
	    // Closing line
	    // $this->Cell(array_sum($w),0,'','T');
	}
}

class activityHistogram
{
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
	    
	    $pdf = new PDF();
        $header = array('Date', 'Criminal Settings','', 'Notes');
	    foreach ($judgesArray as $key => $value) {
	        $pdf->setJudge($value);
	        $pdf->setTotal($NACandTotal[1]);
            $pdf->AddPage();
            $NACandTotal = self::createNACData($value);
            $pdf->setTotal($NACandTotal[1]);
            // print_r ($data);
    		$pdf->FancyTable($header, $NACandTotal[0]);
	    }
		$pdf->Output();
	}
		
	protected function createNACData($judgeName){
	    // Returns the most recent monday, or today if today is Monday
		$startDate = date("Y-m-d", strtotime('last monday', strtotime('tomorrow')));
		
        // Create an array with weekdays as keys for the next 60 weekdays
		for ($i=0, $j=0; $i<50; $i++, $j++) {
           $nextDay = strtotime('+' . $j . ' days', strtotime('last monday', strtotime('tomorrow')));
           if (date('w', $nextDay) > 0 && date('w', $nextDay) < 6) {
               $datestring = date('Y-m-d', $nextDay);
               $NACData[$datestring] = "";
           } else {
               $i--;
           }
        }
        end($NACData);          // move the internal pointer to the end of the array
        $endDate = key($NACData);
        reset($NACData);
        
		// Build the query.
		$query = "SELECT NAC_date, NAC 
			FROM nextActions WHERE
			NAC_date between '{$startDate}' 
			and '{$endDate }' 
			AND case_number like '%B %'
			AND judge like '%{$judgeName}%'";
        
        //echo $query;
		
        
		/*** connect to MySql database ***/
		try 
		{
		    $dbh = mysql_connect('localhost', 'todayspo_ctDbRdr', '4W(Rn*aLgdXi') 
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
		if($result = mysql_unbuffered_query($query)){
        // print_r ($NACData);
        
        // Add histogram to NACData dictionary
		   while($row = mysql_fetch_array( $result, MYSQL_ASSOC )){
		   	$NACData[substr($row["NAC_date"], 0, 10)] .= 
                self::getLetter($row["NAC"]);
		   }
		}
		// Convert dictionary to array of arrays 
		$notes = "";
		$totalSettings = 0;
		foreach ($NACData as $key => $value){
            $results[] = array($key, self::sortString($value),  strlen($value) ,$notes);
            $totalSettings += (int)strlen($value);
		}
        // print_r ($results);
        return array($results, $totalSettings);
	}

	protected function getLetter($nac){
		$settingDictionary = array(	"jury" => "J",
						"bench" => "B",
						"motion" => "M",	
						"sentenc" => "S");
		//print_r($settingDictionary);
		foreach ($settingDictionary as $key => $value){
			//print $key;
			// case insensive
			$pos = stripos($nac,$key);
			//print $nac . " " . $key . $pos ."\n";
			if($pos === false) {
				if ($nac == "PLEA"){ return "P";}
				continue;
			}
			else {
	 			return $value;
			}
		}
		return "*";
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

	protected function sortString($string){
		for ($i = 0; $i <= strlen($string); $i++) {
			$myArray[] = $string[$i];
		}
		//print_r($myArray);
		sort($myArray);
		foreach ($myArray as $key => $value){
			$newString .= $value;
		}
		return $newString;
	}
}

$myHistogram = new activityHistogram();
?>