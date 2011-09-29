<?php

require_once '../iCalcnv-2.0/iCalcnv/iCal2csv.php';

function getHolidays($holidayCalURI, $serverFileName){
    // get holiday URI and save it at $serverFileName
    // print "Getting " . $holidayCalURI . " Saving csv at " . $serverFileName;
    iCal2csv( '$holidayCalURI' , $conf=FALSE, $save=TRUE, $diskfilename = $serverFileName, $log=FALSE );
    
    // print "<br /><br />\n\nPassing " . $serverFileName . " to csv2Array.";
    $holidayCalendarArray = csv2Array($serverFileName);

    foreach ($holidayCalendarArray as $key => $value) {
        $dateKey = substr($value[DTSTART],-8);
        $note =   $value[SUMMARY];
        $holidayNotes[$dateKey] .= $note;
    }
    print_r($holidayNotes);
    return $holidayNotes;
}

function csv2Array($serverFileName){
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

// iCal2csv( 'http://www.google.com/calendar/ical/en.usa%23holiday%40group.v.calendar.google.com/public/basic.ics', $conf=FALSE, $save=TRUE, $diskfilename='holidayIcal.csv', $log=FALSE );

//iCal2csv( 'http://29r.net/calendarICS.php?start=-3months&last=18months&judge=allen&casetype=0', $conf=FALSE, $save=TRUE, $diskfilename='calfeed5.tmp', $log=FALSE );

//iCal2csv( 'https://www.google.com/calendar/ical/sebrenner%40gmail.com/private-25fa0616c76ef0826738c5065cb8023e/basic.ics', $conf=FALSE, $save=TRUE, $diskfilename='calfeed5.tmp', $log=FALSE );


getHolidays( 'http://www.google.com/calendar/ical/en.usa%23holiday%40group.v.calendar.google.com/public/basic.ics' , "holidayIcal.csv");


// $calendarArray = csv2Array('calfeed5.tmp');
// print "The original calendar contains " . count($calendarArray) . " events.<br />";

// $arrayOfNotableEvents = array_filter($calendarArray, "ebt");
// print "The filtered calendar contains " . count($arrayOfNotableEvents) . " events.<br />";

$minDate = 20110101;
$maxDate = 20120101;
$minTime = "8:00";
$maxTime = "12:00";



function ebt($event){
    $myDate = substr($event[DTSTART],-8);
    $myDate = substr($event[DTSTART],-8);
    global $minDate;
    global $maxDate;
    global $minTime;
    global $maxTime;
    /*
    For each event determine how start time is noted.
    See http://www.kanzaki.com/docs/ical/dtstart.html &
    http://www.kanzaki.com/docs/ical/dateTime.html
    E.g., 20110630T100000, VALUE=DATE:20121111, DATE-TIME:20110630T100000
    
    For each event determine if event is all day or starts at a secific time.  If VALUE=DATE then all day.
    
    If event starts at specific time, how end is specified: dtend or duration.
    
    Perhaps write a function that takes the dtstart or dtend value and returns the date-time in standard format, flagged for all day.
    
    Perhaps create a separate function just for holidays.
    
    */        
    

    
    if ($myDate >= $minDate && $myDate <= $maxDate) {
        
        if ($myTime >= $minTime && $myTime <= $maxTime) {
            // print $myDate . " >= " . $minDate ." && " . $myDate ." <= ".$maxDate . "<br />\n";
            return true;
        }
        
        // print $myDate . " >= " . $minDate ." && " . $myDate ." <= ".$maxDate . "<br />\n";
        return true;
    }
    // print "NOT!".  $myDate . " >= " . $minDate ." && " . $myDate ." <= ".$maxDate . "<br />\n";
    return false;

}




?>