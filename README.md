# Error Handler

The Error Handler subsystem isn't just for errors -- it is mainly for tracking activity in the Point System.

## System Description

The system is composed of:
1. text log file;
2. PHP programs to read and write the log file.

The text log file is in the folder structure:
/home/ESIS/dataonly/errorhandler_data/tracking.log

The PHP script is written such that external programs accessing the log file utilize class instantiation. The following scripts are as follows:
### error_handler.php:
This handles all of the access, read and write functions for the tracking.log file. All code is within a class definition. The functions within the class are:
1. __construct(): This reads a JSON file for default values to write into the log file.
2. logmessage: This will construct the format of the log file entries (Priority, date/time, web page name, etc.).
3. readmessage: This will read the log file in to an array. Optionally, depending on the argument supplied, it will call the "whitelisted" function to include non-whitelisted IP addresses.  
4. log_parse_whitelist: This will remove the approved whitelisted IP addresses and leave non-whitelisted IPs.
5. fct_explode_all: This will read each row of the array previously created by "readmessage" and breakout each column. This results in an array of arrays with each of the log file's columns in it.
6. summarize_IPs: This will read each row of the array and summarize the 4 octet IPs down to 3 octets. It also includes a count of each octet.

### trackinglog_purge.php
This program will purge the log of data based on a retention period. The program DOES NOT utilize any of the error_hanbdler.php functions.
