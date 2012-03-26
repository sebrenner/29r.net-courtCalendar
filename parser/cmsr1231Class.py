#!/usr/bin/env python
# encoding: utf-8

"""
cmsr1231Class.py

Created by Scott Brenner on 2011-08-25.
Copyright (c) 2011 Scott Brenner. All rights reserved.

Defines a class for parseing a cmsr1231 docket and accessing the docket data.

headers = ["freshness", "judge", "location" , "NAC_date", "NAC_time", "NAC", "case_num", "JMS", "AP_PO", "out_of_state_D", "caption", "counts", "counsel"]

timeFMT = "%Y-%m-%dT%H:%M"

"""

# = Modules =
import sys, os, datetime, time, csv, pdb, ftplib, pickle, urllib2
from file_functions import *        # these are my file management functions
from string import *
# from collections import Counter


class CMSR1231Docket:
    def __init__(self, CMSRFilePath = 0,  verbose = False):
        """
        Parses CMSR1231 flatfile and stores the result in two lists-
            self._civilList
            self._crimList
        
        Public methods for accessing the lists individually and as a full list.
        """
        # initialize variables
        self._verbose = verbose        
        opperationStartTime = datetime.datetime.now()
        if CMSRFilePath == 0:
            self._CMSR1231Path2File = self.__getLatestFile("/home3/todayspo/public_html/29r/up/server/php/files/")
        else:
            self._CMSR1231Path2File = CMSRFilePath
        if self._verbose:
            print "\n", "*" * 75, "\n\tNew CMSR1231Docket Object\n\tfrom %s." % self._CMSR1231Path2File, "\n", "*" * 75
        
        if self._verbose: print "Loading ", self._CMSR1231Path2File        
        self._lastDate, self._firstDate = "",""
        self._addedEvents = []
        self._droppedEvents = []
        self._unprocessedRows = []
        self._dbUpdateStatus = False
        
        # Parse the CMSR1231
        if self._verbose: print "Parseing ", self._CMSR1231Path2File
        self._myList = self.__parse_file_lines(self._CMSR1231Path2File)
		
        self._crimFileName = "crim_" + str( self._firstDate ) + "--" + str( self._lastDate ) + ".csv"
        self._civilFileName = "civil_" + str( self._firstDate ) + "--" + str( self._lastDate ) + ".csv"
        
        if self._verbose: print "The CMSRfile covers %s to %s." %( self._firstDate, self._lastDate )
        
        if self._verbose: print "Normalizing ", self._CMSR1231Path2File
        self._crimList, self._civilList = self.__normalize_split_crim_civil(self._myList)
        
        self._crimList = self.__final_pass_crim(self._crimList)
        self._civilList = self.__final_pass_civil(self._civilList)
        
        self._currentFullList = self._civilList + self._crimList
        opperationFinishTime = datetime.datetime.now()
        
        # Log and report on progress
        logString = "%s - %s was processed.  Began: %s Finished %s  Total time: %s.  %s Criminal events processed. %s Civil events processed. " %(  str(datetime.datetime.now())[:18], self._CMSR1231Path2File, str(opperationStartTime)[:10], str(opperationFinishTime)[:10], str(opperationFinishTime - opperationStartTime)[:10], len(self._crimList), len(self._civilList))
        
        self.__logFileProcessing( logString )
        if self._verbose: print "Processing CMSR1231 %s completed.  \nBegan: \t\t%s \nFinished: \t%s  \nTotal time: %s.\n%s Criminal events processed. %s Civil events processed. Total Events: %i" %( self._CMSR1231Path2File, str(opperationStartTime)[:20], str(opperationFinishTime)[:20], str(opperationFinishTime - opperationStartTime)[:10], len(self._crimList), len(self._civilList), len(self._crimList) + len(self._civilList) )

		# create the dates file
        dateList=[[self._firstDate],[self._lastDate]]
        self.__write_lists_csv( dateList, "/home3/todayspo/public_html/29r/up/parser/logs/dates.txt")

		# Upload files
        # localFile = "/home3/todayspo/public_html/29r/up/CSVs/" + self._crimFileName
        # 
        # self.upload( file = "/home3/todayspo/public_html/29r/up/parser/logs/" + "TS_final_list_civil.csv" )
        # self.upload( file = "/home3/todayspo/public_html/29r/up/parser/logs/" + "TS_final_list_crim.csv" )
        # self.upload( file = "/home3/todayspo/public_html/29r/up/parser/logs/" + "dates.txt" )
#        f = urllib2.urlopen('http://29r.net/')
 #       f = urllib2.urlopen('http://29r.net/excutesql.php')
  #      print f.read(1300)
    
    # ======================================================
    # = Function for parsing CMS docket                    =
    # ======================================================
    def __parse_file_lines(self, docket_file):
        """
        Iterate through a docket file, line by line, returns a list with lines as a list item.
        It also cleans out some of the cruft.
        Each NAC block and the header are separated by "++++++"
        Each NAC block is divided into three or four sub-block by "^^^^^^"
        The first sub-block is the time and NAC,
        The second is the case number
        The third/last is the either the JMS/lockup data or the cause,counts,attorneys, and PO.
        The fourth/last is the cause,counts,attorneys, and PO.
        """
        inFile = open(docket_file, 'r', 0)
        lines = inFile.readlines()  #create list each item is a line from file.
        clean_lines = ['++++++']
        
        self._firstDate, self._lastDate = self.__getReportTimeFrame( lines )        
                
        # ================
        # = Delimit blocks =
        # ================
        for each in range(len(lines)-1):
            # Clean up lines
            if lines[each] == "\r\n": lines[each] = "^^^^^^"
            if lines[each] == "^^^^^^" and lines[each-1] == "^^^^^^": continue
            if lines[each] == "^^^^^^" and lines[each-1] == "++++++": continue
            if lines[each] == "^^^^^^" and "_______" in lines[each+1]: continue
            if "_______" in lines[each]: lines[each] = "++++++"
            the_line = lines[each].strip()                              # strip
            the_line = ' '.join(the_line.split())                       # remove excess whitespace
            clean_lines.append(the_line)
        while '' in clean_lines:
            clean_lines.remove('')
        inFile.close()
        block_list =[]
        
        # ============================
        # = Add blocks to block list =
        # ============================
        for index, each in enumerate(clean_lines):
            if each == "++++++":
                my_block = []
                counter = 0
                while True:
                    counter += 1
                    if index + counter == len(clean_lines): break
                    if "++++++" in clean_lines[index + counter]: break
                    my_block.append(clean_lines[index + counter])
                block_list.append(my_block)
        
        # =====================================
        # = Gang up items separated by commas =
        # =====================================
        for index, each in enumerate(block_list):
            each = self.__gangAtComma( each )
                
        # ====================
        # = Normalize blocks =
        # ====================
        for index, each in enumerate(block_list):
            if len( block_list[index] ) < 1:  		# if block is empty, pop it and continue.
				# print block_list[index]
				block_list.pop(index)
				continue				
            if "^^^^^^" in each[0]: each.pop(0)		# remove first "^^^^^^ from first block.
            try:
                out_of_state = each.index('POSSIBLE OUT OF STATE RESIDENT')
            except ValueError:
                out_of_state = 0
            if out_of_state:
                each[out_of_state] = "^^^^^^"
                each.append('POSSIBLE OUT OF STATE RESIDENT')
            if "TODAYS DATE:" in each[0]:           # Test if this is a header block.
                # print index,": ", each[0], each[1], each[2], "\n\n"
                # pdb.set_trace()
                if "PAGE: e+0" in each[2]: each.pop(3)      # this will remove the extra line/item created by pages number > 999
                each.insert( 0, "Header" )
                continue
            if '.m.' in each[0]:                # Is there a time in the first item of the event/block
                each.insert(0,each[0][0:10])    # put time in index 0
                each[1] = each[1][10:]          # put NAC in index 1
            else:                               # inhereit time from previous NAC
                # print "***********\n\nThis event had no time so it inherited from the previous event.", each
                # print "\tThis is the time that preceded the prior item.", block_list [index - 1 ]
                each.insert(0,block_list[index-1][0])
                # print "\nNow this event looks like this:", each                
                
            first_two = each[1][0:2]
            three_four = each[1][3:4]
            
            if (first_two == "A " or first_two == "B " or first_two == "EX" or first_two == "M " or first_two == "SK") and three_four.isdigit():
                # if case number inherit NAC from previous each
                each.insert(1,block_list[index-1][1])
                each.insert(2,"^^^^^^")
                
        # save out a csv file for troubleshooting
        if self._verbose:
            print "Saving parsed block list."
            headers = ["freshness", "judge", "location" , "NAC_date", "NAC_time", "NAC", "case_num", "JMS", "AP_PO", "out_of_state_D", "caption", "counts", "counsel"]
            self.__write_lists_csv( block_list, "/home3/todayspo/public_html/29r/up/parser/logs/TS_parse_file_lines.csv" )
        
        return block_list
    
    def __normalize_split_crim_civil(self, block_list):
        """
        Take Block list.
        Normailize blocks, add judge, date, time, and freshness to each block.
        Return two lists of NAC--Civl, Criminal.
        """
        civil_block_list, criminal_block_list = [],[]
        NAC_pos = 2
        JMS_pos = 5
        
        for each in block_list:
            if each[0] == "Header":     # Get header info
                NAC_date =  each[6]
                judge = each[4][7:]
                location = each[-2][6:]
                date_index_pos = each[1].find(": ") + 2
                freshness =  each[1][date_index_pos:date_index_pos+10]
                freshness = self.__make_date(freshness, ' 7:00AM', '%m/%d/%Y %I:%M%p')
                if "TODAYS DATE" in each[1]:
                    pass
                else:
                    print "\n\n\nThis event item starts with Header.  Better take a look at __parse_file_lines.\n", each
                continue
            if "END OF REPORT" in each: # Skip the end of report block
                continue
                
            # combine date and time in standard date object format
            NAC_time = each[0]
            NAC_time = upper(NAC_time)
            NAC_time = replace(NAC_time, ".", "")
            NAC_time = replace(NAC_time, " ", "")
            each[0] = self.__make_date(NAC_date, NAC_time, '%B %d, %Y%I:%M%p')            
            # Gang up the NAC fields
            dilimited = each.count("^^^^^^")
            if dilimited > 0:
                len_of_NAC =  each.index('^^^^^^')        
            my_NAC = ' '.join(each[NAC_pos:len_of_NAC])
            my_NAC = my_NAC.strip(" ")              # strip
            for i in range(NAC_pos,len_of_NAC):
                each.pop(NAC_pos)
            each.insert(NAC_pos, my_NAC)
            
            each.pop(3)                 # remove first delimiter
            
            # Set item JMS_pos to JMSNumber
            if "JMS" in each[JMS_pos]:
                JMS = each[JMS_pos][5:]
                each[JMS_pos] = JMS
            else:
                each.insert(JMS_pos, " ")
                
            # remove second delimiter. If uses JMS_pos because it works.  Go figure.
            each.pop(4)
            if "^^^^^^" in each[JMS_pos]: each.pop(JMS_pos) 
            
            # Gang up the counts for criminal cases
            if each[3][0:1] == "B":            
                counts_counter = 1
                my_counts = ""
                while True:
                    if each[5 + counts_counter].find(":",0,4) and each[5 + counts_counter][:1].isdigit():
                        my_counts = my_counts + each[5 + counts_counter] + "\n"
                    else:
                        break
                    counts_counter += 1    
                    each[6] = my_counts
                if counts_counter > 2:
                    for i in range(7,5 + counts_counter):
                        each.pop(7)
            each.insert(0, location)
            each.insert(0, judge)
            each.insert(0, freshness)
            each.pop(5)
            # trim white space from strings, and replace single quotes
            for index, item in enumerate(each):
                if isinstance(item, str):
                    each[index] = item.replace(""" '""", """ - """)
                    each[index] = item.replace("\'", "")
                    each[index] = item.replace("'", "") 
                    each[index] = item.strip(" ")
            
        # Build criminal and civil block lists.  Exlude header block
        for each in block_list:
            if each[0] == "Header": continue     # Skip the header blocks
            if "END OF REPORT" in each: continue # Skip the end of report block
            if each[5][0:1] == "B":          # Criminal case
                criminal_block_list.append(each)
            else:                           # Civil case
                civil_block_list.append(each)
                
        # save out csv files for troubleshooting
        if self._verbose:
            print "Saving normalized list."
            headers = ["freshness", "judge", "location" , "NAC_date", "NAC_time", "NAC", "case_num", "JMS", "AP_PO", "out_of_state_D", "caption", "counts", "counsel"]
            self.__write_lists_csv( criminal_block_list, "/home3/todayspo/public_html/29r/up/parser/logs/TS_normalized_crim.csv" )
            self.__write_lists_csv( civil_block_list, "/home3/todayspo/public_html/29r/up/parser/logs/TS_normalized_civil.csv" )
        
        return criminal_block_list, civil_block_list
    
    def __final_pass_crim(self, crim_list):
        """
        Take list of Crim NAC and walkthough list items after counts to make sure all counts are ganged.
        Cases:
            Already ganged.  Proof: counts_index + 1 contains ":" at [5:]
            Not ganged.  Means counts_index + 1 belongs Counts AND next item is also count.
        Returns list of normalized NACs
        """
        # Cnum_dict = Counter()
        for row in crim_list:
            # Add coumns for AP_PO, and out of state warning
            row.insert(7,"")
            row.insert(7,"")
            
            # Skip header
            if row[0] == "freshness": continue
            
            # Skip rows that are already normalized remove last delimeter
            if len(row) < 14:
                if row[-1] == "^^^^^^":
                    row.pop()
                    
            # Gang up caption
            if ":" in row[10][0:3]:
                pass
            else:
                while True:
                    row[9] += row.pop(10)
                    try:
                        if ":" in row[10][1:3]: break
                    except IndexError:
                        print "Error in __final_pass_crim while trying to gang up the caption."
                        print "curent caption:", row[9]
                        print "Look at", row
                        print
                        
            # Move AP, if any to ap_Po column
            for index, item in enumerate(row):
                if isinstance(item, str):
                    if "AP-" in item:
                        row[7] = row.pop(index)
                    
            # Move out of state, if any to out of state column 
            for index, item in enumerate(row):
                if isinstance(item, str):
                    if "POSSIBLE OUT OF STATE RESIDENT" in item:
                        row[8] = row.pop(index)
                    
            # Gang up counsel
            for i in range(len(row)-1, 8, -1):
                if isinstance(item, str):
                    if ":" in row[i][:3]:continue
                    if row[i].find(":", 4) > 0:
                        for c in range(i+1, len(row)-1):
                            row[i] += row.pop(i+1)
                        
            # Remove last delimiter
            for index, m in enumerate(row):
                if isinstance(item, str):
                    if m == "^^^^^^": row.pop(index)
                
            # Gang up counts
            for l in range(10,len(row)-2) :
                row[10] += row.pop(10+1)
                
            # Split counsel
            row[-1] = row[-1].replace(""", """,'; ')
            row[-1],d_counsel = self.__split_counsel(row[-1])
            row.append(d_counsel)
            
            #reorder columns to match civil
            row.insert(6, row.pop(9))
            row.insert(7, row.pop(10))
            row.insert(8, row.pop(-2))
            row.insert(9, row.pop(-1))
            
            # split case cntrl from casenumber
            Cnum_cntrl = row[5].partition("CTLN:")
            row[5] = Cnum_cntrl[0]
            # Cnum_dict[row[5]] += 1
            row.append(Cnum_cntrl[2])
            
            # split AP & PO
            AP_PO = row[11].partition("PO:")
            row[11] = AP_PO[0]
            row.append(AP_PO[2])
            
            # trim white space and escape apostrphe's from strings
            for index, item in enumerate(row):
                if isinstance(item, str):
                    row[index] = item.replace("'", "\\'")
                    row[index] = item.replace("trim", "\\'")
                    row[index] = item.strip(" ")
        
        # save out csv file for troubleshooting
        if self._verbose:
            print "Saving final pass crim list."
            headers = ["freshness", "judge", "location" , "NAC_date", "NAC_time", "NAC", "case_num", "JMS", "AP_PO", "out_of_state_D", "caption", "counts", "counsel"]
            self.__write_lists_csv( crim_list, "/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_crim.csv" )
            fileName = "/home3/todayspo/public_html/29r/up/parser/CSVs/" + self._crimFileName
            self.__write_lists_csv( crim_list, fileName )
        
        return crim_list
    
    def __final_pass_civil(self, civil_list):
        """
        Take the civil list and finish normalizing the fields
        creates a list of unprocessedRow.
        """
        unprocessedRows = []
        for row in civil_list:
            if row[-1] == "^^^^^^":
                row.pop()
                
            # Find Cause of Action
            causes = ["ACCOUNTING- OC", "ACCOUNTING & JURY DEMAND- OC", "ADMIN APPEAL- APPEAL CIVIL SERVICE", "ADMIN APPEAL- APPEAL TAXES", "ADMIN APPEAL- APPEAL UNEMPLOYMENT", "ADMIN APPEAL- APPEAL UNEMPLOYMENT- TAXED IN COSTS", "ADMIN APPEAL- APPEAL ZONING", "ADMINISTRATIVE APPEAL", "ADMINISTRATIVE APPEAL- TAXED IN COSTS", "APPROPRIATION & JURY DEMAND- OC", "APPROPRIATION- OC", "APPROPRIATION- OC- TAXED IN COSTS", "BEYOND JURISDICTION- OC- TAXED IN COSTS", "BREACH OF CONTRACT & JURY DEMAND- OC", "BREACH OF CONTRACT- OC", "BREACH OF CONTRACT- OC- TAXED IN COSTS", "BWC- APPEAL", "BWC- APPEAL & JURY DEMAND", "BWC- APPEAL- TAXED IN COSTS", "BWC- NON-COMPLAINT EMPLOYER", "CANCEL LAND CONTRACT- OC", "CHANGE OF VENUE- OC- TAXED IN COSTS", "CLASS ACTION & JURY DEMAND- OC", "CLASS ACTION- OC", "COGNOVIT- OC", "COGNOVIT- 0C- TAXED IN COST", "COMPLEX LITIGATION", "COMPLEX LITIGATION & JURY DEMAND", "CONSUMER SALES ACT & JURY DEMAND- OC", "CONSUMER SALES ACT- OC", "CONSUMER SALES ACT- OC- TAXED IN COSTS", "CONVEY DECLARED VOID", "DECLARATORY JUDGMENT & JURY DEMAND- OC", "DECLARATORY JUDGMENT- OC", "DECLARATORY JUDGMENT- OC- TAXED IN COSTS", "DISCHARGE MECH. LIEN- OC", "DISSOLVE PARTNERSHIP- OC", "ENVIRONMENT- OC", "ENVIRONMENT- OC- TAXED IN COSTS", "EXECUTION FILING", "EXECUTION FILING - TAXED IN COST", "FORECLOSURE", "FORECLOSURE - MECH'S LIEN", "FORECLOSURE - MECH'S LIEN - TAXED IN COSTS", "FORECLOSURE - TAXES", "FORECLOSURE AND JURY DEMAND", "FORECLOSURE- TAX CERTIFICATE", "FORECLOSURE- TAXED IN COSTS", "HABEAS CORPUS- OC- TAXED IN COSTS", "INJUNCTION & JURY DEMAND- OC", "INJUNCTION SEXUAL PREDATOR- OC - TAXED IN COSTS", "INJUNCTION- OC", "INJUNCTION- OC- TAXED IN COSTS", "MANDAMUS- OC","MENACING BY STALKING -OC", "MISCELLANEOUS FORFEITURE", "ON ACCOUNT & JURY DEMAND- OC", "ON ACCOUNT- OC", "ON ACCOUNT- OC- TAXED IN COSTS", "OTHER CIVIL", "OTHER CIVIL - TAXED IN COSTS", "OTHER CIVIL & JURY DEMAND", "OTHER CIVIL & JURY DEMAND- POV AFF", "OTHER TORT", "OTHER TORT & JURY DEMAND", "OTHER TORT- PERSONAL INJURY", "OTHER TORT- PERSONAL INJURY & JURY DEMAND", "OTHER TORT- PERSONAL INJURY & JURY DEMAND- POV AFF", "OTHER TORT- PERSONAL INJURY- TAXED IN COSTS", "OTHER TORT- TAXED IN COSTS", "OTHER TORT- VEHICLE ACCIDENT", "OTHER TORT- VEHICLE ACCIDENT & JURY DEMAND", "OTHER TORT- VEHICLE ACCIDENT & JURY DEMAND-POV AFF", "OTHER TORT- VEHICLE ACCIDENT- TAXED IN COSTS", "OTHER TORT- WRONGFUL DEATH", "OTHER TORT- WRONGFUL DEATH & JURY DEMAND", "OTHER TORT- WRONGFUL DEATH- TAXED IN COSTS", "PARTITION- OC", "PARTITION- OC- TAXED IN COSTS", "PROD LIABL- PERSONAL INJURY", "PROD LIABL- PERSONAL INJURY AND JURY DEMAND", "PROD LIABL- WRONGFUL DEATH", "PROD LIABL- WRONGFUL DEATH AND JURY DEMAND", "PROD LIABL- WRONGFUL DEATH- TAXED IN COSTS", "PRODUCT LIABILITY", "PRODUCT LIABILITY AND JURY DEMAND", "PROF TORT- LEGAL MALPRACTICE", "PROF TORT- LEGAL MALPRACTICE AND JURY DEMAND", "PROF TORT- LEGAL MALPRACTICE- TAXED IN COSTS", "PROF TORT- MEDICAL MALPRACTICE", "PROF TORT- MEDICAL MALPRACTICE & JURY DEMAND", "PROF TORT- MEDICAL MALPRACTICE & JURY DEMAND- P.A.", "PROF TORT- MEDICAL MALPRACTICE- TAXED IN COSTS", "PROF TORT- PERSONAL INJURY", "PROF TORT- PERSONAL INJURY AND JURY DEMAND", "PROF TORT- PERSONAL INJURY AND JURY DEMAND-POV AFF", "PROF TORT- PERSONAL INJURY- TAXED IN COSTS", "PROF TORT- WRONGFUL DEATH", "PROF TORT- WRONGFUL DEATH AND JURY DEMAND", "PROF TORT- WRONGFUL DEATH AND JURY DEMAND- POV AFF", "PROFESSIONAL TORT", "PROFESSIONAL TORT AND JURY DEMAND- PROF TORT", "QUIET TITLE- OC", "REPLEVIN- OC", "REPLEVIN- OC- TAXED IN COSTS", "RESTRAINING ORDER & JURY DEMAND- OC", "RESTRAINING ORDER- OC", "SALE OF REAL ESTATE- OC", "SB10 RE-CLASSIFICATION", "SPECIFIC PERFORMANCE- OC", "TESTIMONY- OC"]
                            
            # use first 12 ch of cause to match cause of action
            # cause_dict = Counter()
            causes_standard = []
            for each in causes:
                causes_standard.append(each[:12])
            
            # find positon of cause of action.  Assumes cause of action is alwasy one item long
            cause_index_pos = 0
            for i in range(3,len(row)):
                if isinstance(row[i], str):
                    if row[i][:12] in causes_standard:
                        cause_index_pos = i
            if cause_index_pos == 0:
                print "This row doesn't have recognized cause of action:\n\t", row
                if row[4][:2] == "SP":
                    row.insert(4, "")
                    row.insert(7, "SB10 RE-CLASSIFICATION")
                    cause_index_pos = 7
                self._unprocessedRows.insert( -1, row )
                continue
                
            # Gang up Counsel.  If the cause is at index 8, and the row is 10 long then
            # counsel is only one item long and there is no need to gang.  INDEX STARTS AT ZERO.
            
            while ( len(row) - cause_index_pos ) > 2:
                stem = len(row) - 2
                row[ stem ] = row[ stem ] + " " + row.pop( -1 )
                
            # Gang up caption.  If the cause is at index 8, then the
            # caption is only one item long and there is no need to gang.
            while cause_index_pos > 8:
                row[ cause_index_pos - 2 ] = row[ cause_index_pos - 2 ] + " " + row.pop( cause_index_pos - 1 )
                cause_index_pos -= 1
            try:
                if row[6] == "":
                    row.pop(6)
            except Exception, e:
                print e
                continue
            
            # Split counsel
            try:
                row[8] = row[8].replace(""", """,'; ')          # delimit counsel with ;
                row[8],d_counsel = self.__split_counsel(row[8])
                row.append(d_counsel)
            except IndexError:
                row.append("No Counsel")
                row.append("No Counsel")
            
            # trim white space from strings
            for index, item in enumerate(row):
                if isinstance(item, str):
                    row[index] = item.strip(" ")
        
        # save out csv file for troubleshooting
        if self._verbose:
            print "Saving final pass civil list."
            headers = ["freshness", "judge", "location" , "NAC_date", "NAC_time", "NAC", "case_num", "JMS", "AP_PO", "out_of_state_D", "caption", "counts", "counsel"]
            self.__write_lists_csv( civil_list, "/home3/todayspo/public_html/29r/up/parser/logs/TS_final_list_civil.csv" )
            fileName = "/home3/todayspo/public_html/29r/up/parser/CSVs/" + self._civilFileName
            self.__write_lists_csv( civil_list, fileName )
        return civil_list
    
    # ====================================
    # = Functions for creating NAC data  =
    # ====================================
    def __make_date(self, the_date,the_time,format):
        """
        Takes date string, returns date object/string
        """
        # print "date,time,format",the_date,the_time,format
        
        try:
            return datetime.datetime.strptime(the_date + the_time, format)
        except ValueError:
            # print "__make_date failed.\n ",the_date,the_time,format
            # print
            return "tacotaco"
    
    def __split_counsel(self, counsel_block):
        """
        Takes counsel block and splits Plaintiff and Defense counsel at the :.
        Delimit counsel by \n
        """
        counsel_tuple = counsel_block.partition(":")
        # print counsel_tuple
        return counsel_tuple[0], counsel_tuple[2]
    
    # ========================================
    # = Functions for accessing docket data  =
    # ========================================
    def getCrimList(self):
        return self._crimList
    
    def getCivilList(self):
        return self._civilList
    
    def getAddedEvents(self):
        return self._addedEvents
    
    def getDroppedEvents(self):
        return self._droppedEvents
    
    def getFullList(self):
        return self._currentFullList
    
    def getPeriod(self):
        return self._firstDate, self._lastDate
    
    # =========================================
    # = Functions for manipulating the lists  =
    # =========================================
    def __saveFullList( self, savefileName ):
        """
        Saves the full list to a pickle file.
        Returns true if successful.
        """
        try:
            pickle.dump( self._currentFullList, open( savefileName, "wb" ) )
        except Exception, e:
            print "Failed to save pickle file in CMSR1231 object"
            raise e
            return False
            
        return True
    
    def loadFullList( self ):
        """
        Load the full list from a pickle file.
        Returns the full list.
        """
        return pickle.load( open( self._lastCMSR1231Path2Pickle ) )
    
    def __logFileProcessing( self, logString):
        """
        Append a line to a log file.
        """
        try:
            with open("/home3/todayspo/public_html/29r/up/parser/logs/CMSR1231-log.txt", "a") as f:
                f.write( logString + "\n" )
        except Exception, e:
            print "Failed to save log file in CMSR1231 object"
            print e
            raise e            
            return False
            
        return True
    
    def __getReportTimeFrame( self, rawReportList ):
        """
        Takes the file as a list of lines.
        Returns the start date and last date.
        """
        startDate = ""
        lastDate = ""
        for index, each in enumerate( rawReportList ):
            if "REPORT FROM" in rawReportList[ index ]:
                startDate = rawReportList[ index ][13:23]
                lastDate = rawReportList[ (index + 1) ][13:23]
            if (startDate != "" and lastDate != ""): 
                startDate = self.__makeDateSortable(startDate)
                lastDate = self.__makeDateSortable(lastDate)
                return startDate, lastDate
    
    def __createDropAddLists( self ):
        """
        Creates two lists: the events to be dropped, the events to be added.
        
        Using the start date and the finsih date, filter the last list and 
        the current list to cover the same time frame, i.e., the latest start date
        and the earliest finish date.
        
        Create a list of the items that are in the last list but not in the current list--
        these are the items to be dropped.
        
        Create a list of the items that are in the current list but not in the last list--
        these are the items to be added.
        
        """        
        #load lastlist
        self._lastCMSR1231Path2Pickle = "log/lastCMSR1231List.pkle"
        try:    
           self._lastFullList = self.loadFullList( )
           if self._verbose: print "%s loaded; %i events in pickled file.  Filtering last list and current list to event within current date range." %( self._lastCMSR1231Path2Pickle, len( self._lastFullList ) )
           
        except IOError as e:
           print e ,'Oh dear, no lastFullList.'
           self._lastFullList = []
            
        # filter list to only cover the date range
        normalLastList = [event for event in self._lastFullList if (event[2] >= self._firstDate and event[2] <= self._lastDate )]
        normalCurrentList = [event for event in self._currentFullList if (event[2] >= self._firstDate and event[2] <= self._lastDate )]
        
        # Find differences in covered date range
        if self._verbose: print "Finding events to add."
        self._addedEvents = filter(lambda x:x not in self._lastFullList, self._currentFullList)
        if self._verbose: print "Finding events to drop."
        self._droppedEvents = filter(lambda x:x not in self._currentFullList, self._lastFullList)
        print "Found %i new events.  Found %i dropped events." %(len(self._addedEvents), len(self._droppedEvents))
    
    def __makeDateSortable( self, date ):
        """
        Takes a date string, e.g., 9/29/2011 and converts it to a sortable string 2011-09-29.
        """
        # print "in make date sortable"
        if "/" in date:
            # convert to list
            # print date
            dateList = date.split("/")
            year = dateList[2]
            month = dateList[0].rjust(2, "0")
            day = dateList[1].rjust(2, "0")
            return year + "-" + month + "-" + day
        print "the date passed %s, is not a date." % date
    
    def __getLatestFile( self, passedDirctory ):
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
    
    def __write_lists_csv( self, block_list, file_name ):
        """
        Takes a list of blocks (each block is a list of items), a files location//name, and a list of headers
        Writes the blocks as rows in a CSV file.  Each item of the blocks is a comma-separated value.
        Returns the location of the CSV file.
        """
        fileWriter = csv.writer(open(file_name, 'wb'), delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        # fileWriter.writerow(headers)
        for each in block_list:
            fileWriter.writerow(each)
    
    def setdbUpdateStatus(self, status):
        """"
        Sets db update status so the object knows whther to 
        save the pickle file.
        """
        self._dbUpdateStatus = status
    
    def __del__(self):
        """
        This function is called when the object is deleted.  The full list is only save when the object is destroyed.  This ensures that the full list is only saved after the db object has successfully updated the db.
        Save new list
        """
        if self._dbUpdateStatus:
            print "\n","*" * 75, "\n\tDb was successfully updated.\n\tSaving pickle file.", "\n", "*" * 75
            self.__saveFullList( self._lastCMSR1231Path2Pickle )
        else:
            print "\n","*" * 75, "\n\tDb was not successfully updated.\n\t%s will not be pickled." %self._CMSR1231Path2File, "\n","*" * 75
    
    def __gangAtComma( self, myList ):
        """
        Takes a list and combines items that are separated by a comma at the end of the first item.
        Teturns a list.
        
        E.g., ["taco,", "stand", "McDonalds", "Wendys", "Burger,", "King", "The,", "Hamburger,", "Stand", "Starbucks"]
        returns:
        ["taco, stand", "McDonalds", "Wendys", "Burger, King", "The, Hamburger, Stand", "Starbucks"]
        """
        listLength = len( myList )
        for i in xrange( listLength - 1 , -1, -1):
            # print "\n", i, myList[i], 
            try:
                # pdb.set_trace()
                if myList[i][-1] == ",":
                    # print "\n\n%s ends with ','" %myList[i]
                    # print myList 
                    if "^" in myList[ i + 1 ]:
                        myList[i] = myList[i][:-1]
                    else:
                        myList[i] = myList[i] + " " + myList.pop( i + 1 )
                    # print myList
            except Exception:
                print "Couldn't gang comma'd item:", Exception
                continue
        # print myList
        return myList
    
    def upload( self, file ):
        site = "ftp.todayspodcast.com"
        dir = "public_html/29r/"
        user = ( 'todayspo', 'onal44Resp!' )
        verbose = True
        if verbose: print 'Uploading', file
        local = open(file, 'rb')
        remote = ftplib.FTP(site)
        remote.login(*user)
        remote.cwd(dir)
        remote.storbinary('STOR ' + file, local, 1024)
        remote.quit()
        local.close()
        if verbose: print 'Upload done.'

	def __getCMSRFileName( self ):
		return self._CMSR1231Path2File
    

if __name__ == '__main__':
    """
    Testing the class
    """
    testDocket = CMSR1231Docket( verbose = True )
       
    # print "\nThese events were added:"
    # for each in testDocket.getAddedEvents():
    #     print each[3], each[4], each[5]
    # print "\nThese events were dropped:" 
    # for each in testDocket.getDroppedEvents():
    #     print each[3], each[4], each[5]
    # print "\nThese events were saved in the lastListFile:"
    #     # print each[3], each[4], each[5]