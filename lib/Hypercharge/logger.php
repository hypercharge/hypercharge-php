<?php
namespace Hypercharge;

/**
* log into file:
* $file = fopen('/var/log/foo.log', 'w');
* Hypercharge\Config::setLogger(new Hypercharge\FileLogger($file));
* ...
* fclose($file);
*
* Or if for some reason you need the log as a string you can log into a memory buffer:
*
* $buffer = fopen('php://memory', 'rw');
* Hypercharge\Config::setLogger(new Hypercharge\FileLogger($buffer));
* ...
* // get log:
* rewind($buffer);
* $log = fread($buffer, 5000);
* fclose($buffer);
*/
class FileLogger implements ILogger {
	protected $level = self::DEBUG;
	protected $fileHandle = null;

	function __construct($fileHandle, $level = self::DEBUG) {
		$this->level = $level;
		$this->fileHandle = $fileHandle;
	}

	function debug($str) {
		$this->log(self::DEBUG, $str);
	}

	function info($str) {
		$this->log(self::INFO, $str);
	}

	function error($str) {
		$this->log(self::ERROR, $str);
	}

	function log($level, $str) {
		if($level < $this->level) return;
		fwrite($this->fileHandle, $str."\n");
	}
}

/**
* used as a dummy placeholder instead of null
*/
class NullLogger implements ILogger {

	function debug($str) {
	}

	function info($str) {
	}

	function error($str) {
	}
}

/**
* usefull for debugging automated tests in the terminal
*/
class StdoutLogger extends FileLogger {
	function __construct($level = self::DEBUG) {
		parent::__construct(fopen('php://stdout', 'w'));
	}
	function __destruct() {
		if(!$this->fileHandle) return;
		fclose($this->fileHandle);
		$this->fileHandle = 0;
	}
}

