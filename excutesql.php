<?php
/**
 * CMS calendar creator 
 * 29r.net/calendar.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and returns an ical.
 */
$dateFile = '/home3/todayspo/public_html/29r/logs/dates.txt';
$crimFile = '/home3/todayspo/public_html/29r/logs/TS_final_list_crim.csv';
$civFile  = '/home3/todayspo/public_html/29r/logs/TS_final_list_civil.csv';

if ( file_exists( $dateFile ) && file_exists( $crimFile ) && file_exists( $civFile ) ){
    $lines = file('/home3/todayspo/public_html/29r/logs/dates.txt');
    $start = trim($lines[0]);
    $end = trim($lines[1]);

    /*** connect to MySql database ***/
	// Get the sql password from an external file.
	require_once("_ignore_git/dbreader_pswd.php");

    try 
    {
        $dsn = 'mysql:host=localhost;dbname=todayspo_courtCal2';
        $username = $dbuser;
        $password = $dbpassword;
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ); 

        $dbh = new PDO($dsn, $username, $password, $options);
        echo "dbConnnection = True\r\n";
    }
    catch(PDOException $e)
    {

        echo $e->getMessage();
        echo "<br><br>Database -- NOT -- loaded successfully .. ";
        die( "<br><br>Query Closed !!! $error");
        echo "dbConnnection = False";
    
    }

    try {
        // First of all, let's begin a transaction
        $dbh->beginTransaction();
        // A set of queries; if one fails, an exception should be thrown   
        $sqlQuery = 'DELETE FROM nextActions WHERE NAC_date between "'. $start . '" and "' . $end . '";';
        $dbh->query( $sqlQuery );
        $sqlQuery = 'Optimize nextActions;' ;
        $dbh->query( $sqlQuery );
        $sqlQuery = 'LOAD DATA LOCAL INFILE "/home3/todayspo/public_html/29r/logs/TS_final_list_civil.csv" INTO 
                            TABLE nextActions 
                            FIELDS TERMINATED BY ","
                            ENCLOSED BY \'"\'
                            LINES TERMINATED BY "\r\n"
                            SET judgeId_fk = (SELECT judgeId FROM judges where CMSRName = judge );';
        $dbh->query( $sqlQuery );
        $sqlQuery = 'LOAD DATA LOCAL INFILE "/home3/todayspo/public_html/29r/logs/TS_final_list_crim.csv" INTO 
                    TABLE nextActions 
                    FIELDS TERMINATED BY ","
                    ENCLOSED BY \'"\'
                    LINES TERMINATED BY "\r\n"
                    SET judgeId_fk = (SELECT judgeId FROM judges where CMSRName = judge );';        
        $dbh->query( $sqlQuery );

        // If we arrive here, it means that no exception was thrown
        // i.e. no query has failed ; and we can commit the transaction
        $dbh->commit();
        echo "The SQL Statement executed successfully";
        // unlink('/home3/todayspo/public_html/29r/logs/TS_final_list_civil.csv');
        // unlink('/home3/todayspo/public_html/29r/logs/TS_final_list_crim.csv');
        // unlink('/home3/todayspo/public_html/29r/logs/dates.txt');
    
    
    } catch (Exception $e) {
        // An exception has been thrown
        // We must rollback the transaction
        $dbh->rollback();
        echo "dbTransactionSuccess = False";
    }
}
else
    echo "the required files are missing.";
?>