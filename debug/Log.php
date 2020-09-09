<?php
chdir(__DIR__);
include_once '../debug/MessageTypes.php';
chdir(__DIR__);
include_once '../utils/ServerData.php';
chdir(__DIR__);
include_once '../api/Response.php';
chdir(__DIR__);
include_once '../config.php';
chdir(__DIR__);

class Log
{

    private string $log;
    private string $errorType;
    private ServerData $server;
    private string $message;
    private Response $response;

    public function __construct($errorType, $message)
    {
        global $log_settings;
        $this->log = $log_settings['path'];
        $this->message = $message;
        $this->response = new Response();
        $this->server = new ServerData();
        switch ($errorType) {
            case MessageTypes::alerta:
            case MessageTypes::Leve:
            case MessageTypes::Medio:
            case MessageTypes::Grave:
                $this->errorType = $errorType;
                break;
            default:
                $trace = "El tipo [$errorType] no es una valida";
                $this->writeInternal($trace);
        }
    }

    private function getFile()
    {
        $file = fopen($this->log, 'a') or die('¡No se puede abrir o crear el archivo de webapp.log revise los permisos del directorio!');
        return $file;
    }

    public function write($die = false)
    {
        $file = $this->getFile();
        $fecha = $this->server->getDate(true);
        $ip = $this->server->getIp();
        fwrite($file, "fecha [$fecha] ip [$ip] tipo [$this->errorType] :" . PHP_EOL . $this->message . PHP_EOL);
        fclose($file);
        if (MessageTypes::Grave == $this->errorType || $die) {
            http_response_code(500);
            $this->response->printError($this->message);
        }
    }

    private function writeInternal($trace)
    {
        $file = $this->getFile();
        $fecha = $this->server->getDate(true);
        $ip = $this->server->getIp();
        fwrite($file, "fecha [$fecha] ip [$ip] tipo [" . MessageTypes::Grave . "]:" . PHP_EOL . $trace . PHP_EOL);
        fclose($file);
        die('Error Grave');
    }

}