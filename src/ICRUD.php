<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\ListResponse;
use REST\Responses\OkResponse;

interface ICRUD
{
	public function read(): ListResponse;
	
	public function delete(): OkResponse;
	
	public function create(): OkResponse;
	
	public function update(): OkResponse;
}
