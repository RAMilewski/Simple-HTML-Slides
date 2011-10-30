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

header('Access-Control-Allow-Origin: *');  
header("Content-Type: text/event-stream\n\n");


$url = $_REQUEST['url'];

function respond($success,$action,$label,$message) { 
	$response = array('sync'=>$success, 'action'=>$action, $label=>$message);
	$myjson = json_encode($response);
	echo ("data: $myjson \n");
	flush();
}

$memcache = new Memcache;
if (!$memcache->connect('localhost', 11211)) {
	respond('fail', 'connect', 'error', 'Memcache connect failed');

} elseif (!strlen($url)) {
	respond('fail', 'url', 'error', 'No document url');

} else {

	/* Check to see if this url is active */
	$result = $memcache->get($url);
}

if ($result != null) { // This is an existing document 
	//We're a follower so fetch the status
	$newstatus = $result['status'];
	$action = $result['action'];
	respond('ok', $action, 'result', $newstatus);
} 


?>