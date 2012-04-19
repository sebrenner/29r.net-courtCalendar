#!/usr/bin/python

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
    
    # =====================
    # = Connect to the db =
    # =====================
    con = None
    print "Connecting to db... " ,
    try:
        con = mdb.connect('localhost', 'todayspo_calAdm',  'Gmu1vltrkLOX4n', 'todayspo_courtCal2')
    except mdb.Error, e:
        print "Error %d: %s" % (e.args[0], e.args[1])
        sys.exit(1)
    print "\t\t\t\t\t\t\tSuccess (connected to dB)."
    
    # ================================================
    # = Execute the sql atomically, rollback on fail =
    # ================================================
    cursor = con.cursor()
    try:
        try:
            # Delete the rows from the relevant period
            print "Deleting records..." ,
            sqlQueryDELETE = "  DELETE FROM nextActions \
                                WHERE NAC_date between \'%s\' and \'%s\'" \
                                %( startDate, endDate )
            
            result = cursor.execute( sqlQueryDELETE )
            print "\t\t\t\t\t\t\tSuccess (%i records dropped)." %result
            
            print "Optimizing nextActions...",
            result = cursor.execute( "OPTIMIZE TABLE nextActions;" )
            print "\t\t\t\t\t\tSuccess (%i records optimized)." %result
            
            # Load each CSV
            for each in CSVs:
                print "Loading %s..." % each, 
                sqlQueryLOADCSV = "LOAD DATA LOCAL INFILE \"%s\" INTO \
                                TABLE nextActions \
                                FIELDS TERMINATED BY \",\" \
                                ENCLOSED BY \'\"\'   \
                                LINES TERMINATED BY \"\\r\\n\" \
                                SET judgeId_fk = (SELECT judgeId FROM judges \
                                where CMSRName = judge );" %( each )
                result = cursor.execute( sqlQueryLOADCSV )
                print "\t\t\tSuccess (%i records imported)" % result
        except:
            print "\n\t\t\tFAILURE.  Records rolled back: ", con.rollback()
            raise
        else:
            # print "Success. Transaction committed: ", 
            con.commit()
    finally:
        print "Committing Transaction...",
        con.commit()
        print "\t\t\t\t\t\tSuccess (Transaction committed).\nClosing cursor...",
        cursor.close() 
        print "\t\t\t\t\t\t\tSuccess (cursor closed)."

def printHeader():
    print "Content-type: text/html"
    print
    print "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">"
    print "<html lang=\"en\">"
    print "<head>"
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">"
    print "<title>CMSR1231 Parser</title>"
    print "<meta name=\"author\" content=\"Scott Brenner\">"
    print "</head><body>"

def printFooter():
    """
    Prints html footer code
    """
    print "</body>"
    print "</html>"

def getLastestFile( passedDirectory ="../server/php/files/", verbose = False ):
    """
    Gets the file path to the latest .pxx file from
    the passed diretory, by default ../server/php/files/
    
    ~/public_html/29r.git/up/server/php/files/
    """
    try:
        # get files from the passedDirectory
        filelist = os.listdir( passedDirectory )
        # print filelist
        
        # filter out directories
        filelist = filter(lambda x: not os.path.isdir(x), filelist)
        # print filelist
        
        # add the path to the CMSRfiles name
        CMSRfiles = []
        for index, item in enumerate(filelist):
            # Only consider files that start wirh "cmsr1231"
            if item[:8] == "cmsr1231":
                # print "File name starts with cmsr1231."
                CMSRfiles.append( passedDirectory + item) 
        # print CMSRfiles
        
        mostRecent = max(CMSRfiles, key=lambda x: os.stat(x).st_mtime)
        if verbose:
            print "The last modified file is: %s" % mostRecent
        return mostRecent
    except Exception, e:
        print "\n\t\tERROR There are no CMSR1231 files in the %s directory." % passedDirectory
        return -1
        # raise e


printHeader()
print "<pre>"
cmsrPath = -1
cmsrPath = getLastestFile()

if ( cmsrPath != -1  ):
    testDocket = CMSR1231Docket( cmsrPath,  verbose = False )
    print "Confirming %s was parsed..." %cmsrPath,
    if testDocket.isSuccessful():
        print "\t\tSuccess (file parsed)."
        dates = testDocket.getPeriod()
        importCSV( [ testDocket.getCrimFilePath(), testDocket.getCivFilePath() ], dates[0], dates[1] )
    else:
        print "\n\t\t\t\tFAILURE-  No changes to the database were made."
print "</pre>"
printFooter()
