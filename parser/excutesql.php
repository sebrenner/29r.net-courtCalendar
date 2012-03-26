<?php
/**
 * CMS calendar creator 
 * 29r.net/calendar.php
 * scott@scottbrenner.com
 *
 * This application queries my database of court date and returns an ical.
 */
$dateFile = '/home3/todayspo/public_html/29r/up/parser/logs/dates.txt';
$crimFile = '/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_crim.csv';
$civFile  = '/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_civil.csv';

if ( file_exists( $dateFile ) && file_exists( $crimFile ) && file_exists( $civFile ) ){
    $lines = file('/home3/todayspo/public_html/29r/up/parser/logs/dates.txt');
    $start = trim($lines[0]);
    $end = trim($lines[1]);
	echo "We got to here." . $start . $end . "\n";
    /*** connect to MySql database ***/
	// Get the sql password from an external file.
	require_once("/home3/todayspo/public_html/29r/_ignore_git/dbadmin_pswd.php");

    try 
    {
        $dsn = 'mysql:host=localhost;dbname=todayspo_courtCal2';
        $username = $dbuser;
        $password = $dbpassword;
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ); 

        $dbh = new PDO($dsn, $username, $password, $options);
        echo "\r\n*******************\r\ndbConnnection = True\r\n";
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
        echo "Beginning Transaction.";
		$dbh->beginTransaction();

		// To force the variables type to int.
		// http://stackoverflow.com/questions/6777154/pdo-mysql-syntax-error-1064
		//         echo "Binding parameters.";
		// $dbh->bindParam(1, (int)$limitvalue, PDO::PARAM_INT);
		// $dbh->bindParam(2, (int)$limit, PDO::PARAM_INT);

        // A set of queries; if one fails, an exception should be thrown   
        
		// Delete rows from relevant time period
		echo "Deleting Rows.";
		$sqlQuery = 'DELETE FROM nextActions WHERE NAC_date between "'. $start . '" and "' . $end . '";';
        $count = $dbh->exec( $sqlQuery );
		print("Deleted $count rows.\n");
		$sqlQuery = 'Optimize nextActions;';
		$result = $dbh->exec( $sqlQuery );	
		print( "Result of optimize $result.\n");
			
		// load civil NAC
        $sqlQuery = 'LOAD DATA LOCAL INFILE "/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_civil.csv" INTO 
                            TABLE nextActions 
                            FIELDS TERMINATED BY ","
                            ENCLOSED BY \'"\'
                            LINES TERMINATED BY "\r\n"
                            SET judgeId_fk = (SELECT judgeId FROM judges where CMSRName = judge );';
        $count = $dbh->exec( $sqlQuery );
		print("$count Civil NAC loaded rows.\n");
		
		// load criminal NAC		
        $sqlQuery = 'LOAD DATA LOCAL INFILE "/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_crim.csv" INTO 
                    TABLE nextActions 
                    FIELDS TERMINATED BY ","
                    ENCLOSED BY \'"\'
                    LINES TERMINATED BY "\r\n"
                    SET judgeId_fk = (SELECT judgeId FROM judges where CMSRName = judge );';        
        $count = $dbh->exec( $sqlQuery );
		print("$count Criminal NAC loaded rows.\n");
		
	
        // If we arrive here, it means that no exception was thrown
        // i.e. no query has failed ; and we can commit the transaction
	    $error = $dbh->errorInfo();
    	echo "The commit Statement returned {$result} with error message:";
		print_r ( $error );
		$result = $dbh->commit();
		echo "We just commit the changes.\n";
		
        echo "The SQL Statement executed successfully";
      	// unlink( '/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_civil.csv');
      	//       	unlink( '/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_crim.csv');
      	//         unlink( '/home3/todayspo/public_html/29r/up/parser/logs/dates.txt');
      	
		if(isset($_GET["cmsr"])){
			$cmsrFilePath =  $_GET["cmsr"];
			echo "$cmsrFilePath: " . $cmsrFilePath . "\n"; 
			$archiveCMSRName = '/home3/todayspo/public_html/29r/up/parser/archive/' . $start . "->" . $end . ".cmsr";
			echo "$archiveCMSRName: " . $archiveCMSRName . "\n"; 
			rename( $cmsrFilePath, $archiveCMSRName );
		}
    
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