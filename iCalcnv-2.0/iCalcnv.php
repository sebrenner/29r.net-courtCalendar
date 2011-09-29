<?php
/**
 * iCalcnv
 * ver 2.0
 *
 * copyright (c) 2009 Kjell-Inge Gustafsson kigkonsult
 * www.kigkonsult.se/index.php
 * ical@kigkonsult.se
 * updated 20090326
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
 */
if( !class_exists( 'vcalendar', FALSE )) require_once '../iCalcreator.class.php'; // include iCalcreator
require_once 'iCalcnv/iCal2csv.php';
require_once 'iCalcnv/iCal2xls.php';
require_once 'iCalcnv/csv2iCal.php';
require_once 'iCalcnv/fileCheck.php';

// if( !class_exists( 'Log', FALSE )) include_once 'Log.php';                   // using PEAR Log class
if( !class_exists( 'eClog', FALSE )) include_once 'iCalcnv/eClog.class.php'; // or using eClog class
 /* if called from the web */

if( isset( $_REQUEST ) && isset( $_REQUEST['iCalfcn'] ) && isset( $_REQUEST['filename'] )) {
  $iCalcnv_VERSION = 'iCalcnv 2.0';
  $filename  =          $_REQUEST['filename'];
  $conf      = ( isset( $_REQUEST['conf'] ))         ? $_REQUEST['conf']                   : FALSE;
  $unique_id = ( isset( $_REQUEST['unique_id'] ))    ? $_REQUEST['unique_id']              : FALSE;
  $save      = ( isset( $_REQUEST['save'] ))         ? TRUE                                : FALSE;
  $dfname    = ( isset( $_REQUEST['diskfilename'] ) && $save ) ? $_REQUEST['diskfilename'] : FALSE;
  $test      = ( isset( $_REQUEST['test'] ))         ? $_REQUEST['test']                   : 3; // errors and major
  if( isset( $_REQUEST['logfile'] ) && ( FALSE !== ( $logfile = fileCheckWrite( $_REQUEST['logfile'], $l=FALSE, FALSE )))) {
    if( class_exists( 'Log', FALSE ) )       // using PEAR Log class, if included
      $eClog = &Log::singleton( 'file', $logfile, $iCalcnv_VERSION.' ('.$_REQUEST['iCalfcn'].')', array( 'timeFormat' => '%Y-%m-%d %T' ), $test );
    elseif( class_exists( 'eClog', FALSE )) // using eClog class, if included
      $eClog = new eClog( 'file', $logfile, $iCalcnv_VERSION.' ('.$_REQUEST['iCalfcn'].')', array( 'timeFormat' => '%Y-%m-%d %T' ), $test );
  }
  if( !isset( $eClog )) {
    class eClogDummy { // dummy log class
      function __construct( $type, $filename, $ident='', $conf=FALSE, $errorlevel=7 ) {}
      function log( $message, $priority = 7 ) {}
      function flush() {}
    }
    $eClog = new eClogDummy( 'file', 'logfile.log', $iCalcnv_VERSION.' ('.$_REQUEST['iCalfcn'].')', array( 'timeFormat' => '%Y-%m-%d %T' ), $test );
  }
  $eClog->log( 'input='.var_export( $_REQUEST, TRUE ), 7 );
  switch( $_REQUEST['iCalfcn'] ) {
    case 'iCal2csv':
      $myString = iCal2csv( $filename, $conf, $save, $dfname, $eClog );
      print $myString;
      break;
    case 'iCal2xls':
      iCal2xls( $filename, $conf, $save, $dfname, $eClog );
      break;
    case 'csv2iCal':
      csv2iCal( $filename, $conf, $unique_id, $save, $dfname, $eClog );
      break;
    default:
      $eClog->log( 'Illegal function call:'.$_REQUEST['iCalfcn'], 3 );
      break;
  }
  $eClog->flush();
}
?>