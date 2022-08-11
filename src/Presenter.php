<?php

declare(strict_types=1);

namespace REST;

use Datalist\Datalist;
use Nette\Application\ApplicationException;
use Nette\Application\IPresenter;
use Nette\Application\Response;
use Nette\Application\UI\Component;
use Nette\ComponentModel\IComponent;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Security\AuthenticationException;
use Nette\Utils\Strings;
use REST\Responses\JsonResponse;
use REST\Responses\OkResponse;

abstract class Presenter extends Component implements IPresenter
{
	/** @inject */
	public \Nette\Http\Request $httpRequest;
	
	public ?string $lang = null;

	protected bool $directLoadState = true;
	
	/**
	 * @throws \ReflectionException
	 * @throws \Nette\Security\AuthenticationException
	 */
	public function run(\Nette\Application\Request $request): Response
	{
		if ($request->getMethod() === IRequest::OPTIONS) {
			return new OkResponse('Polo');
		}
		
		$this->lang = $this->httpRequest->getHeader('Content-Language');
		
		$this->loadState($request->getParameters());
		
		return $this->call($request->getParameter('action') ?? 'default', $request->getParameters());
	}
	
	/**
	 * @param string $method
	 * @param array<mixed> $params
	 * @throws \ReflectionException
	 * @throws \Nette\Security\AuthenticationException
	 */
	protected function call(string $method, array $params): Response
	{
		$rc = $this->getReflection();
		$processor = new Processor();
		$method = Strings::firstUpper($method);
		
		$globalAuthorizeMethod = 'authorize';
		$authorizeMethod = "authorize$method";
		$validateMethod = "validate$method";
		$actionMethod = "action$method";
		
		// call authorizes method
		foreach ([$globalAuthorizeMethod, $authorizeMethod] as $method) {
			if ($rm = $this->isMethodCallable($rc, $method, 'bool')) {
				if (!$rm->invokeArgs($this, [])) {
					throw new AuthenticationException('Permission denied');
				}
			}
		}
		
		// call validate method
		if (($rm = $this->isMethodCallable($rc, $validateMethod, Structure::class)) && isset($params[Router::BODY_KEY])) {
			/** @var \Nette\Schema\Elements\Structure $structure */
			$structure = $rm->invokeArgs($this, []);
			$processor->process($structure, $params[Router::BODY_KEY]);
		}
		
		/** @var \Nette\Application\UI\MethodReflection $rm */
		$rm = $this->isMethodCallable($rc, $actionMethod, JsonResponse::class, true);
		
		// validate by body parameter
		if (isset($params[Router::BODY_KEY])) {
			try {
				$rp = new \ReflectionParameter([$this, $actionMethod], Router::BODY_KEY);
				/** @var \ReflectionNamedType|null $type */
				$type = $rp->getType();
				
				if (!$type) {
					throw new ApplicationException("Body parameter of $actionMethod() has no type");
				}
				
				$class = $type->getName();
				
				if ($class !== InputBody::class && \is_subclass_of($class, InputBody::class)) {
					$validator = new $class();
					$params[Router::BODY_KEY] = $processor->process(Expect::from($validator, $validator->getAdditionalValidation()), $params[Router::BODY_KEY]);
				}
			} catch (\ReflectionException $x) {
				throw new \Nette\Application\BadRequestException("Body is not required for method $actionMethod()", IResponse::S400_BAD_REQUEST);
			}
		}

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
		if (!$this->directLoadState) {
			return;
		}
		
		if (!($child instanceof Datalist)) {
			return;
		}

		$child->monitor(Presenter::class, function (Presenter $presenter) use ($child): void {
			$child->loadState($this->getParameters());
			\Nette\Utils\Arrays::invoke($child->onAnchor, $this);
		});
	}
	
	/**
	 * @param array<mixed> $parameteres
	 */
	protected function getEndpointUrl(array $parameteres = []): string
	{
		$url = $this->httpRequest->getUrl();

		foreach ($parameteres as $name => $value) {
			$url = $url->withQueryParameter($name, $value);
		}
		
		return $url->getAbsoluteUrl();
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
			throw new \Nette\Application\BadRequestException('Cannot call method ' . $method . '()');
		}
		
		/** @var \ReflectionNamedType|null $returnType */
		$returnType = $rm->getReturnType();
		
		if ($returnType !== null && $returnType->getName() !== 'void' && ($returnType->getName() !== $type && !\is_subclass_of($returnType->getName(), $type))) {
			throw new \Nette\InvalidStateException('Method ' . $method . '() has invalid type. Correct type is ' . $type);
		}
		
		return $rm;
	}
}
