<?php

namespace MP\Framework;

class Controller {
	protected $request;
	protected $response;
	protected $di;

	public function _init(&$req = null, &$response = null, &$di = null) {
		// XXX: Should this just have access to the App?
		if ($req !== null)
			$this->request = $req;
		if ($response !== null)
			$this->response = $response;
		if ($di !== null)
			$this->di = $di;
	}

	function notFound()
	{
		$t = $this->di->get('templates');
		$c = $this->di->get('app_config');
		$vars = array('_req' => $this->request);

		return $this->response->end($t->render($c['not_found_template'], $vars));
	}
}