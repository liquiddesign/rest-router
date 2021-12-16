<?php

namespace REST;

use Nette;

class Router implements \Nette\Routing\Router
{
	public const BODY_KEY = 'body';
	
	private const ACTIONS = [
		'GET' => 'read',
		'POST' => 'create',
		'PATCH' => 'update',
		'DELETE' => 'delete',
	];
	
	private const DEFAULT_ACTION = 'fallback';
	
	private const ACTION_KEY = 'action';
	
	private const SUBACTION_KEY = 'subAction';
	
	private const OPERATION_KEY = 'op';
	
	private const ID_KEY = 'id';
	
	private const IDS_KEY = 'ids';
	
	/**
	 * @var string[]
	 */
	private ?array $noRestfullPresenter;
	
	private int $currentVersion;
	
	private string $module;
	
	/**
	 * @param string $module
	 * @param int $currentVersion
	 * @param string[] $noRestfullPresenter
	 */
	public function __construct(string $module = 'Api', int $currentVersion = 1, ?array $noRestfullPresenter = [])
	{
		$this->noRestfullPresenter = $noRestfullPresenter;
		$this->currentVersion = $currentVersion;
		$this->module = $module;
	}
	
	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @return mixed[]|null
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
				$matched[self::BODY_KEY] = new InputBody($jsonBody);
			}
			
			if ($httpRequest->getMethod() === 'POST' && isset($jsonBody->{self::OPERATION_KEY})) {
				$matched += (array) $jsonBody;
				$matched[self::ACTION_KEY] = Nette\Utils\Arrays::pick($matched, self::OPERATION_KEY);
				unset($matched[self::BODY_KEY]);
			}
			
			if (!isset($matched[self::ACTION_KEY])) {
				$matched[self::ACTION_KEY] = $this->mapAction($httpRequest->getMethod()) . (isset($matched[self::SUBACTION_KEY]) && $matched['subAction'] ? \ucfirst($matched['subAction']) : '');
			} else {
				$matched[self::ACTION_KEY] = $this->mapAction($httpRequest->getMethod()) . \ucfirst($matched[self::ACTION_KEY]);
			}
			
			if (isset($matched[self::ID_KEY]) && \is_string($matched[self::ID_KEY])) {
				$matched[self::IDS_KEY] = [$matched[self::ID_KEY]];
			}
			
			return $matched;
		}
		
		return null;
	}
	
	/**
	 * @param mixed[] $params
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
