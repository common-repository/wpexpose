<?php

namespace Expose;

class Encoding
{
    /**
     * Logger instance
     * @var object
     */
    private $logger = null;
	
    /**
     * Decode data before analize
     *
     * @param array $data Data to decode
     */
	static public function prepareDataForScan($data)
	{
		if(is_array($data)) {
			foreach ($data as $c=>$v) {
				$data[$c] = self::prepareDataForScan($v);
			}
		}
		else if ( is_string($data))	{
			$data = urldecode ($data);
		}
		return $data;
	}

	
}