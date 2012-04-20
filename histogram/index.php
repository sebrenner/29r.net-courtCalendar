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

class PDF extends FPDF
{
	// Page header
	function Header()
	{
	    // Arial bold 15
	    $this->SetFont('Arial','B',10);
	    // Move to the right
	    $this->Cell(80);
	    $title = "Judge " . ucfirst( $this->judge ) . " - Criminal Trial Settings Histogram     " . "                  " . $this->freshness;
	    $memo = "No criminal jury trials on Mondays.  No more that 10 settings per day.";
	    $this->Cell(30,5,$title,0,1,'C');
	    $this->Cell(80);
	    $this->Cell(30,5,$memo,0,0,'C');
	    // Line break
	    $this->Ln(5);
	}

	function Footer()
	{
	    $this->SetFont('Arial','',8);
	    print $totalSettings;
	    $freshness= "Total settings:" . $this->totalSettings . ". Prepared on: " . date('l F j') . ".";
	    $notes = "j = jury trial; b = bench trial; s = sentence; m = motion; p = plea; e = expungement; * = other. Uppercase indicates defendant is locked up.";
	    $this->Cell(80);
	    $this->Cell(30,5,$freshness,0,1,'C');
	    $this->Cell(80);
	    $this->Cell(30,5,$notes,0,0,'C');
	    // Line break
	    $this->Ln(10);
	}

    function setJudge($judge){
        $this->judge = $judge;
    }

    function setTotal($number){
        $this->totalSettings = $number;
    }

	function setFreshness( $dateTime ){
		$this->freshness = date( 'l F jS h:i:s A', strtotime( $dateTime ) );
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
	        $this->Cell($w[3],$lineHeight,$row[3],'LR' . $border ,0,'L',$fill);
	        $this->Ln();
	        $fill = !$fill;
	    }
	    // Closing line
	    // $this->Cell(array_sum($w),0,'','T');
	}
}

class activityHistogram
{
    //  Class variables
    protected $holidays;
	protected $freshess;
	    
	// Contruct object
	function __construct() {
	    // Create holidays array
        // $this->holidays = self::getHolidays( 'http://www.google.com/calendar/ical/en.usa%23holiday%40group.v.calendar.google.com/public/basic.ics' , "holidayIcal.csv");
        
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
	    
        $header = array('Date', 'Criminal Settings','', 'Notes');
		$pdf = new PDF();

		//	Loop through judges add a page for each.
	    foreach ($judgesArray as $key => $value) {
	        $pdf->setJudge( $value );
            $NACandTotal = self::createNACData( $value );
			$pdf->setTotal( $NACandTotal[1] );
			$pdf->setFreshness( $this->freshness );
			$pdf->AddPage();
    		$pdf->FancyTable( $header, $NACandTotal[0] );
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
		$query = "SELECT NAC_date, NAC, lockup, freshness
			FROM nextActions WHERE
			NAC_date between '{$startDate}' 
			and '{$endDate }' 
			AND case_number like '%B %'
			AND judge like '%{$judgeName}%'";
        
        // echo $query;
		
		/*** connect to MySql database ***/
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
		if($result = mysql_unbuffered_query($query)){
			// Add histogram to NACData dictionary
			while($row = mysql_fetch_array( $result, MYSQL_ASSOC )){
				$NACData[ substr( $row[ "NAC_date" ], 0, 10 ) ] .= 
				self::getLetter( $row[ "NAC" ], $row[ "lockup" ] );
				
				// pass freshness to $pdf.  Most recent freshness will be retained by $pdf.
				self::getFreshness( $row[ "freshness" ]);
			}
		}
		// Convert dictionary to array of arrays 
		$notes = "1234567890";
		$totalSettings = 0;
		
		//	Loop through array building results to populat the histogram
		foreach ($NACData as $key => $value){
            $results[] = array( $key, self::sortString($value), strlen($value), self::getNotes($key) );
            $totalSettings += (int)strlen($value);
		}
        //print_r ($results);
        return array($results, $totalSettings);
	}

	protected function getLetter( $nac, $locked ){
		$settingDictionary = array(	"jury" => "j",
						"bench" => "b",
						"motion" => "m",
						"expung" => "e",	
						"sentenc" => "s");
		//print_r($settingDictionary);
		foreach ($settingDictionary as $key => $value){
			//print $key;
			// case insensive
			$pos = stripos($nac,$key);
			//print $nac . " " . $key . $pos ."\n";
			if($pos === false) {
				if ($nac == "PLEA"){
					if ( $locked ) { return "P";}
					return "p";
				}
				continue;
			}
			else {
	 			if ( $locked ) { return strtoupper( $value );}
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

	protected function getFreshness( $dateTime ){
		if( $dateTime > $this->freshness ){
			$this->freshness = $dateTime;
		}
    }

    protected function getNotes( $date ){
        // print_r($this->holidays);
        return $this->holidays[str_replace ( "-" , "" , $date )];
        return "Notes";
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
	
    protected function getHolidays($holidayCalURI, $serverFileName){
        iCal2csv( '$holidayCalURI' , $conf=FALSE, $save=TRUE, $diskfilename = $serverFileName, $log=FALSE );

        $holidayCalendarArray = self::csv2Array($serverFileName);

        foreach ($holidayCalendarArray as $key => $value) {
            $dateKey = substr($value[DTSTART],-8);
            $note =   $value[SUMMARY];
            $holidayNotes[$dateKey] .= $note;
        }
        // print_r($holidayNotes);
        return $holidayNotes;
    }

    protected function csv2Array($serverFileName){
        $row = 1;
        if (($handle = fopen($serverFileName, "r")) !== FALSE) {
            while (($line = fgetcsv($handle, 1000)) !== FALSE) {
                if ($line[0] == "X-WR-CALNAME") $calName = $line[1];
                if ($line[0] == "TYPE") $header = $line;
                if ($line[0] == "vevent"){
                    // Loop through line and create and array with headers as keys
                    for ($i = 0; $i < count($line); $i++) $event[$header[$i]] = $line[$i];
                    // Add calName to event
                    $event["calName"] = $calName;
                    // Add event to array of events
                    $eventsArray[] = $event;
                }
            }
            fclose($handle);
            // print_r($eventsArray);
            return $eventsArray;
        }
    }
}

$myHistogram = new activityHistogram();
?>Histogram = new activityHistogram();
?>;
?>;
?>