<?php

declare(strict_types=1);

namespace REST\Responses;

abstract class JsonResponse implements \Nette\Application\Response
{
	private const CONTENT_TYPE = 'application/json';
	
	/** @var mixed */
	protected $payload;
	
	/**
	 * @param mixed $payload
	 */
	public function __construct($payload)
	{
		$this->payload = $payload;
	}
	
	/**
	 * Sends response to output.
	 */
	public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse): void
	{
		unset($httpRequest);
		
		$httpResponse->setContentType(self::CONTENT_TYPE, 'utf-8');
		echo \Nette\Utils\Json::encode($this->payload);
	
	}
}



