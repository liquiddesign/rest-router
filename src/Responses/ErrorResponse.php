<?php

declare(strict_types=1);

namespace REST\Responses;

class ErrorResponse extends JsonResponse
{
	public function __construct(\Exception $exception)
	{
		$payload = [
			'error' => $exception->getCode(),
			'message' => $exception->getMessage(),
		];
		
		parent::__construct($payload);
	}
}
