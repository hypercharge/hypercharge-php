<?php
namespace Hypercharge;

require_once 'XmlMapping.php';
/**
* static helper methods used all over in hypercharge-php
*/
class Helper {
	private static $xmlMapping = null;

	/**
	* @param string $klass without "Hypercharge\" prefix
	* @param mixed $request array or instanceof $klass
	* @returns Hypercharge\IRequest
	*/
	static function ensure($klass, $request) {
		$klass = 'Hypercharge\\'.$klass;
		if(gettype($request) != 'object') {
			return new $klass($request);
		}
		if($request instanceof $klass) {
			return $request;
		}
		$type = gettype($request);
		if($type == 'object') $type = get_class($type);
		throw new Errors\ArgumentError("'request' must be an array or a $klass but was $type");
	}

	static function assign($self, $p) {
		if(!(is_array($p) || is_object($p))) return;
		if(!self::$xmlMapping) self::$xmlMapping = new XmlMapping();
		$xmlMapping = self::$xmlMapping;
		foreach($p as $k=>$v) {
			if($k === '__attributes') continue;
			if(!($v instanceof Serializable)) {
				$klass = $xmlMapping->getClass($k);
				if($klass) {
					$v = new $klass($v);
				}
				$converter = $xmlMapping->getConverter($k);
				if($converter) {
					$v = $converter->fromXml($v);
				}
			}
			$self->{$k} = $v;
		}
	}

	/**
	* appends random suffix to $transaction_id to prevent "'transaction_id' has already been used!" error.
	* default divider is '---'
	* $transaction_id = "YOUR_SHOP_ORDER_ID";
	* Hypercharge\Helpder::appendRandomId($transaction_id);
	* returns string
	* "YOUR_SHOP_ORDER_ID---5187b98ac1c5e"
	*
	* use Config::setIdDiver to customize divider string.
	* e.g. if you prefer "<<<" as divider:
	* Config::setIdDiver('<<<');
	* with
	* $transaction_id = "YOUR_SHOP_ORDER_ID";
	* Hypercharge\Helpder::appendRandomId($transaction_id);
	* then will return
	* "YOUR_SHOP_ORDER_ID<<<5187b98ac1c5e"
	*
	* If you do not want random prefix Config::setIdDiver(false);
	*
	* @param string $transaction_id
	* @return string   $transaction_id . Config::getIdDivider() . uniqid()
	*/
	static function appendRandomId($transaction_id) {
		if(!Config::hasIdSeparator()) return $transaction_id;
		return $transaction_id . Config::getIdSeparator() . uniqid();
	}
	/**
	*
	* @param string $transaction_id  with random id suffix e.g. "YOUR_SHOP_ORDER_ID---5187b98ac1c5e"
	* @return object  {transaction_id:string, random_suffix:string}
	*/
	static function extractRandomId($transaction_id) {
		$o = new \stdClass();
		$o->transaction_id = '';
		$o->random_id = '';
		if(Config::hasIdSeparator()) {
			$parts = explode(Config::getIdSeparator(), $transaction_id);
			if(sizeof($parts) == 2) {
				list($o->transaction_id, $o->random_id) = $parts;
			} else {
				trigger_error("WARNING in \Hypercharge\Helper::extractRandomId(): no seperator found. transaction_id: '$transaction_id'");
				$o->transaction_id = $transaction_id;
			}
		} else {
			$o->transaction_id = $transaction_id;
		}
		return $o;
	}

	static function stripCc($str) {
		$replace = array(
			'/"card_number"\s*=>\s*"[^"]+"/'           => '"card_number"=>"xxxxxxxxxxxxxxxxxxxx"' // php print_r()
			,'/<card_number>([^<]+)/'                  => '<card_number>xxxxxxxxxxxxxxxxxxx'      // xml
			,'/\[card_number\]\s*=>\s*[^\s]+/'         => '[card_number] => xxxxxxxxxxxxxxxxxxx'  // php var_dump()
			,'/"card_number"\s*:\s*"[^"]+"/'           => '"card_number":"xxxxxxxxxxxxxxxxxxx"'   // json
			,'/(\\\*")?card_number(\\\*")?:(\\\*")?\d+(\\\*")?/' => '$1card_number$2:$3xxxxxxxxxxxxxxxxxxx$4' // escaped json
			,'/"cvv"\s*=>\s*"[^"]+"/'                  => '"cvv"=>"xxx"'
			,'/<cvv>[^<]+/'                            => '<cvv>xxx'
			,'/\[cvv\]\s*=>\s*[^\s]+/'                 => '[cvv] => xxx'
			,'/"cvv"\s*:\s*"[^"]+"/'                   => '"cvv":"xxx"'
			,'/(\\\*")?cvv(\\\*")?:(\\\*")?\d+(\\\*")?/' => '$1cvv$2:$3xxx$4'
			,'/"bank_account_number"\s*=>\s*"[^"]+"/'  => '"bank_account_number"=>"xxxxxxxxxxx"'
			,'/<bank_account_number>([^<]+)/'          => '<bank_account_number>xxxxxxxxxxx'
			,'/\[bank_account_number\]\s*=>\s*[^\s]+/' => '[bank_account_number] => xxxxxxxxxxx'
			,'/"bank_account_number"\s*:\s*"[^"]+"/'   => '"bank_account_number":"xxxxxxxxxxx"'
			,'/(\\\*")?bank_account_number(\\\*")?:(\\\*")?\d+(\\\*")?/' => '$1bank_account_number$2:$3xxx$4'
		);
		return preg_replace(array_keys($replace), array_values($replace), $str);
	}

}
