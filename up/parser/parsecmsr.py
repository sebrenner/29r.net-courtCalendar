#!/usr/bin/python
# encoding: utf-8

"""
parsecmsr.py

This code passes a cmsr1231.pxx file path to the CMSR231 class.
The class parse the .pxx file and creates to csv viles.
This code then executes sql statements importing the csv files into the Mysql db.
Then the csv files are moved to an archive and deletes the oldest file in the archive using the file name to determine age.

Created by Scott Brenner on Saturday, March 24, 2012 7:23:37 PM
.
Copyright (c) 2012 Scott Brenner. All rights reserved.

"""

# = Modules =
from cmsr1231Class import *  # this is the CMSR class
import sys
import MySQLdb as mdb

def importCSV( CSVs, startDate, endDate):
    """
    Takes CSV path and date range.
    In a single transaction, all the matching records in the date range
    """
    
    # ====================
    # = Connec to the db =
    # ====================
    con = None
    try:
        con = mdb.connect('localhost', 'todayspo_calAdm',  'Gmu1vltrkLOX4n', 'todayspo_courtCal2')        
    except mdb.Error, e:
        print "Error %d: %s" % (e.args[0], e.args[1])
        sys.exit(1)
    
    # ================================================
    # = Execute the sql atomically, rollback on fail =
    # ================================================
    cursor = con.cursor()
    try:
        try:
            # Delete the rows from the relevant period
            sqlQueryDELETE = "  DELETE FROM nextActions \
                                WHERE NAC_date between \'%s\' and \'%s\'" \
                                %( startDate, endDate )
            print sqlQueryDELETE ,"\n\n"
            cursor.execute( sqlQueryDELETE )
            
            # Load each CSV
            for each in CSVs:
                sqlQueryLOADCSV = "LOAD DATA LOCAL INFILE \"%s\" INTO \
                                TABLE nextActions \
                                FIELDS TERMINATED BY \",\" \
                                ENCLOSED BY \'\"\'   \
                                LINES TERMINATED BY \"\\r\\n\";" %( each )
                print "\n", sqlQueryLOADCSV
                cursor.execute( sqlQueryLOADCSV )
                cursor.execute( 'Optimize nextActions;' ) 
            con.rollback()
        except:
            con.rollback()
            raise
        else:
            con.commit()
    finally:
        cursor.close()

def getLatestFile( self, passedDirctory ):
    try:
        # get files from the self._passedDirctory
        filelist = os.listdir( passedDirctory )
        
        # filtor out directories
        filelist = filter(lambda x: not os.path.isdir(x), filelist)
        
        # add the path to the CMSRfiles name
        CMSRfiles = []
        for index, item in enumerate(filelist):
            # Only consider files that start wirh "cmsr1231"
            if item[:8] == "cmsr1231":
                CMSRfiles.append( passedDirctory + item) 
                
        mostRecent = max(CMSRfiles, key=lambda x: os.stat(x).st_mtime)
        if self._verbose:
            print"The last modified file is: %s" % mostRecent
        return mostRecent
    except Exception, e:
        print "There are no CMSR1231 files in the %s directory." % passedDirctory
        raise e

def printHeader():
    print "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">"
    print "<html lang=\"en\">"
    print "<head>"
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">"
    print "<title>CMSR1231 Parser</title>"
    print "<meta name=\"author\" content=\"Scott Brenner\">"

def printFooter():
    """
    Prints html footer code
    """
    print "</body>"
    print "</html>"

def isCriminal( filePath ):
    """
    Predicate function returns true if the file path is for a crim case CSV.
    """
    if "crim" in filePath:
        return True
    else:
        return False


printHeader()

testDocket = CMSR1231Docket( "testFiles/cmsr1231.P53",  verbose = True )
dates = testDocket.getPeriod()
importCSV( [ testDocket.getCrimFilePath(), testDocket.getCivFilePath() ], dates[0], dates[1] )

printFooter()
