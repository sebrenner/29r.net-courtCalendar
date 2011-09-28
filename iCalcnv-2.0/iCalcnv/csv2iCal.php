<?php
/**
 * csv2iCal
 * ver 2.0
 *
 * copyright (c) 2009 Kjell-Inge Gustafsson kigkonsult
 * www.kigkonsult.se/index.php
 * ical@kigkonsult.se
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
**/
/**
 * function csv2iCal
 *
 * Convert csv file to iCal format and send file to browser (default) or save Ical file to disk
 * Definition iCal  : rcf2445, http://localhost/work/kigkonsult.se/downloads/index.php#rfc2445
 * Definition csv   : http://en.wikipedia.org/wiki/Comma-separated_values
 * Using iCalcreator: http://localhost/work/kigkonsult.se/downloads/index.php#iCalcreator
 * csv directory/file read/write will be directed to error_log
 *
 * @author Kjell-Inge Gustafsson <ical@kigkonsult.se>
 * @since 2.0 - 2009-03-26
 * @param string $filename      file to convert (incl. opt. directory)
 * @param array  $conf          opt, default FALSE(=array('del'=>'"','sep'=>',', 'nl'=>'\n'), delimiter, separator and newline characters
 *                              escape sequences will be expanded, '\n' will be used as "\n" etc.
 *                              also map iCal property names from user friendly names, ex. 'DTSTART' => 'startdate'
 * @param string $unique_id     used in iCalcreator then creating properties PRODID (at calendar level ) and UID (component)
 * @param bool   $save          opt, default FALSE, TRUE=save to disk
 * @param string $diskfilename  opt, filename for file to save or else taken from $filename + 'ics' extension
 * @param object $log           opt, default FALSE (error_log), writes log to file using PEAR LOG or eClog class
 * @return bool                 returns FALSE when error
 */
function csv2iCal( $filename, $conf=FALSE, $unique_id=FALSE, $save=FALSE, $diskfilename=FALSE, & $log=FALSE ) {
  if( $log ) $timeexec = array( 'start' => microtime( TRUE ));
  $csv2iCal_VERSION = 'csv2iCal 2.0';
  if( !function_exists( 'fileCheckRead' ))
    require_once 'fileCheck.php';
  if( !class_exists( 'vcalendar', FALSE ))
    require_once 'iCalcreator.class.php';
  if( $log ) $log->log( "$csv2iCal_VERSION input=$filename, conf=".var_export($conf,TRUE).", unique_id=$unique_id, save=$save, diskfilename=$diskfilename", 7 );
  $remoteInput = ( in_array( strtolower( substr( $filename, 0, 7 )), array( 'http://', 'webcal:' ))) ? TRUE : FALSE;
  // field DELimiter && field SEParator
  if( !$conf ) $conf = array();
  if( !isset( $conf['del'] ))
    $conf['del'] = '"';
  if( !isset( $conf['sep'] ))
    $conf['sep'] = ',';
  if( !isset( $conf['nl'] ))
    $conf['nl']  = "\n";
  foreach( $conf as $key => $value ) {
    if( in_array( $key, array( 'del', 'sep', 'nl' )))
      $conf[$key] = "$value";
    else {
      $conf[strtoupper( $value )] = strtoupper( $key ); // flip map names
      if( $log ) $log->log( "$csv2iCal_VERSION $value mapped to $key", 7 );
    }
  }
  /* create path and filename */
  if( $remoteInput ) {
    $inputFileParts = parse_url( $filename );
    $inputFileParts = array_merge( $inputFileParts, pathinfo( $inputFileParts['path'] ));
    if( $save && !$diskfilename )
      $diskfilename = $inputFileParts['filename'].'.ics';
  }
  else {
    if( FALSE === ( $filename = fileCheckRead( $filename, $log ))) {
      if( $log )  {
        $log->log( "$csv2iCal_VERSION (".number_format(( microtime( TRUE ) - $timeexec['start'] ),  5 ).')' );
        $log->flush();
      }
      return FALSE;
    }
    $inputFileParts = pathinfo( $filename );
    if( $save && !$diskfilename )
      $diskfilename = $inputFileParts['dirname'].DIRECTORY_SEPARATOR.$inputFileParts['filename'].'.ics';
  }
  if( $save ) {
    $outputFileParts = pathinfo( $diskfilename );
    if( FALSE === ( $diskfilename = fileCheckWrite( $outputFileParts['dirname'].DIRECTORY_SEPARATOR.$outputFileParts['basename'], $log ))) {
      if( $log ) {
        $log->log( "$csv2iCal_VERSION (".number_format(( microtime( TRUE ) - $timeexec['start'] ),  5 ).')' );
        $log->flush();
      }
      return FALSE;
    }
  }
  if( $log ) {
    $msg = $csv2iCal_VERSION.' INPUT FILE:"'.$inputFileParts['dirname'].DIRECTORY_SEPARATOR.$inputFileParts['basename'].'"';
    if( $save )
      $msg .= ' OUTPUT FILE: "'.$outputFileParts['dirname'].DIRECTORY_SEPARATOR.$outputFileParts['basename'].'"';
    $log->log( $msg, 7 );
  }
  /* read csv file into input array */
  $fp = fopen( $filename, "r" );
  if( FALSE === $fp ) {
    $msg = $csv2iCal_VERSION.' ERROR 3 INPUT FILE:"'.$filename.'" unable to read file: "'.$inputFileParts['dirname'].'"';
    if( $log ) {
      $log->log( $msg, 3 );
      $log->log( "$csv2iCal_VERSION (".number_format(( microtime( TRUE ) - $timeexec['start'] ),  5 ).')' );
      $log->flush(); }
    else
      error_log( $msg );
    return FALSE;
  }
  $rows = array();
  while ( FALSE !== ( $row = fgetcsv( $fp, FALSE, $conf['sep'], $conf['del'] )))
    $rows[] = $row;
  fclose( $fp );
  $cntrows = count( $rows );
  /* iCalcreator check when setting directory and filename */
  $calendar = new vcalendar();
  if( $unique_id )
    $calendar->setConfig( 'unique_id', $unique_id );
  if( $save ) {
    if( FALSE === $calendar->setConfig( 'directory', $outputFileParts['dirname'] )) {
        $msg = $csv2iCal_VERSION.' ERROR 4 INPUT FILE:"'.$filename.'" iCalcreator: invalid directory: "'.$outputFileParts['dirname'].'"';
        if( $log ) {
          $log->log( $msg, 3 );
          $log->log( "$csv2iCal_VERSION (".number_format(( microtime( TRUE ) - $timeexec['start'] ),  5 ).')' );
          $log->flush();
        }
        else
          error_log( $msg );
        return FALSE;
    }
    if( FALSE === $calendar->setConfig( 'filename',  $outputFileParts['basename'] )) {
      $msg = $csv2iCal_VERSION.' ERROR 5 INPUT FILE:"'.$filename.'" iCalcreator: invalid filename: "'.$outputFileParts['basename'].'"';
      if( $log ) {
        $log->log( $msg, 3 );
        $log->log( "$csv2iCal_VERSION (".number_format(( microtime( TRUE ) - $timeexec['start'] ),  5 ).')' );
        $log->flush();
      }
      else
        error_log( $msg );
      return FALSE;
    }
  }
  elseif( FALSE === $calendar->setConfig( 'filename',  $inputFileParts['filename'].'.ics' )) {
    $msg = $csv2iCal_VERSION.' ERROR 6 INPUT FILE:"'.$filename.'" iCalcreator: invalid export filename: "'.$outputFileParts['filename'].'.ics"';
    if( $log ) {
      $log->log( $msg, 3 );
      $log->log( "$csv2iCal_VERSION (".number_format(( microtime( TRUE ) - $timeexec['start'] ),  5 ).')' );
      $log->flush();
    }
    else
      error_log( $msg );
    return FALSE;
  }
  if( $log ) $timeexec['fileOk'] = microtime( TRUE );
  /* info rows */
  $actrow = 0;
  for( $row = $actrow; $row < $cntrows; $row++ ) {
    if( empty( $rows[$row] ) ||
       ( 1 >= count( $rows[$row] )) ||
       ( '' >= $rows[$row][1] ) ||
       ( 'iCal' == substr( $rows[$row][0], 0, 4 )) ||
       ( 'kigkonsult.se' == $rows[$row][0] ))
      continue;
    elseif( 'TYPE' == strtoupper( $rows[$row][0] )) {
      $actrow = $row;
      break;
    }
    elseif( 'CALSCALE' == strtoupper( $rows[$row][0] ))
      $calendar->setProperty( 'CALSCALE', $rows[$row][1] );
    elseif( 'METHOD' == strtoupper( $rows[$row][0] ))
      $calendar->setProperty( 'METHOD', $rows[$row][1] );
    elseif( 'X-' == substr( $rows[$row][0], 0, 2 ))
      $calendar->setProperty( $rows[$row][0], $rows[$row][1] );
    elseif( 2 >= count( $rows[$row] ))
      continue;
    else {
      $actrow = $row;
      break;
    }
  }
  if( $log ) $timeexec['infoOk'] = microtime( TRUE );
  $cntprops = 0;
  /* fix opt. vtimezone */
  if(( $actrow < $cntrows) && ( in_array( 'tzid', $rows[$actrow] ) || in_array( 'TZID', $rows[$actrow] ))) {
    foreach( $rows[$actrow] as $key => $header ) {
      $header = strtoupper( $header );
      if( isset( $conf[$header] )) {
        $proporder[$conf[$header]] = $key; // check map of userfriendly name to iCal property name
        if( $log ) $log->log( "$csv2iCal_VERSION header row ix:$key => $header, replaced by ".$conf[$header], 7 );
      }
      else
        $proporder[$header] = $key;
    }
    $allowedProps = array( 'VTIMEZONE' => array( 'TZID', 'LAST-MODIFIED', 'TZURL' )
                         , 'STANDARD'  => array( 'DTSTART', 'TZOFFSETTO', 'TZOFFSETFROM', 'COMMENT', 'RDATE', 'RRULE', 'TZNAME' )
                         , 'DAYLIGHT'  => array( 'DTSTART', 'TZOFFSETTO', 'TZOFFSETFROM', 'COMMENT', 'RDATE', 'RRULE', 'TZNAME' ));
    $actrow++;
    $comp = $subcomp = $actcomp = FALSE;
    for( $row = $actrow; $row < $cntrows; $row++ ) {
      if( empty( $rows[$row] ) || ( 1 >= count( $rows[$row] )))
        continue;
      $compname = strtoupper( $rows[$row][0] );
      if( 'TYPE' == $compname ) { // next header
        $actrow = $row;
        break;
      }
      if( $comp && $subcomp ) {
        $comp->setComponent( $subcomp );
        $subcomp = FALSE;
      }
      if( 'VTIMEZONE' == $compname ) {
        if( $comp )
          $calendar->setComponent( $comp );
        $comp = new vtimezone();
        $actcomp = & $comp;
        $cntprops += 1;
      }
      elseif( 'STANDARD' == $compname ) {
        $subcomp = new vtimezone( 'STANDARD' );
        $actcomp = & $subcomp;
      }
      elseif( 'DAYLIGHT' == $compname ) {
        $subcomp = new vtimezone( 'DAYLIGHT' );
        $actcomp = & $subcomp;
      }
      else {
        if( $log ) $log->log( "$csv2iCal_VERSION $compname skipped", 4 );
        continue;
      }
      foreach( $proporder as $propName => $col ) { // insert all properties into component
        if(( 2 > $col ) || ( 'ORDER' == strtoupper( $propName )))
          continue;
        $propName = strtoupper( $propName );
        if(( 'X-' != substr( $propName, 0, 2 )) &&
           ( !in_array( $propName, $allowedProps[$compname] ))) { // check if allowed property for the component
          if( $log ) $log->log( "$csv2iCal_VERSION $compname: $propName skipped", 7 );
          continue;
        }
        if( isset( $rows[$row][$col] ) && !empty( $rows[$row][$col] )) {
          $value = ( FALSE !== strpos( $rows[$row][$col], $conf['nl'] )) ? explode( $conf['nl'], $rows[$row][$col] ) : array( $rows[$row][$col] );
          foreach( $value as $val ) {
            if( empty( $val ) && ( '0' != $val ))
               continue;
            if( $log ) $log->log( "$csv2iCal_VERSION $propName=$val", 7 );
            if(( 'RDATE' == $propName ) || ( 'RRULE' == $propName )) {
              if( FALSE === $actcomp->parse( "$propName:$val" )) {
                $msg = $csv2iCal_VERSION.' ERROR 7 INPUT FILE:"'.$filename." iCalcreator: parse error: $propName:$val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
            elseif(( substr_count( $val, '=' ) == (substr_count( $val, ';' ) + 1)) && ( 1 >= substr_count( $val, ':' ))) {
              if( FALSE === $actcomp->parse( "$propName;$val" )) { // any param (LANGUAGE etc.) is set
                $msg = $csv2iCal_VERSION.' ERROR 8 INPUT FILE:"'.$filename." iCalcreator: parse error: $propName;$val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
            else {
              if( FALSE === $actcomp->setProperty( $propName, $val )) {
                $msg = $csv2iCal_VERSION.' ERROR 9 INPUT FILE:"'.$filename." iCalcreator: setProperty error: $propName, $val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
          } // end foreach( $value
        } // end if( isset
      } // end foreach( $proporder
    } // end for( $row = $actrow
    if( $comp && $subcomp )
      $comp->setComponent( $subcomp );
    if( $comp )
      $calendar->setComponent( $comp );
    $comp = $subcomp = $actcomp = FALSE;
  }
  if( $log ) $timeexec['zoneOk'] = microtime( TRUE );
  /* fix data */
  if(( $actrow < $cntrows) && isset( $rows[$actrow][0] ) && ( 'TYPE' == strtoupper( $rows[$actrow][0] ))) {
    foreach( $rows[$actrow] as $key => $header ) {
      $header = strtoupper( $header );
      if( isset( $conf[$header] )) {
        $proporder[$conf[$header]] = $key; // check map of user friendly name to iCal property name
        if( $log ) $log->log( "$csv2iCal_VERSION header row ix:$key => $header, replaced by ".$conf[$header], 7 );
      }
      else
        $proporder[$header] = $key;
    }
    $allowedProps = array( 'VEVENT'    => array( 'ATTACH', 'ATTENDEE', 'CATEGORIES', 'CLASS', 'COMMENT', 'CONTACT', 'CREATED', 'DESCRIPTION', 'DTEND'
                                               , 'DTSTAMP', 'DTSTART', 'DURATION', 'EXDATE', 'RXRULE', 'GEO', 'LAST-MODIFIED', 'LOCATION', 'ORGANIZER'
                                               , 'PRIORITY', 'RDATE', 'RECURRENCE-ID', 'RELATED-TO', 'RESOURCES', 'RRULE', 'REQUEST-STATUS', 'SEQUENCE'
                                               , 'STATUS', 'SUMMARY', 'TRANSP', 'UID', 'URL', )
                         , 'VTODO'     => array( 'ATTACH', 'ATTENDEE', 'CATEGORIES', 'CLASS', 'COMMENT', 'COMPLETED', 'CONTACT', 'CREATED', 'DESCRIPTION'
                                               , 'DTSTAMP', 'DTSTART', 'DUE', 'DURATION', 'EXATE', 'EXRULE', 'GEO', 'LAST-MODIFIED', 'LOCATION', 'ORGANIZER'
                                               , 'PERCENT', 'PRIORITY', 'RDATE', 'RECURRENCE-ID', 'RELATED-TO', 'RESOURCES', 'RRULE', 'REQUEST-STATUS'
                                               , 'SEQUENCE', 'STATUS', 'SUMMARY', 'UID', 'URL' )
                         , 'VJOURNAL'  => array( 'ATTACH', 'ATTENDEE', 'CATEGORIES', 'CLASS', 'COMMENT', 'CONTACT', 'CREATED', 'DESCRIPTION', 'DTSTAMP'
                                               , 'DTSTART', 'EXDATE', 'EXRULE', 'LAST-MODIFIED', 'ORGANIZER', 'RDATE', 'RECURRENCE-ID', 'RELATED-TO'
                                               , 'RRULE', 'REQUEST-STATUS', 'SEQUENCE', 'STATUS', 'SUMMARY', 'UID', 'URL' )
                         , 'VFREEBUSY' => array( 'ATTENDEE', 'COMMENT', 'CONTACT', 'DTEND', 'DTSTAMP', 'DTSTART', 'DURATION', 'FREEBUSY', 'ORGANIZER', 'UID', 'URL' )
                         , 'VALARM'    => array( 'ACTION', 'ATTACH', 'ATTENDEE', 'DESCRIPTION', 'DURATION', 'REPEAT', 'TRANSP', 'TRIGGER' ));
    $actrow++;
    $comp = $subcomp = $actcomp = FALSE;
    $allowedComps = array( 'VEVENT', 'VTODO', 'VJOURNAL', 'VFREEBUSY' );
    $recurs = array ( 'EXDATE', 'RDATE', 'EXRULE', 'RRULE' );
    for( $row = $actrow; $row < $cntrows; $row++ ) {
      if( empty( $rows[$row] ) || ( 1 >= count( $rows[$row] )))
        continue;
      if( $comp && $subcomp ) {
        $comp->setComponent( $subcomp );
        $subcomp = FALSE;
      }
      $compname = strtoupper( $rows[$row][0] );
      if( in_array( $compname, $allowedComps )) {
        if( $comp )
          $calendar->setComponent( $comp );
        $comp = new $rows[$row][0];
        $actcomp = & $comp;
        $cntprops += 1;
      }
      elseif( 'VALARM' == $compname ) {
        $subcomp = new valarm();
        $actcomp = & $subcomp;
      }
      else {
        if( $log ) $log->log( "$csv2iCal_VERSION $compname skipped", 4 );
        continue;
      }
      foreach( $proporder as $propName => $col ) { // insert all properties into component
        if(( 2 > $col ) || ( 'ORDER' == strtoupper( $propName )))
          continue;
        $propName = strtoupper( $propName );
        if(( 'X-' != substr( $propName, 0, 2 )) &&
           ( !in_array( $propName, $allowedProps[$compname] ))) { // check if allowed property for the component
          if( $log ) $log->log( "$csv2iCal_VERSION $compname: $propName skipped", 7  );
          continue;
        }
        if( isset( $rows[$row][$col] ) && !empty( $rows[$row][$col] )) {
          $value = ( FALSE !== strpos( $rows[$row][$col], $conf['nl'] )) ? explode( $conf['nl'], $rows[$row][$col] ) : array( $rows[$row][$col] );
          foreach( $value as $val ) {
            if( empty( $val ) && ( '0' != $val ) && ( 0 != $val ))
               continue;
            if( $log ) $log->log( "$propName=$val", 7 );
            if( 'GEO' == $propName ) {
              $parseval = ( FALSE !== strpos( $val, ':' )) ? "GEO$val" : "GEO:$val";
              if( FALSE === $actcomp->parse( $parseval )) {
                $msg = $csv2iCal_VERSION.' ERROR 10 INPUT FILE:"'.$filename." iCalcreator: parse error: $parseval";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
            elseif( 'REQUEST-STATUS' == $propName ) { // 'REQUEST-STATUS' without any parameters.. .
              if( FALSE === $actcomp->parse( "$propName:$val" )) {
                $msg = $csv2iCal_VERSION.' ERROR 11 INPUT FILE:"'.$filename." iCalcreator: parse error: $propName:$val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
            elseif( in_array( $propName, $recurs ) && ( 1 > substr_count( $val, ':' ))) { // recurrent rules
              if( FALSE === $actcomp->parse( "$propName:$val" )) {
                $msg = $csv2iCal_VERSION.' ERROR 12 INPUT FILE:"'.$filename." iCalcreator: parse error: $propName:$val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
            elseif(( substr_count( $val, '=' ) == (substr_count( $val, ';' ) + 1)) && ( 2 >= substr_count( $val, ':' ))) {
              if( FALSE === $actcomp->parse( "$propName;$val" )) { // some parameter (LANGUAGE etc.) is set
                $msg = $csv2iCal_VERSION.' ERROR 13 INPUT FILE:"'.$filename." iCalcreator: parse error: $propName;$val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            }
            else {
              if( FALSE === $actcomp->setProperty( $propName, $val )) { // no parameters at all
                $msg = $csv2iCal_VERSION.' ERROR 14 INPUT FILE:"'.$filename." iCalcreator: setProperty error: $propName, $val";
                if( $log ) $log->log( $msg, 3 ); else error_log( $msg );
              }
            } // end else
          } // end foreach( $value as $val
        } // end if( isset( $rows[$row][$col]
      } // end foreach( $proporder
    } // end for( $row = $actrow;
    if( $comp && $subcomp )
      $comp->setComponent( $subcomp );
    if( $comp )
      $calendar->setComponent( $comp );
  }
  if( $log ) {
    $timeexec['exit'] = microtime( TRUE );
    $msg  = "$csv2iCal_VERSION '$filename'";
    $msg .= ' fileOk:' .number_format(( $timeexec['fileOk']  - $timeexec['start'] ),  5 );
    $msg .= ' infoOk:' .number_format(( $timeexec['infoOk']  - $timeexec['fileOk'] ), 5 );
    $msg .= ' zoneOk:' .number_format(( $timeexec['zoneOk']  - $timeexec['infoOk'] ), 5 );
    $msg .= ' compOk:' .number_format(( $timeexec['exit']    - $timeexec['zoneOk'] ), 5 );
    $msg .= ' total:'  .number_format(( $timeexec['exit']    - $timeexec['start'] ),  5 ).'sec';
    $log->log( $msg, 7 );
    $msg  = "$csv2iCal_VERSION '$filename' (".$cntprops.' components) start:'.date( 'H:i:s', $timeexec['start'] );
    $msg .= ' total:'  .number_format(( $timeexec['exit']    - $timeexec['start'] ),  5 ).'sec';
    if( $save )
      $msg .= " -> '$diskfilename'";
    $log->log( $msg, 6 );
  }
  /* save or send the file */
  if( $save ) {
    if( FALSE !== $calendar->saveCalendar()) {
      if( $log ) $log->flush();
      return TRUE;
    }
    else { // ??
      $d = $calendar->getConfig( 'directory' );
      $f = $calendar->getConfig( 'filename' );
      $msg = $csv2iCal_VERSION.' ERROR 15 INPUT FILE:"'.$filename.'" Invalid write to output file : "'.$d.DIRECTORY_SEPARATOR.$f.'"';
      if( $log ) { $log->log( $msg, 3 ); $log->flush(); $log->flush(); } else error_log( $msg );
      return FALSE;
    }
  }
  else {
    if( $log ) $log->flush();
    $calendar->returnCalendar();
  }
  exit();
}
?>