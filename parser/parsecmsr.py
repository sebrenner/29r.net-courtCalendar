#!/usr/bin/python
# encoding: utf-8

"""
parsecmsr.py

This code creates a cmsr class.  The class:
	1) parses the most recent cmsr file to csvs
	2) calls the php code for importing the csvs into mysql.

Created by Scott Brenner on Saturday, March 24, 2012 7:23:37 PM
.
Copyright (c) 2011 Scott Brenner. All rights reserved.

"""

# = Modules =
import urllib2
from cmsr1231Class import *  # this is the CMSR class
from file_functions import *  # these are my file management functions

print "\n\n\n\nThe CMSR file is being parsed.\n\n\n\n"
testDocket = CMSR1231Docket( verbose = True )
print "\n\n\n\nCalling http://29r.net/excutesql.php?cmsr.\n\n\n\n"

url = 'http://29r.net/up/parser/excutesql.php?cmsr=' + testDocket._CMSR1231Path2File
print "\n\n\n\nURI\n"
print url

urllib2.urlopen('http://29r.net/up/parser/excutesql.php?cmsr=' + testDocket._CMSR1231Path2File )
