<?php
/**
 * eClog
 * ver 1.0
 *
 * Simple file log class emulating PEAR_LOG and using PEAR_LOG constants
 *
 * copyright (c) 2009 Kjell-Inge Gustafsson kigkonsult
 * www.kigkonsult.se/index.php
 * ical@kigkonsult.se
 * updated 20090314
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
 */
/** using PEAR Log priority levels */
if( !defined('PEAR_LOG_EMERG'))   define('PEAR_LOG_EMERG',    0);     // System is unusable
if( !defined('PEAR_LOG_ALERT'))   define('PEAR_LOG_ALERT',    1);     // Immediate action required
if( !defined('PEAR_LOG_CRIT'))    define('PEAR_LOG_CRIT',     2);     // Critical conditions
if( !defined('PEAR_LOG_ERR'))     define('PEAR_LOG_ERR',      3);     // Error conditions
if( !defined('PEAR_LOG_WARNING')) define('PEAR_LOG_WARNING',  4);     // Warning conditions
if( !defined('PEAR_LOG_NOTICE'))  define('PEAR_LOG_NOTICE',   5);     // Normal but significant
if( !defined('PEAR_LOG_INFO'))    define('PEAR_LOG_INFO',     6);     // Informational
if( !defined('PEAR_LOG_DEBUG'))   define('PEAR_LOG_DEBUG',    7);     // Debug-level messages
/**
 * usage:
 * 
 * require_once 'eClog.class.php';                                    // include eClog class (incl path)
 * .. .
 * $log = new eClog( 'file', '<filename>', '<ident>', FALSE, 5 );     // create eClog instance
 *                                                                    // parameters:
 *                                                                    // 'file' - fixed
 *                                                                    // log filename incl path
 *                                                                    // unique log item identifier
 *                                                                    // conf, default array( 'timeFormat' => '%Y-%m-%d %T' ), (strftime format)
 *                                                                    // priority level
 * .. .
 * $log->log( 'This is a debug message.', 7 );                        // log message
 * $log->log( 'This is an informational message.', PEAR_LOG_INFO );   // log message
 * $log->log( 'This is a significate message.', 5 );                  // log message
 * $log->log( 'This is a warning message.', PEAR_LOG_WARNING );       // log message
 * $log->log( 'This is an error message.', 3 );                       // log message
 * $log->log( 'This is a critical message.', PEAR_LOG_CRIT );         // log message
 * $log->log( 'This is an action required message.', 1 );             // log message
 * $log->log( 'This is a system unusable message.', PEAR_LOG_EMERG ); // log message
 * .. .
 * .. .
 * $log->flush();                                                     // write log to file (writes to file anyway at instance destruction)
 *
**/
class eClog {
  private $f;  // file name
  private $i;  // unique identifier
  private $c;  // configuration, default array( 'timeFormat' => '%Y-%m-%d %T' ), (strftime format)
  private $p;  // priority level
  private $pt; // priority to text array
  private $m;  // message array
  /** create log instance */
  function __construct( $type, $filename, $ident='', $conf=array(), $priority=7 ) {
    // $type emulates log type in PEAR log, here dummy parameter
    $this->f  = $filename;
    $this->i  = $ident;
    $this->c  = $conf;
    if( !isset( $this->c['timeFormat'] ) || ( FALSE == $this->c['timeFormat'] ))
      $this->c['timeFormat'] = '%Y-%m-%d %T';
    $this->m  = array();
    $this->p  = $priority;
    $this->pt = array( '[emerg]', '[alert]', '[crit]', '[err]', '[warning]', '[notice]', '[info]', '[debug]' );
    if( 6 <= $priority )
      $this->log( 'eClog 1.0 (kig/kigkonsult.se) START', 6 );
  }
  /** flush messages to file at destruction */
  function __destruct() {
    $this->flush();
  }
  /** inserts message into log array with opt. priority */
  function log( $message, $priority = 7 ) {
    if( $priority <= $this->p ) {
      $out = strftime( $this->c['timeFormat'] );
      if( !empty( $this->i ))
        $out .= ' '.$this->i;
      $this->m[] = $out.' '.str_pad( $this->pt[$priority], 8 ).' '.$message."\n";
    }
  }
  /** writes messages array to file, if called with message, write message to messages array first */
  function flush( $message=FALSE, $priority = 7 ) {
    if( $message )
      $this->log( $message, $priority );
    if( 0 < count( $this->m )) {
      file_put_contents( $this->f, $this->m, FILE_APPEND | LOCK_EX );
      $this->m = array();
    }
  }
}
?>