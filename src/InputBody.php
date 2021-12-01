<?php

declare(strict_types=1);

namespace REST;

class InputBody extends \stdClass
{
	public function __construct(\stdClass $payload)
	{
		foreach ((array) $payload as $property => $value) {
			$this->$property = $value;
		}
	}
}