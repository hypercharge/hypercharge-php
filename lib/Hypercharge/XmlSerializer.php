<?php
namespace Hypercharge;

class XmlSerializer {
	private static $mapping;
	public static $sort = false;

	function __construct(XmlMapping $mapping = null) {
		if(!$mapping) $mapping = new XmlMapping();
		self::$mapping = $mapping;
	}
	/**
	* @param Hypercharge\Serializable $o
	* @param DOMElement $parent if null a new xml document incl a root node named according to xmlClassBindings mapping
	* @return string or null. xml string if $parent was null. returns null If $parent was not null.
	*/
	function toXml(Serializable $o) {
		$root = null;
		if(method_exists($o, 'getRootName')) {
			$root = self::createDocument($o->getRootName());
		} else {
			$root = self::createDocument(self::$mapping->getRootName($o));
		}
		$doc = $root->ownerDocument;

		self::_toXml($o, $root);

		return $doc->saveXml();
	}

	static function _toXml(Serializable $o, \DOMElement $parent) {
		$keys = array();
		foreach($o as $k=>$v) {
			$keys[] = $k;
		}
		//print_r($keys);
		if(self::$sort) {
			sort($keys);
			//print_r($keys);
		}
		foreach($keys as $k) {
			$v = $o->{$k};
			if($v instanceof Serializable) {
				$child = self::addChild($parent, $k);
				self::_toXml($v, $child);
			} else if(is_array($v)) {
				$converter = self::$mapping->getConverter($k);
				if($converter) {
					$converter->toXml($v, self::addChild($parent, $k));
				}
			} else if(!is_object($v) && !is_array($v)) {
				self::addChild($parent, $k, $v);
			}
		}
	}

	/**
	* @param string $rootNodeName
	* @returns DOMElement root node
	*/
	static function createDocument($rootNodeName) {
		$doc = new \DOMDocument('1.0', 'UTF-8');
		if(Config::isSandboxMode()) $doc->formatOutput = true;
		return $doc->appendChild($doc->createElement($rootNodeName));
	}

	/**
	* @param DOMElement $parent
	* @param string $name
	* @param string $value
	*/
	static function addChild(\DOMElement $parent, $name, $value = null) {
		if($value === null) {
			return $parent->appendChild($parent->ownerDocument->createElement($name));
		} else {
			if(is_bool($value)) $value = $value ? 'true' : 'false';
			return $parent->appendChild($parent->ownerDocument->createElement($name, $value));
		}
	}

	/**
	* recursively convert xml dom into an array data structure
	*
	* node attributes are in __attributes
	* <foo a="1" b="2"><bar>xxx</bar></foo>
	* becomes
	* {
	*   foo: {
	*			__attributes: {a:1, b:2}
	*			,bar: 'xxx'
	*		}
	* }
	*
	* attributes of nodes without child nodes are dropped.
	* not perfect but not occuring in hypercharge xml stuff.
	* <foo a="1" b="2">xxx</foo>
	* becomes
	* {
	*		foo: 'xxx'
	* }
	*
	* @param SimpleXMLElement $node
	* @param boolean $root
	* @return array
	*/
	static function dom2hash(\SimpleXMLElement $node, $root = true) {
		$m = array();
		if($root) {
			$children = array();
			$m[$node->getName()] = &$children;
		} else {
			$children = &$m;
		}
		foreach($node->attributes() as $name => $elem) {
			if(!isset($children['__attributes'])) $children['__attributes'] = array();
	    $children['__attributes'][$name] = $elem->__toString();
		}
		if($node->count()) {
			foreach($node->children() as $n) {
				if(count($node->{$n->getName()}) > 1) {
					$children[$n->getName()][] = self::dom2hash($n, false);
				} else {
					$children[$n->getName()] = self::dom2hash($n, false);
				}
			}
		} elseif($root) {
			$ch = self::dom2hash($node, false);
			if(is_array($ch)) {
				$children = array_merge($children, $ch);
			} else if(!empty($ch)) {
				$children = $ch;
			}
		} else {
			return trim($node->__toString());
		}
		return $m;
	}

}