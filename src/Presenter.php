<?php

declare(strict_types=1);

namespace REST;

use Grid\Datalist;
use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\UI\Component;
use Nette\ComponentModel\IComponent;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Processor;
use Nette\Security\AuthenticationException;
use REST\Responses\JsonResponse;
use REST\Responses\OkResponse;

abstract class Presenter extends Component implements IPresenter
{
	/** @inject */
	public \Nette\Http\Request $httpRequest;
	
	/**
	 * Called on default by OPTIONS HTTP method
	 */
	public function checkRequest(): OkResponse
	{
		return new OkResponse(true);
	}
	
	/**
	 * @throws \ReflectionException
	 * @throws \Nette\Security\AuthenticationException
	 */
	public function run(\Nette\Application\Request $request): Response
	{
		$this->loadState($request->getParameters());
		
		return $this->call($request->getParameter('action') ?? 'default', $request->getParameters());
	}
	
	/**
	 * @param string $method
	 * @param mixed[] $params
	 * @throws \ReflectionException
	 * @throws \Nette\Security\AuthenticationException
	 */
	protected function call(string $method, array $params): \REST\Responses\JsonResponse
	{
		$rc = $this->getReflection();
		$method = \ucfirst($method);
		
		$globalAuthorizeMethod = "authorize";
		$authorizeMethod = "authorize$method";
		$validateMethod = "validate$method";
		$actionMethod = "action$method";
		
		// call authorizes method
		foreach ([$globalAuthorizeMethod, $authorizeMethod] as $method) {
			if ($rm = $this->isMethodCallable($rc, $method, 'bool')) {
				if (!$rm->invokeArgs($this, [$this->httpRequest])) {
					throw new AuthenticationException('Permission denied');
				}
			}
		}
		
		// call validate method
		if (($rm = $this->isMethodCallable($rc, $validateMethod, Structure::class)) && isset($params['body'])) {
			/** @var \Nette\Schema\Elements\Structure $structure */
			$structure = $rm->invokeArgs($this, [$this->httpRequest]);
			$processor = new Processor();
			
			$processor->process($structure, $params['body']);
		}
		
		/** @var \Nette\Application\UI\MethodReflection $rm */
		$rm = $this->isMethodCallable($rc, $actionMethod, JsonResponse::class, true);
		
		// call action method
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
	
	protected function validateChildComponent(IComponent $child): void
	{
		if ($child instanceof Datalist) {
			$child->monitor(Presenter::class, function (Presenter $presenter) use ($child): void {
				$child->loadState($this->getParameters());
				\Nette\Utils\Arrays::invoke($child->onAnchor, $this);
			});
		}
	}
	
	private function isMethodCallable(\ReflectionClass $rc, string $method, string $type, bool $throw = false): ?\ReflectionMethod
	{
		if (!$rc->hasMethod($method)) {
			if (!$throw) {
				return null;
			}
			
			throw new \Nette\Application\BadRequestException('Method not exists ' . $method . '()');
		}
		
		$rm = $rc->getMethod($method);
		
		if ($rm->isPrivate() || $rm->isAbstract() || $rm->isStatic()) {
			throw new \Nette\InvalidStateException('Cannot call method ' . $method . '()');
		}
		
		/** @var \ReflectionNamedType|null $returnType */
		$returnType = $rm->getReturnType();
		
		if ($returnType !== null && ($returnType->getName() !== $type && !\is_subclass_of($returnType->getName(), $type))) {
			throw new \Nette\InvalidStateException('Method ' . $method . '() has invalid type. Correct type is ' . $type);
		}
		
		return $rm;
	}
}
