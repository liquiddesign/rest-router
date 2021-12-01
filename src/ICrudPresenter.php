<?php

declare(strict_types=1);

namespace REST;

use REST\Responses\ListResponse;
use REST\Responses\OkResponse;

interface ICrudPresenter
{
	/**
	 * @param string[]|int[] $ids
	 */
	public function actionRead(array $ids): ListResponse;
	
	/**
	 * @param string[]|int[] $ids
	 */
	public function actionDelete(array $ids): OkResponse;
	
	public function actionCreate(InputBody $body): OkResponse;
	
	/**
	 * @param string[]|int[] $ids
	 * @param \REST\InputBody $body
	 */
	public function actionUpdate(array $ids, InputBody $body): OkResponse;
}
