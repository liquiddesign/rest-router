<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\DataListResponse;
use REST\Responses\OkResponse;

interface ICRUD
{
	public function read(): DataListResponse;
	
	public function delete(): OkResponse;
	
	public function create(): OkResponse;
	
	public function update(): OkResponse;
}
