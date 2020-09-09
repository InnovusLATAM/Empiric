<?php
chdir(__DIR__);
include_once 'Request.php';
chdir(__DIR__);
include_once 'Response.php';
chdir(__DIR__);
include_once '../endpoints/admin/Imagenes.php';
chdir(__DIR__);
include_once '../endpoints/admin/Videos.php';
chdir(__DIR__);
include_once '../config.php';
$request = new Request(array('action' => 'no_value'));
$response = new Response();
if (isset($_GET['type']) && isset($_GET['name'])) {
    $type = $_GET['type'];
    $name = $_GET['name'];
    if ($type === 'imagen') {
        $imagenes = new Imagenes($request, $response, false);
        $query = $imagenes->getRecords(array('uuid', 'ruta'), "WHERE uuid='$name'");
        if (count($query) == 0) {
            $response->printError('No se ha encontrado el recurso', 404);
        }
        $name = $query[0]['uuid'];
        $extension = explode('.', $name)[1];
        $ruta = $query[0]['ruta'];
        header("Content-Type:image/$extension");
        echo file_get_contents($ruta . '/' . $name);
        die();
    } else if ($type === 'video') {
        $videos = new Videos($request, $response, false);
        $query = $videos->getRecords(array('uuid', 'ruta'), "WHERE uuid='$name'");
        if (count($query) == 0) {
            $response->printError('No se ha encontrado el recurso', 404);
        }
        $name = $query[0]['uuid'];
        $extension = explode('.', $name)[1];
        $ruta = $query[0]['ruta'];
        header("Content-Type:video/$extension");
        echo file_get_contents($ruta . '/' . $name);
        die();
    } else if ($type === 'thumbnail') {
        global $media_path;
        header("Content-Type:image/png");
        echo file_get_contents($media_path['thumnails'] . '/' . $name);
        die();
    } else if ($type === 'assets') {
        header("Content-Type:image/png");
        global $media_path;
        echo file_get_contents($media_path['assets'] . '/' . $name);
        die();
    }
}
$response->printError('Petición incorrecta', 400);


