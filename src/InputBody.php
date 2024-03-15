<?php

declare(strict_types=1);

namespace REST;

use Nette\Utils\Json;

class InputBody extends \stdClass
{
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

	public static function fromJSON(?\stdClass $payload): static
	{
		// @phpstan-ignore-next-line
		$object = new static();

		if ($payload === null) {
			return $object;
		}

		foreach ((array) $payload as $property => $value) {
			$object->$property = $value;
		}

		return $object;
	}
}
