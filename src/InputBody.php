<?php

declare(strict_types=1);

namespace REST;

class InputBody extends \stdClass
{
	public function __construct(?\stdClass $payload = null)
	{
		if ($payload === null) {
			return;
		}
		
		foreach ((array) $payload as $property => $value) {
			$this->$property = $value;
		}
	}
	
	/**
	 * @return array<int|string, mixed>
	 */
	public function getAdditionalValidation(): array
	{
		return [];
	}
}
