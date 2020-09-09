<?php
chdir(__DIR__);
include_once '../config.php';
chdir(__DIR__);
include_once 'Response.php';
chdir(__DIR__);
include_once 'Request.php';
chdir(__DIR__);
include_once '../debug/Log.php';
chdir(__DIR__);
include_once '../debug/MessageTypes.php';
chdir(__DIR__);
include_once 'DataTypes.php';
chdir(__DIR__);
include_once 'EndPoints.php';
global $api_data;

$response = new Response();

$petition = $_SERVER['REQUEST_METHOD'];
if ($petition != 'POST') {
    (new Log(MessageTypes::alerta, "Se rechazo tu peticion $petition, solo se permiten peticiones POST"))->write();
    $server_data = new ServerData();
    $response->addValue('ip_request', $server_data->getIp());
    $response->addValue('date', $server_data->getDate(true));
    $response->addValue('info', $api_data['company']);
    $response->addValue('server', 'activo');
    $response->printResponse(false);
}
$data = file_get_contents('php://input');
$array = json_decode($data, true) or (new Log(MessageTypes::Grave, 'JSON invalido:' . PHP_EOL . $data))->write() == 1;
$request = new Request($array);
$request->validateDataTypes(array('endpoint' => 'string', 'action' => 'string'), true);
$endpoint = $array['endpoint'];
$action = $array['action'];

switch ($endpoint) {
    default:
        $message = "El endpoint '$endpoint' no es valido";
        $response->printError($message, 400);
}
$response->printError('No response provided by the server', 500);