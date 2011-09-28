iCalcnv v2.0
copyright (c) 2009 Kjell-Inge Gustafsson, kigkonsult
www.kigkonsult.se
ical@kigkonsult.se


DESCRIPTION
===========

iCalcnv is a PHP utility package containing functions for converting iCal files
to xls and csv format and from csv format to iCal.

All functions support local and remote iCal/csv input files.

Default is to redirect output to browser with option to save file to disk.

The csv2iCal function makes it possible to create a time schedule or plan in a
spreadsheet, "save'as" a csv file, convert file to iCal format using the 
csv2iCal function and update a calendar application.

The iCal2csv function converts from iCal to csv file format.

The iCal2xls function converts iCal to spreadsheet xls file format and is using
PEAR Spreadsheet_Excel_Writer-0.9.1 and OLE-1.0.0RC1.

iCalcnv may use PEAR Log or eClog packages (included).

iCalcnv also includes two functions, 'fileCheckRead' and 'fileCheckWrite',
checking (local) files read- and writeability.

Definition of csv can be found at
"http://en.wikipedia.org/wiki/Comma-separated_values".

To get a proper understanding of iCal, explore the RFC2445, download from
"http://www.kigkonsult.se/downloads#rfc2445".

All functions are using iCalcreator class, to be downloaded from 
"http://www.kigkonsult.se/downloads/index.php#iCalcreator".


ICAL TO XLS/CSV
===============

Input file format to functions iCal2xls and iCal2csv is an iCal RFC2445 file 
(*.ics). Output file format are xls/csv formatted and are row oriented.
The xls format file is ISO-8859-1 encoded.

Using the 'conf' parameter supports
 map of iCal property names to user friendly column names,
 order output columns,
 iCal properties to skip from output.

First comes rows with product, date and file information, then opt. calendar
properties like METHOD, CALSCALE and X-PROPerties with name and value.

Next comes opt. timezone/standard/daylight components in row order, with a
leading header row, every component in a separate row, every property in a
separate column, starting with columns for component type and order.

The event components (vevent/vtodo/vfreebusy/vjournal and corresponding valarms)
are also presented in row order, with a leading header row, like the timezone
part above, components in rows and their properties in separate columns, also
starting with columns for component type and order.


XLS/CSV TO ICAL
===============

When converting from xls format to iCal, use spreadsheet application function
'save'as' a CSV file and select, if possible, field separator comma ',', field
delimiter double quote '"' and, if selectable and also depending on PHP
configuration and platform, 'UTF-8' as character set.

Input csv file format to function csv2iCal is actually output csv file format
above. Output file format is an iCal RFC2445 file (*.ics).

Using the 'conf' parameter supports map of user friendly column names to iCal
property names.

When parsing the csv file, empty rows or empty property values are skipped.

If exist, the product, date and file information are skipped. Any opt. rows
containing calendar properties like METHOD, CALSCALE and X-PROPerties updates
the calendar. (METHOD property as well as x-properties "X-WR-CALNAME",
"X-WR-CALDESC" and "X-WR-TIMEZONE" may be required later when importing iCal
files into some calendaring software (MS etc.).) This part may be missing in the
csv file when converting to iCal format.

The timezone part, if exist, MUST start with a leading header row. The header
row MUST have a "TYPE" column header in first column and the "TZID" property
header MUST exist in header row. Any standard/daylight components, if exist,
MUST appear directly after corresponding timezone component. This part may be
missing in the csv file.

The event part MUST start with a leading header row and MUST have a "TYPE"
column in the first column. Any alarms, if exist, MUST appear directly after
corresponding component. This part MUST exist in the csv file.

The content in the both header row (as well as for calendar properties) columns
are case and, except for "TYPE" (col 1), order independent, but MUST contain
strict RFC2445 property names. The values in the "TYPE" column MUST contain
strict RFC2445 component name: VTIMEZONE, STANDARD, DAYLIGHT, VEVENT, VTODO,
VJOURNAL, VFREEBUSY and VALARM.

The "ORDER" column in csv(/xls) output file is ignored when parsed, used only
for information. Opt. X-PROPerties in calendar, timezone, event or alarm
components are supported.

Each property contents are assumed to be in a strict RFC2445 format, ex. dates
(DTSTART, DTEND, DUE) may be prefixed by TZID or VALUE DATETIME/DATE parameters,
RELATED-TO by RELTYP parameter, RECURRENCE-ID by RANGE parameter, FREEBUSY by
FBTYPE parameter, COMMENT by LANGUAGE parameter etc.

Properties with multiple occurence within a component like ATTENDEE, COMMENT
etc. are assumed to be in one "field" (i.e. a cell, content within (default)
double quotes, field delimiters) separated by newline character(-s).


INSTALL
=======

Unpack to any directory within a webserver document root.

Download iCalcreator from 
"http://www.kigkonsult.se/downloads/index.php#iCalcreator", unzip and place 
iCalcreator.class.php in the "iCalcnv" directory (FILE LIST below), otherwise
change iCalcreator path in php require_once command in iCalcnv.php, If already
included somewhere else, comment the require command in "iCalcnv.php"

If not using PEAR Log, unzip eClog-1.0.zip and place in the "iCalcnv" directory.

Include "require_once '<path>/iCalcnv.php'" where appropriate, if used within
software.


CONFIGURATION AND USE
=====================

Update path in the PHP require command for iCalcreator class package in 
iCalcnv.php

iCal2xls uses PEAR Spreadsheet_Excel_Writer-0.9.1 (and OLE-1.0.0RC1) to be
installed as
"pear install channel://pear.php.net/OLE-1.0.0RC1"
"pear install channel://pear.php.net/Spreadsheet_Excel_Writer-0.9.1"
(may be ignored, if function is not used).

How to use the functions:

iCal2csv( filename, conf, save, diskfilename, log )
 string filename           url/file to convert (incl. opt. path)
 array  conf          opt, default FALSE(=array('"',',', '\n'),
                           csv field delimiter, field separator, newline char.
                            escape sequences will be expanded,
                            '\n' will be used as "\n" etc.
                           also map iCal property names to user friendly names,
                            ex. 'DTSTART' => 'startdate'
                           also order output columns, ex. 2 => 'DTSTART' 
                            (2=first order column, 3 next etc)
                           also properties to skip,
                            ex. 'skip' => array( 'CREATED', 'LAST-MODIFIED' );
 bool   save          opt, default FALSE(redirect to browser), TRUE=save to file
 string diskfilename  opt, filename (incl. path) for file to save
                           if missing, using filename + 'csv' extension
 object log           opt, default FALSE (=error_log), PEAR Log/eClog object
Returns FALSE if error occurs (check log fore details).


iCal2xls( filename, conf, save, diskfilename, log )
 string filename           url/file to convert (incl. opt. path)
 array  conf          opt, map iCal property names to user friendly names,
                            ex. 'DTSTART' => 'startdate'
                           also order output columns, ex. 2 => 'DTSTART' 
                            (2=first order column, 3 next etc)
                           also properties to skip,
                            ex. 'skip' => array( 'CREATED', 'LAST-MODIFIED' );
 bool   save          opt, default FALSE(redirect to browser), TRUE=save to file
 string diskfilename  opt, filename (incl. path) for file to save
                           if missing, using filename + 'xls' extension
 object log           opt, default FALSE (=error_log), PEAR Log/eClog object
Returns FALSE if error occurs (check logfile fore details).


csv2iCal( filename, conf, unique_id, save, diskfilename, log );
 string filename           url/file to convert (incl. opt. path)
 array  conf          opt, default FALSE(=array('"',',', '\n'),
                           csv field delimiter, field separator, newline char.
                           escape sequences will be expanded,
                           '\n' will be used as "\n" etc.
                           map user friendly names to iCal property names,
                            ex. 'DTSTART' => 'startdate'
 string unique_id     opt, used in iCalcreator then creating properties PRODID
                           (at calendar level ) and UID (component)
 bool   save          opt, default FALSE(redirect to browser), TRUE=save to file
 string diskfilename  opt, filename for file to save
                           if missing, using filename + 'ics' extension
 object log           opt, default FALSE (=error_log), PEAR Log/eClog object
Returns FALSE if error occurs (check logfile fore details).

Notice that the "conf" parameter to iCal2csv and csv2iCal function gives an
option to use non-default CSV character alternatives for field delimiter, field
separator and newline characters, default '"', ',', "\n".


TEST
====

Use the 'iCalcnv.php' script and test from the web, adapt the PHP
require commands.

When converting a file and redirect result to browser;
"http://<server>/<path>/iCalfcn.php?iCalfcn=<fcn>&filename=<filename>
where <fcn> = "iCal2csv" / "iCal2xls" / "csv2iCal"
and <filename> is an URL or a valid iCal or csv file (incl path).

When converting  a file and save (keep filename with new extension),
add "&save=1" to the url above.

When converting  a file and save to disk (with a new filename),
add "&save=1&diskfilename=<newFileName>" to the url above.


When converting a file and checking only opt. errors in file <log/log.log>
add "&logfile=<log/log.log>" to any of the url's above.
(If missing and an error occur, the standard logfile used in function
"error_log" is used)

When converting a file and information priority in file <log/log.log>
add "&test=6&logfile=<log/log.log" to any of the url's above.
(debug priority=7).

'tiCalFile' is creating an actual calendar file (using current date and time),
usable when testing calendar software etc, download from
"http://www.kigkonsult.se/downloads/index.php#tiCalFile" 


Please email opt. bugs, improvments etc to "ical@kigkonsult" asap!


FILE LIST
=========

calendars/                      calendars directory (test?)
eClog-1.0.zip                   eClog class package
iCalcnv/                        includes directory
iCalcnv/csv2iCal.php            csv2iCal function
iCalcnv/iCal2xls.php            iCal2xls function
iCalcnv/iCal2cnv.php            iCal2cnv function
iCalcnv/fileCheck.php           functions checking file read-/writeability
images/                         free iCal/xls/csv images to use
iCalcnv.php                     the script
LGPL.txt                        licence
README.txt                      this file


COPYRIGHT & LICENCE
===================

COPYRIGHT

iCalcnv v2.0
copyright (c) 2009 Kjell-Inge Gustafsson, kigkonsult
www.kigkonsult.se
ical@kigkonsult.se

LICENCE

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
