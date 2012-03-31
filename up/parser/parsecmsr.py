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
from file_functions import *  # these are my file management functions

printHeader()

testDocket = CMSR1231Docket( verbose = True )

printFooter()

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


def printHeader(){
	print "<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">"
	print " <html lang=\"en"> "
	print "<head>"
	print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">"
	print "<title>CMSR1231 Parser</title>"
	print "<meta name=\"author\" content=\"Scott Brenner\">"
	
}

def printooter(){
	"""
	Prints html footer code
	"""
	print "</body>"
	print "</html>"
}