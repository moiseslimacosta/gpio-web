<?php


class Pins {

	// Returns an array
	public static function parse(Gpio $gpio, $pinspec) {
		if (is_array($pinspec)) {
			// Return original array
			return $pinspec;
		} else if ($pinspec == "all") {
			// all
			return $gpio->getAllPins();
		} else {
			// 2,3,6,6
			return array_map('trim', explode(',', $pinspec));
		}
	}

	// All available pins on device
	public static function all() {
		return (Board::getRevision() == Board::REV1) ? Pins::r1pins() : Pins::r2pins();
	}

	// http://geekypi.files.wordpress.com/2013/01/gpio_layout.png
	public static function r1pins() {
		return array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);
	}
	// http://ecuflashking.com/2012-12-06-RaspberryPi/Raspberry-Pi-GPIO-Layout-Revision-2-e1347664831557.png
	public static function r2pins() {
		return array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);	
	}
	public static function mappings() {
		return (Board::getRevision() == Board::REV1) ? Pins::r1mappings() : Pins::r2mappings();
	}

	// Physical in => BCMGPIO
	public static function r1mappings() {
		return array(
			'3'  =>  0,
			'5'  =>  1,
			'7'  =>  4,
			'8'  => 14,
			'10' => 15,
			'11' => 17,
			'12' => 18,
			'13' => 21,
			'15' => 22,
			'16' => 23,
			'18' => 24,
			'19' => 10,
			'21' =>  9,
			'22' => 25,
			'23' => 11,
			'24' =>  8,
			'26' =>  7
			);
	}

	public static function r2mappings() {
		return array(
			'3'  =>  2,
			'5'  =>  3,
			'7'  =>  4,
			'8'  => 14,
			'10' => 15,
			'11' => 17,
			'12' => 18,
			'13' => 27,
			'15' => 22,
			'16' => 23,
			'18' => 24,
			'19' => 10,
			'21' =>  9,
			'22' => 25,
			'23' => 11,
			'24' =>  8,
			'26' =>  7
			);
	}

}

class Board {
	// This table explains the revisions
	// http://www.raspberrypi.org/upcoming-board-revision/
	const REV1 = "rev1";
	const REV2 = "rev2";

	public static function getRevision() {
		return (Board::getRevisionCode() < 4) ? Board::REV1 : Board::REV2;
	}
	// Get the revision code, with this we can determine if it's a revision 1 or revision 2 model
	public static function getRevisionCode() {
		$file = preg_split ("/\n/", file_get_contents('/proc/cpuinfo'));
		foreach ($file as $line) {
			if (preg_match('/Revision\s*:\s*([^\s]*)\s*/', $line, $match)) {
				return hexdec($match[1]) & 0xf;
			}
		}
		// By default, revision 2?
		return 4;
	}
}
// Basic interaction with GPIO
class Gpio {

	const HIGH = 1;
	const LOW = 0;

	const IN = "in";
	const OUT = "out";

	// BCM GPIO numbers (default)
	const MODE_BCM = "bcm";
	// Physical pin numbers
	const MODE_BOARD = "board";

	private $revision;
	private $gpiodir = "/sys/class/gpio";
	private $mode = Gpio::MODE_BCM;

	// All valid pin numbers are in this array (we don't want to break the board!)
	private $pins;
	private $mappings;
	private $exportPins;

	public function __construct() {
		$this->pins = Pins::all();
		$this->mappings = Pins::mappings();
		$this->revision = Board::getRevision();
	}

	// Set the current mode (board or bcm)
	public function setMode($mode) {
		if ($mode != Gpio::MODE_BCM && $mode != Gpio::MODE_BOARD) return;
		$this->mode = $mode;
	}

	// Get the BCM Gpio values for the physical pin
	private function map($pin) {
		if ($this->mode == Gpio::MODE_BCM) {
			return $pin;
		}
		return $this->mappings[$pin];
	}
	// Return a pin number that the user expects
	private function unmap($pin) {
		if ($this->mode == Gpio::MODE_BCM) {
			return $pin;
		}
		return array_search($pin, $this->mappings);
	}

	public function getRevision() {
		return $this->revision;
	}

	public function validatePins(array $pins) {
		foreach ($pins as $key => $pin) {
			if (!is_numeric($pin) || !in_array($this->map($pin), $this->pins)) {
				return false;
			}
		}
		return true;
	}

	private function validatePin($pin) {
		return (is_numeric($pin) && in_array($this->map($pin), $this->pins));
	}

	private function validateDirection($direction) {
		return ($direction === Gpio::IN || $direction === Gpio::OUT);
	}

	private function validateValue($value) {
		return ($value === Gpio::HIGH || $value === Gpio::LOW);
	}

	// The following functions will read the file always, instead of caching,
	// We do this because another program could modify the gpio exports

	// Previously to export multiple pins, export and setup were called for each pin
	// Now export is called on all pins, which creates the nodes in the kernel
	// Then setup will be faster
	private function export($pins) {
		// Support a single pin:
		if (!is_array($pins)) 
			$pins = array($pins);
		if (!$this->validatePins($pins)) return;
		foreach ($pins as $key => $pin) {
			if (!$this->isExported($pin)) {
				$this->write_safe($this->gpiodir.'/export', $this->map($pin));
			}
		}
	}

	// Setup multiple pins to the specified direction.
	// Returns true if all were setup correctly
	public function setup($pins, $direction) {
		// Support a single pin:
		if (!is_array($pins)) 
			$pins = array($pins);
		if (!$this->validateDirection($direction)) return false;
		// First export the pins
		$this->export($pins);
		foreach ($pins as $key => $pin) {
		// Only setup for different direction
			if ($this->getDirection($pin) != $direction) {
				$this->write_safe($this->gpiodir.'/gpio'.$this->map($pin).'/direction', $direction);
			}
		}		
		return true;
	}

	public function unexport($pins) {
		// Support a single pin:
		if (!is_array($pins)) 
			$pins = array($pins);
		foreach ($pins as $key => $pin) {
			if (!$this->isExported($pin)) return;
			$this->write_safe($this->gpiodir.'/unexport', $this->map($pin));
		}
	}

	// Returns an array of pins with which you will be able to work
	public function getAllPins() {
		return $this->pins;
		// $result = array();
		// foreach ($this->pins as $key => $pin) {
		// 	$result[]=$this->unmap($pin);
		// }
		// return $result;
	}


	public function close() {
		$this->unexportAllPins();
	}

	// For closing
	private function unexportAllPins() {
		$this->unexport($this->pins);
	}

	public function isExported($pin) {
		if (!$this->validatePin($pin)) return false;
		return file_exists($this->gpiodir.'/gpio'.$this->map($pin));
	}

	// Returns the current direction of a pin
	public function getDirection($pin) {
		if (!$this->isExported($pin)) 
			return false;
		return trim(file_get_contents($this->gpiodir.'/gpio'.$this->map($pin).'/direction'));
	}

	public function isInput($pin) {
		return ($this->getDirection($pin) == Gpio::IN);
	}

	public function isOutput($pin) {
		return ($this->getDirection($pin) == Gpio::OUT);
	}

	// Input now returns an array!
	public function input($pins) {
		// Support a single pin:
		if (!is_array($pins)) 
			$pins = array($pins);
		$this->export($pins);
		$result = array();
		foreach ($pins as $key => $pin) {
			// We can actually read pins in output mode
			// if (!$this->isInput($pin)) return;
			// TODO Poll support and event notification is ofcourse still missing
			// But you can use AJAX or websockets in your API to get the value, 
			// Alghough it will be very slow...
			$result[$this->unmap($pin)] = trim(file_get_contents($this->gpiodir.'/gpio'.$this->map($pin).'/value'));			
		}
		return $result;
	}

	public function output($pins, $value) {
		// Support a single pin:
		if (!is_array($pins)) 
			$pins = array($pins);
		
		if (!$this->setup($pins, Gpio::OUT)) return;
		foreach ($pins as $key => $pin) {
			$this->write_safe($this->gpiodir.'/gpio'.$this->map($pin).'/value', $value);
		}
	}

	// This waits untill the udev script is done with chmod and chown (so we have write access)
	function write_safe($filename, $value) {
		// Somehow the kernel doesn't allow to write even though is_writable is true...
		error_reporting(0);
		file_exists($filename) or die("File does not exist:".$filename);
		// Wait until udev script is done...
		while (true) {
			if (!is_writable($filename)) {
				usleep(5 * 1000);
			} else if (file_put_contents($filename, $value) <= 0) {
				usleep(5 * 1000);
			} else {
				break;
			}
		}
	}
}

?>