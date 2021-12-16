<?php

declare(strict_types=1);

namespace REST;

use Nette\Application\Request;

class Helpers
{
	public static function getExceptionFromRequest(Request $request, string $module = 'Api'): ?\Throwable
	{
		if ($request->getParameter('request') instanceof Request) {
			$moduleLink = $request->getParameter('request')->getPresenterName();
			
			if ((\Nette\Application\Helpers::splitName($moduleLink)[0] ?? null) === $module) {
				if ($request->getParameter('exception') instanceof \Throwable) {
					return $request->getParameter('exception');
				}
			}
		}
		
		return null;
	}
}
