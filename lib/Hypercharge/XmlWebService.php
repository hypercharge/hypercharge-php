<?php
namespace Hypercharge;

class XmlWebservice {

	/**
	* @param Hypercharge\IUrl $url
	* @param Hypercharge\IRequest $request
	* @return Hypercharge\IResponse
	* @throws Hypercharge\Error
	*/
	public function call(IUrl $url, IRequest $request) {
		$curl = Config::getFactory()->createHttpsClient(Config::getUser(), Config::getPassword());
		$serializer = new XmlSerializer();
		$responseStr = $curl->xmlPost($url->get(), $serializer->toXml($request));
		$responseDom = new \SimpleXMLElement($responseStr);
		return $request->createResponse(XmlSerializer::dom2hash($responseDom));
	}

}
