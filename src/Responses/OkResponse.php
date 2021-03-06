<?php

declare(strict_types=1);

namespace REST\Responses;

class OkResponse extends JsonResponse
{
	/**
	 * @param mixed $result
	 */
	public function __construct($result)
	{
		$payload = [
			'result' => $result,
		];
		
		parent::__construct($payload);
	}
}
