<?php
chdir(__DIR__);
include_once '../api/Request.php';
chdir(__DIR__);
include_once '../api/Response.php';
chdir(__DIR__);

class NoDbEndPoint
{
    private Request $request;
    private Response $response;

    public function __construct(Request $request, Response $response, $actions)
    {
        $this->request = $request;
        $this->response = $response;
        $action = $request->getValue('action');
        if ($actions != null && !in_array($action, $actions)) {
            $str_actions = '[' . implode(', ', $actions) . ']';
            $response->printError("La accion '$action' para el endpoint indicado no es valida, las posibles son: $str_actions", 400);
        }
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

}