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

function blink($pins, $offset, $on, $off, $repeat) {
	global $gpio;
	usleep($offset * 1000);
	for ($i = 0; $i < $repeat; $i++) { 
		$gpio->output($pins, Gpio::HIGH);
		usleep($on * 1000);
		$gpio->output($pins, Gpio::LOW);
		usleep($off * 1000);
	}
}

function carousel($pins, $numOnPins, $hop, $repeat) {
	global $gpio;
	$size = count($pins);
	// First put them all off
	$gpio->output($pins, Gpio::LOW);
	for ($i = 0; $i < $repeat * $size; $i++) {
		$position = $i % $size;
		for ($j = 0; $j < $numOnPins; $j++) {
			$gpio->output($pins[($position + $j) % $size], Gpio::HIGH);
		}
		usleep($hop * 1000);
		// Prepare for next loop
		for ($j = 0; $j < $numOnPins; $j++) {
			$gpio->output($pins[($position + $j) % $size], Gpio::LOW);
		}
	}
}

function shutdown($pins, $hop) {
	global $gpio;
	$size = count($pins);
	foreach ($pins as $pin) {
		$gpio->output($pin, Gpio::LOW);
		usleep($hop * 1000);
	}
}


// Let the user execute whatever long operation he wants (blink a led for a week?)
// He can close the connection if he thinks it takes too long
set_time_limit(0);


$action = $_GET['action'];
if (!isset($action)) {
	exitError("No action specified");
}

if (isset($_GET['mode'])) {
	$gpio->setMode($_GET['mode']);
}

if(isset($_GET['pins'])) {
	$pins = Pins::parse($gpio, $_GET['pins']);
	if (!$gpio->validatePins($pins)) {
		echo "not valid<br>";
		$pins = array();
	}
}

switch ($action) {
	case 'blink':
	$offset = $_GET['offset'];
	$on = $_GET['on'];
	$off = $_GET['off'];
	$repeat = $_GET['repeat'];
	blink($pins, $offset, $on, $off, $repeat);
	break;
	
	case 'carousel':
	$on = $_GET['on'];
	$hop = $_GET['hop'];
	$repeat = $_GET['repeat'];
	carousel($pins, $on, $hop, $repeat);
	break;

	case 'shutdown':
	shutdown($pins);
	break;

	case 'output':
	$val = $_GET['value'];
	$gpio->output($pins, $val);
	break;

	case 'input':
	$result['pins'] = $gpio->input($pins);
	break;

	case 'close':
	$gpio->close();
	break;

	case 'get_revision':
	$result['revision'] = $gpio->getRevision();
	break;

	case 'get_mappings':
	$result['mappings'] = Pins::mappings();
	break;

}
// Pretty print is just for the demo, you can remove it if you want
echo json_encode($result, JSON_PRETTY_PRINT);
?>