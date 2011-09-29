<?php
/**
 * Calendar histogram creator 
 * 29r.net/calendarHistogram.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and 
 * returns an array of dates->"notes"  Notes is 46 or less characters.
 *  
 */

class notes 
{
    protected function getNotes($dbConnection, $startDate, $endDate, $judges){
        // Get events from iCals
        $cvsString = Cal2csv(); // get parameters
        $calArray = csv2Array( $csvString );
        
        $ebtStart       = $startDate;   // as date only
        $ebtStartTime   = '8:00';       // as time only
        $ebtEnd         = $endDate;     // as date only
        $ebtEndTime     = '13:00';      // as time only
        $selectedEvents = array_filter($calArray, 'ebt')
    
    
        function ebt($array){
            global $ebtStart;
        }
	}

}

?>