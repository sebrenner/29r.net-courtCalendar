#!/usr/bin/env python
# encoding: utf-8

"""
cmsr1231Class.py

Created by Scott Brenner on 2011-08-25.
Copyright (c) 2011 Scott Brenner. All rights reserved.

Defines a class for parseing a cmsr1231 docket and creatign two CSVs files-one of civil settings, one of criminal settings.

headers = ["freshness", "judge", "location" , "NAC_date", "NAC_time", "NAC", "case_num", "JMS", "AP_PO", "out_of_state_D", "caption", "counts", "counsel"]

timeFMT = "%Y-%m-%dT%H:%M"

"""

# = Modules =
import sys, os, datetime, time, csv, pdb
from string import *

class CMSR1231Docket:
    def __init__(self, CMSRFilePath,  verbose = False):
        """
        """
        # initialize variables
        self._dateListFilePath ="logs/dates.csv"
        self._verbose = verbose
        self._freshness, self._lastDate, self._firstDate = "","",""
        self._addedEvents = []
        self._droppedEvents = []
        self._unprocessedRows = []
        self._judges = set()
        self._dbUpdateStatus = False        
        self._CMSR1231Path2File = CMSRFilePath
        opperationStartTime = datetime.datetime.now()   # For measuring performance
        if self._verbose:
            print "\n", "*" * 75, "\n\tNew CMSR1231Docket Object\n\tfrom %s." % self._CMSR1231Path2File, "\n", "*" * 75
            print "Loading ", self._CMSR1231Path2File
        
        # =======================
        # = Parse the CMSR1231  =
        # =======================
        if self._verbose: print "Parseing ", self._CMSR1231Path2File
        self._myList = self.__parse_file_lines(self._CMSR1231Path2File) # This function also gets freshness, stat and end dates
        # Create filenames based on time frames.
        self._crimFileName = "crim_" + str( self._firstDate ) + "--" + str( self._lastDate ) + ".csv"
        self._civilFileName = "civil_" + str( self._firstDate ) + "--" + str( self._lastDate ) + ".csv"
        if self._verbose:
            print "The CMSRfile was created on %s. It covers %s to %s." %( self._freshness, self._firstDate, self._lastDate )
        
        if self._verbose: print "Normalizing and spitting", self._CMSR1231Path2File
        self._crimList, self._civilList = self.__normalize_split_crim_civil(self._myList)   # This function gets the judges
            
        if ( self.__isFresher() and self.__isAllJudges() ):
            if self._verbose: 
                print "The passed file is fresher and contains all judges."
                print "Executing final passes on criminal and civil lists."
            self._crimList = self.__final_pass_crim(self._crimList)
            self._civilList = self.__final_pass_civil(self._civilList)
        else:
            print "The file: ", self._CMSR1231Path2File , " could not be imported. It is either not fresh enough or it does not contain schedules for all the judges."
            return -1
        
        opperationFinishTime = datetime.datetime.now() # For measuring performance
        
        # =================
        # = Log progress  =
        # =================
        logString = "%s - %s was processed.  Processing time: %s.  %s Criminal events processed. %s Civil events processed. Freshness: %s" %(  str( datetime.datetime.now())[:18], self._CMSR1231Path2File, str( opperationFinishTime - opperationStartTime )[:10], len( self._crimList ), len( self._civilList ), self._freshness )
        self.__logFileProcessing( logString )
        if self._verbose: print logString
        
    def __del__(self):
        """
        This function is called when the object is deleted.
        """
        # Log the dates in the dates file
        if self._verbose: print "\nSaving dates to file:", self._dateListFilePath,
        dateList = [ [ self._firstDate ],[ self._lastDate ], [self._freshness] ]
        self.__write_lists_csv( dateList, self._dateListFilePath )
        if self._verbose: print "Date range and freshness successfully saved."
    
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
        
        self._freshness, self._firstDate, self._lastDate = self.__getReportTimeFrame( lines )        
                
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
            self.__write_lists_csv( block_list, "logs/TS_parse_file_lines.csv" )
        
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
                self._judges.add( judge )   # Add judge to set of judges.
                location = each[-2][6:]
                date_index_pos = each[1].find(": ") + 2
                freshness =  each[1][date_index_pos:date_index_pos+10]
                freshness = self.__make_date(freshness, ' 4:00PM', '%m/%d/%Y %I:%M%p')
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
            self.__write_lists_csv( criminal_block_list, "logs/TS_normalized_crim.csv" )
            self.__write_lists_csv( civil_block_list, "logs/TS_normalized_civil.csv" )
        
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
            self.__write_lists_csv( crim_list, "logs/TS_final_list_crim.csv" )
            fileName = "CSVs/" + self._crimFileName
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
            self.__write_lists_csv( civil_list, "logs/TS_final_list_civil.csv" )
            fileName = "CSVs/" + self._civilFileName
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
    
    def getPeriod(self):
        return self._firstDate, self._lastDate
    
    def getFreshness(self):
        return self._freshness
    
    # =========================================
    # = Functions for manipulating the lists  =
    # =========================================
    def __logFileProcessing( self, logString):
        """
        Append a line to a log file.
        """
        try:
            with open("logs/CMSR1231-log.txt", "a") as f:
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
        Saves the date range and freshness is a text file.
        Returns the start date and last date, and the freshness of the report.
        """
        startDate = ""
        lastDate = ""
        for index, each in enumerate( rawReportList ):
            if "TODAYS DATE" in rawReportList[ index ]:
                freshness = rawReportList[ index ][13:23]
            if "REPORT FROM" in rawReportList[ index ]:
                startDate = rawReportList[ index ][13:23]
                lastDate = rawReportList[ (index + 1) ][13:23]    
            if (startDate != "" and lastDate != "" and freshness != ""):
                freshness = self.__makeDateSortable( freshness )
                startDate = self.__makeDateSortable( startDate )
                lastDate = self.__makeDateSortable( lastDate )                
                return freshness, startDate, lastDate
    
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
    
    def __write_lists_csv( self, block_list, file_name ):
        """
        Takes a list of blocks (each block is a list of items), a files location//name, and a list of headers
        Writes the blocks as rows in a CSV file.  Each item of the blocks is a comma-separated value.
        Returns the location of the CSV file.
        """
        print "here1"
        fileWriter = csv.writer( open(file_name, 'wb' ), delimiter=',', quotechar='"', quoting=csv.QUOTE_MINIMAL)
        print "here2"
        for each in block_list:
            print "here3"
            fileWriter.writerow(each)
        print "here4"

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
    
    def __isFresher( self ):
        """
        Reads in the freshness of the last file to be parsed and
        compares it to the freshness of the files passed to this instances.
        If the passed file is fresher, it returns true.
        """
        try:
            dateReader = csv.reader( open( self._dateListFilePath, 'rb' ) )
            for index, row in enumerate( dateReader ):
                if index == 2:
                    previousFreshness = row[ 0 ]
            if self._freshness >= previousFreshness:
                if self._verbose:
                    print "\n\nThe last successfully imported CMSRfile was created on %s. Now we will import the CMSR files created on %s." %( previousFreshness, self._freshness )
                return True
            else:
                print "\n\nThis files is not fresher than the previous import.\n"
        except Exception, e:
            print "There is no previous freshness date in the log file."
            return False
    
    def __isAllJudges( self ):
        """
        Confirms that the CMSR filed passed to this instance contains schedules for more that one jduge.
        It assumes that CMS can only create CMSR1231 files for all judges or only one judge.
        If the passed file contains more that one judge schedule, it returns true.
        """
        logString = "%s - Judes set contains: %s judges." %(  str( datetime.datetime.now())[:18], len( self._judges ))
        self.__logFileProcessing( logString )
        if self._verbose: print logString
        
        if len( self._judges ) > 1: return True
        return False
    

if __name__ == '__main__':
    """
    Testing the class
    """
    CMSRforTesting = "docket-sheets/cmsr1231.P53"
    testDocket = CMSR1231Docket( CMSRforTesting, verbose = True )