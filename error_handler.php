<?php

class cls_error_handler{
  public $my_json;
  public $my_json_decoded;
  public $message_defaults;
  public $logfilename = '/home/ESIS/dataonly/errorhandler_data/tracking.log';
  public $logfile_data;  // in functions this is an array after the logfile is read.
  public $logfile_data_explode = array();  // Same as logfile_data, but all elements are broken down.


  function __construct(){
    $this->my_json = file_get_contents("/home/ESIS/dataonly/errorhandler_data/error_handler.json");
    $this->my_json_decoded = json_decode($this->my_json);
    $this->message_defaults = $this->my_json_decoded->{"message_defaults"};
  }


  // This function will write to the log file "tracking.log".
  function logmessage($arg_message='A default message') {
    // Build a message and utilize the syslog function.
    // https://datatracker.ietf.org/doc/html/rfc5424#section-6.2.1
    // Determine message priority "prival" based on syslog format. Facility "LOCAL0" and severity "Informational".
    // 16 local use 0  (local0)
    // 6  Informational: informational messages
    // (16 * 8) + 6 = 134

    //$datetime = new DateTime('now', new DateTimeZone('America/New_York'));
    $datetime = date(DATE_W3C);  //date() retrieves today's date and returns it as a string in W3C format.

    $full_message = '';
    // Yes, PRI and version should NOT have a space between them, but
    // "explode" works a lot better to break up the columns.
    $full_message .= '<134> 1 '; // message PRI and version
    //$full_message .= $datetime->format('Y-m-d\TH:i:sP');  // escaped letter "T" is part of RFC5424
    $full_message .= $datetime;
    $full_message .= ' ';
    if(isset($_SERVER['SERVER_NAME'])){
      $full_message .= $_SERVER['SERVER_NAME'].' ';
    }
    else{
      $full_message .= $this->message_defaults->{"systemname"}.' ';
    }
    $full_message .= $this->message_defaults->{"program"}.' ';
    $full_message .= '- - ';  // This is part of normal NILVALUE in syslog format.
    $full_message .= $arg_message;

    error_log($full_message, 3, $this->logfilename);   //error_log is a built-in php function
  }


  function readmessage($arg_whitelist=False){
    $this->logfile_data = file($this->logfilename);  // "file" is a PHP function to load a text file into an array.
    // If the argument is false, this will reload the array with only non-whitelisted IPs.
    if($arg_whitelist == False){
      $this->logfile_data = $this->log_parse_whitelist();  // overlay the array with only non-whitelisted IPs.
    }
    return $this->logfile_data;
  }


  // log_parse_whitelist function will read the public array of IPs already read, push only non-whitelisted IPs
  // to a temporary array, and return the temporary array.
  // This is mainly called from the "readmessage" function above.
  function log_parse_whitelist(){
    $whitelist_array = array();
    foreach($this->logfile_data as $row){
      $wrk_array = (explode(' ', $row, 10));  //Return an array of strings.
      if($wrk_array[8] == 'whitelisted:False'){
        array_push($whitelist_array, $row);
      }
    }
    return $whitelist_array;
  }


  // This function will break down all elements of the log file's row info to an array
  function fct_explode_all(){
    foreach($this->logfile_data as $row){
      // Return an array of strings.
      // Note the 9th and 10th elements are JSON formatted (although not formatted correctly).
      $wrk_array = (explode(' ', $row, 10));  //Return an array of strings.
      $temp_whitelist = str_ireplace('whitelisted:', '', $wrk_array[8]);
      $temp_json_response = str_ireplace('Response:', '', $wrk_array[9]);
      $temp_json_response = json_decode($temp_json_response, true);  // true will create an associative array, not a JSON object.
      $file_data_exploded_array = array();  // Work array.
      array_push($file_data_exploded_array, $wrk_array[0], $wrk_array[1], $wrk_array[2], $wrk_array[3], $wrk_array[4], $temp_whitelist);
      // NOTE: The response variable may not be set if the IP is associated to a LAN, not WAN.
      //  but each element will be set to empty strings if not.
      if(isset($temp_json_response)){
        foreach($temp_json_response as $key=>$value){
          array_push($file_data_exploded_array, $value);
        }
      }
      else{
        array_push($file_data_exploded_array, '', '', '', '', '', '', '', '', '', '', '', '');
      }
      //var_dump($temp_json_response);
      // Once all elements for a row have been "exploded out", write the one line work array to the full log file data array.
      array_push($this->logfile_data_explode, $file_data_exploded_array);
    }
    //var_dump($this->logfile_data_explode);
    return $this->logfile_data_explode;
  }


  // This function will read each row in the log file array and parse out the
  // IP address up to the 3rd octet. This will include a count of each 3rd octet encoutnered.
  function summarize_IPs($arg_whitelisted=False){
    $IP_array = array();
    $pattern = '/\d{1,3}\.\d{1,3}\.\d{1,3}\./';  // REGEX pattern for 3 octet IP.
    //$logfile = $this->readmessage($arg_whitelisted);
    foreach($this->logfile_data as $row){
      // each row is retrieved as a long string, but is well-formed. Each value in the row
      // can be separated based on the "explode" string function.
      // The 9th element of the explode contains the "response" variable.
      $wrk_array = (explode(' ', $row, 10));  //Return an array of strings.
      if(($wrk_array[8] == 'whitelisted:False' and $arg_whitelisted == False) or ($arg_whitelisted == True)){
        $wrk_object = $wrk_array[9];
        $wrk_object2 = str_ireplace('Response:', '', $wrk_object);  // Strip out "response" label and creates string.
        $json = json_decode($wrk_object2);  // Creates a PHP object from the string.
        // If not null, use REGEX to determine 3 octets and add to another work array.
        if(!is_null($json)){
          preg_match($pattern, $json->ip, $IP_array_tmp);  // Match produces an array.
          array_push($IP_array, $IP_array_tmp[0]);  //Push the single element to another array.
        }
      }
    }
    return $IP_array;
  }

  // end of class.
}

/*-------------------------------------------------------------------------------*/
/*   mainline for testing only.
/*-------------------------------------------------------------------------------*/

/*
$wrk_cls_error_handler = new cls_error_handler;
$wrk_cls_error_handler->readmessage();
//$wrk_cls_error_handler->summarize_IPs();
$wrk_cls_error_handler->fct_explode_all();
var_dump($wrk_cls_error_handler);
*/
/*
$wrk_cls_error_handler = new cls_error_handler;
$wrk_cls_error_handler->logmessage();
*/

?>
