<?php
namespace Hypercharge;

const VERSION = '1.25.5';

class Config {

	const ENV_LIVE = 'live';
	const ENV_SANDBOX = 'sandbox';

	private static $user = '';
	private static $password = '';
	private static $mode = self::ENV_SANDBOX; // default
	private static $logger = null;

	private static $factory;

	private static $idSeparator = '---';

	static function set($user, $password, $mode) {
		if(!self::isValidMode($mode)) throw new Exception('mode must be "sandbox" or "live"');

		self::$user = $user;
		self::$password = $password;
		self::$mode = $mode;
	}

	static function setLogger(ILogger $logger) {
		self::$logger = $logger;
	}
	static function getLogger() {
		if(!self::$logger) self::$logger = new NullLogger();
		return self::$logger;
	}
	/**
	* @see #set
	* @returns string
	*/
	static function getUser() {
		return self::$user;
	}

	/**
	* @see #set
	* @returns string
	*/
	static function getPassword() {
		return self::$password;
	}

	/**
	* @see #set
	* @returns string self::ENV_LIVE or self::ENV_SANDBOX
	*/
	static function getMode() {
		return self::$mode;
	}

	/**
	* @see #set
	* @returns string self::ENV_LIVE or self::ENV_SANDBOX
	*/
	static function isSandboxMode() {
		return self::$mode == self::ENV_SANDBOX;
	}

	/**
	* @see #set
	* @returns string self::ENV_LIVE or self::ENV_SANDBOX
	*/
	static function isLiveMode() {
		return self::$mode == self::ENV_LIVE;
	}

	/**
	* @param string $mode
	* @returns boolean
	*/
	static function isValidMode($mode) {
		return ($mode == self::ENV_LIVE || $mode == self::ENV_SANDBOX);
	}

	/**
	* @protected
	* @return Hypercharge\IFactory
	*/
	static function getFactory() {
		if(!self::$factory) self::$factory = new Factory();
		return self::$factory;
	}

	/**
	* @protected
	* only for mocking in tests
	*/
	static function setFactory(IFactory $f) {
		self::$factory = $f;
	}

	/**
	* @public
	* @param mixed $str  either a string e.g. '<<<' or false
	*/
	static function setIdSeparator($str) {
		if(!((is_string($str) && !empty($str)) || $str === false)) throw new Errors\ArgumentError('parameter must be a non-empty string or false');
		self::$idSeparator = $str;
	}

	static function hasIdSeparator() {
		return self::$idSeparator !== false;
	}

	static function getIdSeparator() {
		return self::$idSeparator;
	}

}