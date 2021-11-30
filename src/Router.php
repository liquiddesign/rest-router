<?php

namespace REST;

use Nette;

class Router implements \Nette\Routing\Router
{
	private const ACTIONS = [
		'GET' => 'read',
		'POST' => 'create',
		'PUT' => 'update',
		'DELETE' => 'delete',
	];
	
	private const DEFAULT_ACTION = 'default';
	
	private const ACTION_KEY = 'action';
	
	private const SUBACTION_KEY = 'subAction';
	
	private const OPERATION_KEY = 'op';
	
	private const BODY_KEY = 'body';
	
	/**
	 * @var string[]
	 */
	private array $noRestfullPresenter;
	
	private int $currentVersion;
	
	private string $module;
	
	public function __construct(array $noRestfullPresenter = [], string $module = 'Api', int $currentVersion = 1)
	{
		$this->noRestfullPresenter = $noRestfullPresenter;
		$this->currentVersion = $currentVersion;
		$this->module = $module;
	}
	
	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @return mixed[]|null
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		$noRestfullPresenters = \implode('|', $this->noRestfullPresenter);
		$versions = \implode('|', \range(1, $this->currentVersion));
		
		$routes = [
			new Nette\Application\Routers\Route("api/v<version=1 $versions>/<presenter $noRestfullPresenters>/<action>", ['module' => $this->module]),
			new Nette\Application\Routers\Route("api/v<version=1 $versions>/<presenter>[/<id>][/<subAction>][/<subId>]", ['module' => $this->module]),
		];
		
		foreach ($routes as $route) {
			$matched = $route->match($httpRequest);
			
			if (!$matched) {
				continue;
			}

			$jsonBody = \json_decode($httpRequest->getRawBody(), true);
			
			if (\json_last_error() === \JSON_ERROR_NONE) {
				$matched[self::BODY_KEY] = $jsonBody;
			} elseif ($httpRequest->getRawBody()) {
				throw new Nette\Application\BadRequestException('JSON error: #'. \json_last_error());
			}
			
			if ($httpRequest->getMethod() === 'POST' && isset($jsonBody[self::OPERATION_KEY])) {
				$matched += $jsonBody;
				$matched[self::ACTION_KEY] = Nette\Utils\Arrays::pick($matched, self::OPERATION_KEY);
				unset($matched[self::BODY_KEY]);
			}
			
			if (!isset($matched[self::ACTION_KEY])) {
				$matched[self::ACTION_KEY] = $this->mapAction($httpRequest->getMethod()) . (isset($matched[self::SUBACTION_KEY]) && $matched['subAction'] ? \ucfirst($matched['subAction']) : '');
			}
			
			return $matched;
		}
		
		return null;
	}

	public function constructUrl(array $params, Nette\Http\UrlScript $refUrl): ?string
	{
		unset($params, $refUrl);
		
		return null;
	}
	
	private function mapAction(string $method): string
	{
		return self::ACTIONS[$method] ?? self::DEFAULT_ACTION;
	}
}
