<?php

declare(strict_types=1);

namespace REST;

use Nette\Utils\Json;

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
	
	/**
	 * @param array<string, mixed> $filterArrays
	 * @return array<mixed, mixed>
	 */
	public function toArray(array $filterArrays = []): array
	{
		$result = Json::decode(Json::encode($this), Json::FORCE_ARRAY);
		
		foreach ($filterArrays as $name => $value) {
			$result[$name] = \array_keys(\array_filter($result[$name], fn($val) => $val === $value));
		}
		
		return $result;
	}
}
