<?php
namespace Hypercharge;

class JsonSchema {

	static function check(Typed $object) {
		$mapping = new XmlMapping();
		$rootName = $mapping->getRootName($object);

		$o = new \stdClass();
		$o->$rootName = $object;

		$validator = new JsonSchemaValidator($object->getType());
		return $validator->check($o);
	}
}