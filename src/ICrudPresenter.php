<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\ListResponse;
use REST\Responses\OkResponse;

interface ICrudPresenter
{
	/**
	 * @param array<string>|array<int> $ids
	 */
	public function actionRead(array $ids): ListResponse;
	
	/**
	 * @param array<string>|array<int> $ids
	 */
	public function actionDelete(array $ids): OkResponse;
	
	public function actionCreate(InputBody $body): OkResponse;
	
	/**
	 * @param array<string>|array<int> $ids
	 * @param \REST\InputBody $body
	 */
	public function actionUpdate(array $ids, InputBody $body): OkResponse;
}
