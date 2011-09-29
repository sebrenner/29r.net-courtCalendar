<?php

require_once '../iCalcnv-2.0/iCalcnv/iCal2csv.php';


function csv2Array(){
    $row = 1;
    if (($handle = fopen("calfeed.tmp", "r")) !== FALSE) {
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

iCal2csv( 'http://www.google.com/calendar/ical/en.usa%23holiday%40group.v.calendar.google.com/public/basic.ics', $conf=FALSE, $save=TRUE, $diskfilename='calfeed.tmp', $log=FALSE );

$calendarArray = csv2Array();
print "The original calendar contains " . count($calendarArray) . " events.<br />";

$arrayOfNotableEvents = array_filter($calendarArray, "ebt");
print "The filtered calendar contains " . count($arrayOfNotableEvents) . " events.<br />";

$minValue = 20110101;
$maxValue = 20120101;

function ebt($event){
    $myValue = substr($event[DTSTART],-8);
    global $minValue;
    global $maxValue;
    
    if ($myValue >= $minValue && $myValue <= $maxValue) {
        // print $myValue . " >= " . $minValue ." && " . $myValue ." <= ".$maxValue . "<br />\n";
        return true;
    }
    // print "NOT!".  $myValue . " >= " . $minValue ." && " . $myValue ." <= ".$maxValue . "<br />\n";
    return false;

}




?>