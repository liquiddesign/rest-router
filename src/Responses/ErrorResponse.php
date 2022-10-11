<?php

declare(strict_types=1);

namespace REST\Responses;

use Nette\Schema\ValidationException;
use REST\Exception;

class ErrorResponse extends JsonResponse
{
	public function __construct(\Throwable $exception, bool $debugMode)
	{
		$showError = $debugMode || $exception instanceof Exception || $exception instanceof ValidationException;
		
		$payload = [
			'error' => $showError ? $exception->getMessage() : 'unspecified server error',
			'code' => $showError ? $exception->getCode() : 500,
			'type' => $showError ? \get_class($exception) : 'internal',
		];
		
		parent::__construct($payload);
	}
}
