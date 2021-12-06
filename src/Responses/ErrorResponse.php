<?php

declare(strict_types=1);

namespace REST\Responses;

class ErrorResponse extends JsonResponse
{
	public function __construct(\Throwable $exception)
	{
		$payload = [
			'error' => $exception->getMessage(),
			'code' => $exception->getCode(),
			'type' => \get_class($exception),
		];
		
		parent::__construct($payload);
	}
}
