<?php 

// This exposes all of Gpio's internal methods to a nice format
require_once('../../lib/gpio.php');

$gpio = new Gpio();
$result = array('status' => 'OK');

function exitError($err) {
	global $result;
	$result['status'] = "ERROR";
	$result['error'] = $err;
	echo json_encode($result, JSON_PRETTY_PRINT);
	die();
}

function inputMultiple($pinarray) {
	global $gpio, $result;

	$pins = array();
	foreach ($pinarray as $key => $pin) {
		$pins[$pin] = $gpio->input($pin);
	}
	$result['pins'] = $pins; 
}

function outputMultiple($pinarray, $value) {
	global $gpio;
	foreach ($pinarray as $key => $pin) {
		$gpio->output($pin, $value);
	}
}
function blink($pins, $offset = 0, $on = 70, $off = 70, $repeat = 5) {
	global $gpio;
	usleep($offset * 1000);
	for ($i = 0; $i < $repeat; $i++) { 
		foreach ($pins as $key => $pin) { 
			$gpio->output($pin, Gpio::HIGH);
		}
		usleep($on * 1000);
		foreach ($pins as $key => $pin) {
			$gpio->output($pin, Gpio::LOW);
		}
		usleep($off * 1000);
	}
}

function validateMultiple($pinarray) {
	global $gpio;
	foreach($pinarray as $key => $pin) {
		if (!$gpio->validatePin($pin)) {
			return false;
		}
	}
	return true;
}

// Let the user execute whatever long operation he wants (blink a led for a week?)
// He can close the connection if he thinks it takes too long
set_time_limit(0);


$action = $_GET['action'];
if (!isset($action)) {
	exitError("No action specified");
}

if(isset($_GET['pins'])) {
	$getPins = $_GET['pins'];
}
if ($getPins == 'all') {
	$pins = $gpio->getAllPins();
} else if (isset($getPins) && !empty($getPins)) {
	$pins = array_map('trim', explode(',', $getPins));
} else if ($action != 'get_revision') {
	exitError("No pins specified");
}
if (!validateMultiple($pins)) {
	exitError("One or more pins are invalid");
}

switch ($action) {
	case 'blink':
	$offset = $_GET['offset'];
	$on = $_GET['on'];
	$off = $_GET['off'];
	$repeat = $_GET['repeat'];
	blink($pins, $offset, $on, $off, $repeat);
	break;
	
	case 'output':
	$val = $_GET['value'];
	outputMultiple($pins, $val);
	break;

	case 'input':
	inputMultiple($pins);
	break;

	case 'close':
	$gpio->close();
	break;

	case 'get_revision':
	$result['revision'] = $gpio->getRevision();
	break;

}

echo json_encode($result, JSON_PRETTY_PRINT);
?>