<?php

namespace REST;

use Nette;
use Nette\Utils\Strings;

class Router implements \Nette\Routing\Router
{
	public const BODY_KEY = 'body';
	
	private const ACTIONS = [
		Nette\Http\IRequest::GET => 'read',
		Nette\Http\IRequest::POST => 'create',
		Nette\Http\IRequest::PATCH => 'update',
		Nette\Http\IRequest::DELETE => 'delete',
	];
	
	private const DEFAULT_ACTION = 'fallback';
	
	private const ACTION_KEY = 'action';
	
	private const SUB_ACTION_KEY = 'subAction';
	
	private const OPERATION_KEY = '_op';
	
	private const ID_KEY = 'id';
	
	private const IDS_KEY = 'ids';
	
	/**
	 * @var array<int, string>
	 */
	private ?array $noRestfullPresenter;
	
	private int $currentVersion;
	
	private string $module;
	
	/**
	 * @param string $module
	 * @param int $currentVersion
	 * @param array<string> $noRestfullPresenter
	 */
	public function __construct(string $module = 'Api', int $currentVersion = 1, ?array $noRestfullPresenter = [])
	{
		$this->noRestfullPresenter = $noRestfullPresenter;
		$this->currentVersion = $currentVersion;
		$this->module = $module;
	}
	
	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @return array<mixed>|null
	 * @throws \Nette\Utils\JsonException
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		
		$versions = \implode('|', \range(1, $this->currentVersion));
		
		$routes = [];
		
		if ($this->noRestfullPresenter === null || \count($this->noRestfullPresenter) > 0) {
			$noRestfullPresenters = $this->noRestfullPresenter === null ? '' : \implode('|', $this->noRestfullPresenter);
			$routes[] = new Nette\Application\Routers\Route("api/[v<version=1 $versions>/]<presenter $noRestfullPresenters>/<action>", ['module' => $this->module]);
		}
		
		$routes[] = new Nette\Application\Routers\Route("api/[v<version=1 $versions>/]<presenter>[/<id>][/<subAction>][/<subId>]", ['module' => $this->module]);
		
		foreach ($routes as $route) {
			$matched = $route->match($httpRequest);
			
			if (!$matched) {
				continue;
			}
			
			if ($httpRequest->getRawBody()) {
				$jsonBody = Nette\Utils\Json::decode($httpRequest->getRawBody());
				
				if (isset($jsonBody->{self::OPERATION_KEY})) {
					$operation = $jsonBody->{self::OPERATION_KEY};
					unset($jsonBody->{self::OPERATION_KEY});
				}
				
				$matched[self::BODY_KEY] = new InputBody($jsonBody);
			}

			if ($httpRequest->getMethod() === Nette\Http\IRequest::POST && isset($jsonBody) && isset($operation)) {
				$matched += (array) $jsonBody;
				$matched[self::ACTION_KEY] = $operation;
			} else {
				$matched[self::ACTION_KEY] = !isset($matched[self::ACTION_KEY]) ?
					$this->mapAction($httpRequest->getMethod()) . Strings::firstUpper($matched[self::SUB_ACTION_KEY] ?? '') :
					$this->mapAction($httpRequest->getMethod()) . Strings::firstUpper($matched[self::ACTION_KEY]);
			}
			
			if (isset($matched[self::ID_KEY]) && \is_string($matched[self::ID_KEY])) {
				$matched[self::IDS_KEY] = [$matched[self::ID_KEY]];
			}
			
			return $matched;
		}
		
		return null;
	}
	
	/**
	 * @param array<string|int, mixed> $params
	 * @param \Nette\Http\UrlScript $refUrl
	 */
	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		unset($params, $refUrl);
		
		return null;
	}
	
	public function isApiRequest(Nette\Application\Request $appRequest): bool
	{
		return (Nette\Application\Helpers::splitName($appRequest->getPresenterName())[0] ?? null) === $this->module;
	}
	
	private function mapAction(string $method): string
	{
		return self::ACTIONS[$method] ?? self::DEFAULT_ACTION;
	}
}
