<?php
/* S5m - The Simple Silent Slide Syncronization Server for HTML Slide Presentations - ver 0.2  */

/************************************ This code requires memcached *****************************/

/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is the Simple Silent Slide Synchronization Server (S5)
 *
 * The Initial Developer of the Original Code is
 * Richard A. Milewski (richard at mozilla dot com)
 * Portions created by the Initial Developer are Copyright (C) 2010
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

/* The Slide synchronizer database contains 3 fields:
 *
 *      url - the MD5 hash of the document url
 *  control - the key required to control the document.
 *   status - the control signal to other instances of the document (usually a CSS element id).
 *   
 * There are two legal queries and four possible outcomes:
 *
 * url,control,status - If the url exists in the database, status is updated and the record is timestamped.
 *                     If the url does not exist, a new record is created.
 *                     
 * url - The current status of the document is returned.
 *                     
 * Any other combination of input parameters returns an error message.
 *
 * The return is in the form of a JSON response array:  
 *			{'sync':("ok"|"error"),"action":"("zoom"|"theme"|"request"),"result":"(<status>|<error msg>)";
 */

$time_start=microtime(true);

header('Access-Control-Allow-Origin: *');  

$ttl = (1800);  # Number of seconds records persist in memcache

function respond($success,$action,$label,$message) { # Note that any call to respond() terminates the program
    global $time_start;
    $time_end = microtime(true);
    $run_time = 1000 * ($time_end - $time_start);
	$response = array('sync'=>$success, 'action'=>$action, $label=>$message, 'time'=>$run_time);
	echo json_encode($response);
	exit;
}

$memcache = new Memcache;
if (!$memcache->connect('localhost', 11211)) {
	respond('fail', 'connect', 'error', 'Memcache connect failed');
}


import_request_variables("G","s5_");

if (!strlen($s5_url)) {
	respond('fail', 'url', 'error', 'No document url');
}


/* Check to see if this url is active */
$result = $memcache->get($s5_url);

if ($result != null) { // This is an existing document 
	
	if ($s5_control == $result['control']) { // We're a leader so post the status
		if ($s5_status == null) {
			respond('fail', 'control', 'error','No status supplied');
		}
		$result['action'] = $s5_action;
		$result['status'] = $s5_status;
		$memcache->set($s5_url,$result,0,$ttl);
		respond('ok', $s5_action, 'result', 'Updated');
	
	} else { //We're a follower so fetch the status
		$newstatus = $result['status'];
		$action = $result['action'];
		respond('ok', $action, 'result', $newstatus);
    }

} else { /* The url is not in the cache - new or expired url */
    if (!isset($s5_control) && !isset($s5_status)) { 
    	respond('fail', 'request', 'error', 'URL not in cache'); 
    }
	if (!isset($s5_control) || !isset($s5_status)) { 
		respond('fail', 'registration', 'error', 'Bad parameters'); 
	}
	$result = array('control'=>$s5_control, 'action'=>$s5_action, 'status'=>$s5_status);
	$memcache->set($s5_url,$result,0,$ttl);
	respond("ok","registration","result","New registration");
}
?>