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

	/**
	* convinience method. Throws json schema validation errors as ValidationError.
	* @param string $schemaName e.g. 'scheduler_index' - without path and sufix!
	* @param array|object $data
	* @return void
	* @throws Hypercharge\Errors\ValidationError
	*/
	static function validate($schemaName, $data) {
		$data = Helper::arrayToObject($data);

		$error = JsonSchemaValidator::validate($schemaName, $data);
		if(!empty($error)) {
			throw new Errors\ValidationError($error);
		}
	}
}