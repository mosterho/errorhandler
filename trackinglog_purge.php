<?php

// This program will purge the tracking log based on the current month and year.
// The __contruct functions handles the entire process.
// The public variables are defined.
// Today's date is determined, then the number of days to keep the information is determined.
// The program will then read the tracking log into an array. The program then loops through
// the array and determines which rows it will KEEP in the log file (as opposed to
//  deleting the rows from the file).
// Once the tracking file is read in its entirety and the data to keep is determined,
// write the contents of the array back into the log file.

class cls_trackinglog_purge{
  public $logfilename = '/home/ESIS/dataonly/errorhandler_data/tracking.log';
  public $logfile_keepdays = '/home/ESIS/dataonly/errorhandler_data/trackinglog_keep.json';
  public $logfile_contents;
  public $new_contents = array();

  function __construct(){
    if(!file_exists($this->logfilename)){
      die('In trackinglog_purge, the '.$this->logfilename.' was not found');
    }
    if(!file_exists($this->logfile_keepdays)){
      die('In trackinglog_purge, the '.$this->logfile_keepdays.' was not found');
    }

    // date() retrieves today's date as a string in W3C format (same as log file).
    // date_create() creates a DateTime object from the string.
    $purge_date = date_create(date(DATE_W3C));   // Get Today's date.
    // Retrieve the number of days to keep in the log from JSON file.
    $wrk_keep_days = file_get_contents($this->logfile_keepdays);
    $wrk_keep_days_jsondecoded = json_decode($wrk_keep_days);
    $date_interval = date_interval_create_from_date_string($wrk_keep_days_jsondecoded->keep); // Create a date interval object (needed for date math).
    //var_dump($date_interval);
    //
    $purge_date = date_sub($purge_date, $date_interval);  // Subtract ## days from today's date.
    // var_dump($purge_date);
    $this->logfile_contents = file($this->logfilename);  // file creates an array, one line per element.
    // Read the log file. Explode each line to get variables. $data[2] is the log file entry date/time.
    foreach($this->logfile_contents as $row){
      $data = explode(' ', $row);
      $row_date = date_create($data[2]);  // Create a date/time object for comparison.
      if($row_date <= $purge_date){
        //echo PHP_EOL.'Found one...'.$row;
      }
      else{
        array_push($this->new_contents, $row);  // NOTE: a newline '\n' is not required.
      }
    }
    //var_dump($this->new_contents);
    // The log file will now be overwritten in its entirety.
    file_put_contents($this->logfilename, $this->new_contents);
  }
}
//  End of class definition.



/*-------------------------------------------------------------------------------*/
/*   mainline.
/*-------------------------------------------------------------------------------*/

$new_class = new cls_trackinglog_purge;

?>
