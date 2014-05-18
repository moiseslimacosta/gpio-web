<?php

// This class provides the basic implementation to interact with the GPIO
class Gpio {

	const HIGH = 1;
	const LOW = 0;

	const IN = "in";
	const OUT = "out";

	const REV1 = "rev1";
	const REV2 = "rev2";

	private $revision;
	private $gpiodir = "/sys/class/gpio";

	// All valid pin numbers are in this array (we don't want to break the board!)
	private $pins;
	private $exportPins;

	public function __construct() {
		// As per the following table, if the revision is < 4, we have a rev1
		// http://www.raspberrypi.org/upcoming-board-revision/
		if ($this->getRevisionCode() < 4) {
			// (rev1) http://geekypi.files.wordpress.com/2013/01/gpio_layout.png
			$this->revision = Gpio::REV1;
			$this->pins = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);
		} else {
			// (rev2) http://ecuflashking.com/2012-12-06-RaspberryPi/Raspberry-Pi-GPIO-Layout-Revision-2-e1347664831557.png
			$this->revision = Gpio::REV2;
			$this->pins = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
		}
	}
	
	public function getRevision() {
		return $this->revision;
	}
	// Get the revision code, with this we can determine if it's a revision 1 or revision 2 model
	private function getRevisionCode() {
		$cpuinfo = preg_split ("/\n/", file_get_contents('/proc/cpuinfo'));
		foreach ($cpuinfo as $line) {
			if (preg_match('/Revision\s*:\s*([^\s]*)\s*/', $line, $matches)) {
				return hexdec($matches[1]) & 0xf;
			}
		}
		return 0;
	}

	public function validatePin($pin) {
		return is_numeric($pin) && in_array($pin, $this->pins);
	}


	private function validateDirection($direction) {
		return ($direction === Gpio::IN || $direction === Gpio::OUT);
	}

	private function validateValue($value) {
		return ($value === Gpio::HIGH || $value === Gpio::LOW);
	}

	// The following functions will read the file always, instead of caching,
	// We do this because another program could modify the gpio

	private function export($pin) {
		if (!$this->validatePin($pin) || $this->isExported($pin)) return;

		$this->write_safe($this->gpiodir.'/export', $pin);
	}

	// Returns true if the pin can be used after this call
	public function setup($pin, $direction) {
		// First export the pin
		$this->export($pin);
		if (!$this->isExported($pin) || !$this->validateDirection($direction)) return false;

		// Only setup for different direction
		if ($this->getDirection($pin) != $direction) {
			$this->write_safe($this->gpiodir.'/gpio'.$pin.'/direction', $direction);
		}
		return true;
	}

	public function unexport($pin) {
		if (!$this->isExported($pin)) return;
		$this->write_safe($this->gpiodir.'/unexport', $pin);
	}

	// Returns an array of pins with which you will be able to work
	public function getAllPins() {
		return $this->pins;
	}


	public function close() {
		$this->unexportAllPins();
	}

	// For closing
	private function unexportAllPins() {
		foreach ($this->pins as $key => $value) {
			$this->unexport($value);
		}
	}

	public function isExported($pin) {
		if (!$this->validatePin($pin)) return false;
		return file_exists($this->gpiodir.'/gpio'.$pin);
	}

	// Returns the current direction of a pin
	public function getDirection($pin) {
		if (!$this->isExported($pin)) 
			return false;
		return trim(file_get_contents($this->gpiodir.'/gpio'.$pin.'/direction'));
	}

	public function isInput($pin) {
		return ($this->getDirection($pin) == Gpio::IN);
	}

	public function isOutput($pin) {
		return ($this->getDirection($pin) == Gpio::OUT);
	}

	public function input($pin) {
		// We can actually read pins in output mode
		// if (!$this->isInput($pin)) return;
		if (!$this->isExported($pin)) return;
		return trim(file_get_contents($this->gpiodir.'/gpio'.$pin.'/value'));
	}

	public function output($pin, $value) {
		if (!$this->setup($pin, Gpio::OUT)) return;
		return $this->write_safe($this->gpiodir.'/gpio'.$pin.'/value', $value);
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