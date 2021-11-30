<?php

declare(strict_types=1);

namespace REST;

use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\UI\Component;

abstract class Presenter extends Component implements IPresenter
{
	/** @inject */
	public \Nette\Http\Request $httpRequest;
	
	public function run(\Nette\Application\Request $request): Response
	{
		$this->loadState($request->getParameters());
		
		return $this->call($request->getParameter('action') ?? 'default', $request->getParameters());
	}
	
	/**
	 * @param string $method
	 * @param mixed[] $params
	 */
	protected function call(string $method, array $params): \REST\Responses\JsonResponse
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
