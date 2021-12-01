<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\ListResponse;
use REST\Responses\OkResponse;

interface ICRUD
{
	public function actionRead(): ListResponse;
	
	public function actionDelete(): OkResponse;
	
	public function actionCreate(): OkResponse;
	
	public function actionUpdate(): OkResponse;
}
