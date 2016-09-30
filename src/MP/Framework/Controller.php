<?php

namespace MP\Framework;

/**
 * The base Controller class for MattPHP
 * @author Matt Parrett
 */
class Controller
{
    protected $request;
    protected $response;
    protected $di;

    public function _init(&$req = null, &$response = null, &$di = null)
    {
        if ($req !== null) {
            $this->request = $req;
        }
        if ($response !== null) {
            $this->response = $response;
        }
        // Dependency injection
        if ($di !== null) {
            $this->di = $di;
        }
    }

    /**
     * Response handler for 404
     */
    public function notFound()
    {
        $t = $this->di->get('templates');
        $c = $this->di->get('app_config');
        $vars = array('_req' => $this->request);

        return $this->response->end($t->render($c['not_found_template'], $vars));
    }
}
