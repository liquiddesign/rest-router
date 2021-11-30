<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\DataListResponse;
use REST\Responses\SuccessResponse;

interface ICRUD
{
	public function read(): DataListResponse;
	
	public function delete(): SuccessResponse;
	
	public function create(): SuccessResponse;
	
	public function update(): SuccessResponse;
}
