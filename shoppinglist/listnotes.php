<?php
require('../config.php');
require($CFG->dirroot.'/lib/setup.php');
require('keys.php');
require 'vendor/autoload.php';

function get_param($name, $default) {
	$field = $_GET;
	if (isset($field[$name])) {
		if (!empty($field[$name])){
			return $field[$name];
		}
	}
	return $default;
}

function init_evernote($token) {
	global $ENCLIENT, $NS;
	$ENCLIENT = new Evernote\Client(array('token' => $token, 'sandbox'=>false));
	$NS = $ENCLIENT->getNoteStore();
}

function get_notes($filter, $start =0,$limit = 10) {
	try {
	global $ENCLIENT, $NS;
	$spec = new EDAM\NoteStore\NotesMetadataResultSpec();
	$spec->includeTitle = True;
	 
	$notelist = $NS->findNotesMetadata($filter, $start, $limit, $spec);
	return  $notelist;
/* 
	$c = '';
	$notes = array();
	foreach($notelist->notes as $n) {
		$notes[] = $NS->getNote($n->guid, true,false,false,false);
	}
	return $notes;
*/	}
	catch(Exception $e) {
		echo($e->errorCode);
		exit();
	}

}

function get_template() {
	$template = file_get_contents(__DIR__.'/page.tpl');
	return $template;
}

function get_page($body,$header='') {
	$template = get_template();
	$page = str_replace("{%%HEADCONTENT%%}", $header, $template);	//replace head maerk
	$page = str_replace("{%%BODYCONTENT%%}", $body, $page);
	return $page;
}

function format_note($note) {
	$out = '';
	
//	$out .= "<h2>{$note->title}</h2>";	
	$out .= "{$note->content}";

	return $out;
}


ini_set('display_errors','1');
init_evernote($evernoteKey);

//todo get notes in the "list" tag that are newer than the last printed date.

$filter = new EDAM\NoteStore\NoteFilter();
//$filter->tagGuids = array($tagguid);
$filter->order = 2;	// order by updated 
$filter->ascending = false;

$note = get_param('noteid', false);
$start = get_param('start',0);
if (!$note) {

$notes = get_notes($filter,$start);
//var_dump($notes);
echo $OUTPUT->header();
echo "<p>Select a note to print</p>";
echo "<ul>";
foreach($notes->notes as $n) {
	echo "<li><a href='{$CFG->wwwroot}/shoppinglist/listnotes.php?noteid={$n->guid}'>{$n->title}</a></li>";
}
echo "</ul>";
echo $OUTPUT->footer();
}
//if ($note) {
else {
$n = $NS->getNote($note, true,false,false,false);

$body ='<h1>'.$n->title.'</h1><div>Printed '. date('r')."</div>";
//foreach($notes as $note) {
	$body.=format_note($n);
//}
//$etagval = md5($devToken.$body);

$page =  get_page($body);
//echo $page;
send_to_lp($lpKey,$page);
header('Location:'. $CFG->wwwroot.'/shoppinglist/');

}
function send_to_lp($key,$html, $style = '', $dump = false) {
	if ($html == '') {
		return;
	}
	$ch = curl_init('http://remote.bergcloud.com/playground/direct_print/'.$key);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'html='. urlencode($html));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	$result = curl_exec($ch);		
	curl_close($ch);
	error_log("Little Printer result:".$result);
}
