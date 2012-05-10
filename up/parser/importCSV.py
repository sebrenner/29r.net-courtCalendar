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
            print "Bad query: " , sqlQueryLOADCSV
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


printHeader()
print "<pre>"
# importCSV( [ "CSVs/2009-12-01--2010-01-01_civil.csv", "CSVs/2009-12-01--2010-01-01_crim.csv" ], "2009-12-01", "2010-01-01" )
importCSV( [ "CSVs/2009-12-01--2010-01-01_crim.csv" ], "2009-12-01", "2010-01-01" )
printFooter()
