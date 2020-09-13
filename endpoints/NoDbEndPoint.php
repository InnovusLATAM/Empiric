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
    private string $endpoint;

    public function __construct(Request $request, Response $response, array $actions, string $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->request = $request;
        $this->response = $response;
        $action = $request->getValue('action');
        if ($actions != null && !in_array($action, $actions)) {
            $str_actions = '[' . implode(', ', $actions) . ']';
            $response->printError("La accion '$action' para el endpoint indicado no es valida, las posibles son: $str_actions", 400);
        }
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getEndPoint(): string
    {
        return $this->endpoint;
    }

}