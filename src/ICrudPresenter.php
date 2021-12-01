<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\ListResponse;
use REST\Responses\OkResponse;

interface ICrudPresenter
{
	public function actionRead(array $id): ListResponse;
	
	public function actionDelete(array $id): OkResponse;
	
	public function actionCreate(InputBody $body): OkResponse;
	
	public function actionUpdate(array $id, InputBody $body): OkResponse;
}
