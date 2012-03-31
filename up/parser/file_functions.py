# file_functions.py
# Created by Scott Brenner on 2011-08-25.
# Copyright (c) 2011 Scott Brenner. All rights reserved.

import ftplib, os
try:
   import cPickle as pickle
except:
   import pickle


def ftpFile(file, site, dir, user=(), verbose=True):
    """
    upload a file by ftp to a site/directory
    login hard-coded, binary transfer
    """
    if verbose: print 'Uploading', file
    local = open(file, 'rb')
    remote = ftplib.FTP(site)
    remote.login(*user)
    remote.cwd(dir)
    remote.storbinary('STOR ' + file, local, 1024)
    remote.quit()
    local.close()
    if verbose: print 'Upload done.'

def fingerPrintFile(filePath):
    """
    Takes a file path
    Returns a tupel.  (filePath, time modified, time created, length)
    """
    # create tuple describing the file
    return (filePath,
            os.path.getmtime(filePath),
            os.path.getctime(filePath),
            os.path.getsize(filePath)
            )        

def write_data_to_file(data_obj,location_name):
    """
    Takes a data object and file location//name and pickles and writes the object
    to the given location//name.
    """
    output = open(location_name, 'wb')
    pickle.dump(data_obj, output,-1)
    output.close()
    
    return location_name

def read_data_from_file(location_name):
    """
    Takes a file location//name and returns the data object.
    """
    try:
        return pickle.load(open(location_name, 'rb'))
    except Exception, e:
        return False    

