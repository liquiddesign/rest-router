<?php

declare(strict_types=1);

namespace REST;

use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Component;

abstract class Presenter extends Component implements IPresenter
{
	/** @inject */
	public \Nette\Http\Request $httpRequest;
	
	public function run(\Nette\Application\Request $request): Response
	{
		$return = $this->call($request->getParameter('action'), $request->getParameters());
		
		return new JsonResponse($return);
	}
	
	/**
	 * @param string $method
	 * @param mixed[] $params
	 * @return mixed[]
	 */
	protected function call(string $method, array $params): array
	{
		$rc = $this->getReflection();
		
		if (!$rc->hasMethod($method)) {
			throw new \Nette\InvalidStateException('Method not exists ' . $method . '().');
		}
		
		$rm = $rc->getMethod($method);
		
		if ($rm->isPrivate() || $rm->isAbstract() || $rm->isStatic()) {
			throw new \Nette\InvalidStateException('Cannot call method ' . $rm->getName() . '().');
		}
			
		try {
			$args = $rc->combineArgs($rm, $params);
		} catch (\Nette\InvalidArgumentException $e) {
			throw new \Nette\Application\BadRequestException($e->getMessage());
		}
		
		try {
			return $rm->invokeArgs($this, $args);
		} catch (\ReflectionException $e) {
			throw new \Nette\Application\BadRequestException($e->getMessage());
		}
	}
}
