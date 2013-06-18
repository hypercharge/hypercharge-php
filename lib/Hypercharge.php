<?php
namespace Hypercharge;

if(!class_exists('SimpleXMLElement')) {
	throw new Exception('Hypercharge needs SimpleXML php extension!');
}
if(!function_exists('curl_init')) {
	throw new Exception('Hypercharge needs curl php extension!');
}
if(!function_exists('hash_algos')) {
	throw new Exception('Hypercharge needs hash functions php extension - PHP 5 >= 5.1.2, PECL hash >= 1.1!');
}
if(!in_array('sha512', hash_algos())) {
	throw new Exception('Hypercharge needs sha512 hash algorithm!');
}

