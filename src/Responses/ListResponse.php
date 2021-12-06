<?php

declare(strict_types=1);

namespace REST\Responses;

use Datalist\Datalist;

class ListResponse extends JsonResponse
{
	public function __construct(Datalist $datalist)
	{
		$payload = [
			'items' => $datalist->getItemsOnPage(),
			'totalItemCount' => $datalist->getPaginator()->getItemCount(),
		];
		
		parent::__construct((string) $payload);
	}
}
