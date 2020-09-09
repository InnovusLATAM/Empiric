<?php
chdir(__DIR__);
include_once 'Response.php';
chdir(__DIR__);
include_once '../debug/Log.php';
chdir(__DIR__);
include_once '../debug/MessageTypes.php';
chdir(__DIR__);
include_once '../api/DataTypes.php';
chdir(__DIR__);
include_once '../config.php';
chdir(__DIR__);
require '../vendor/autoload.php';
chdir(__DIR__);
include_once 'Roles.php';
chdir(__DIR__);

use Firebase\JWT\JWT;

class Request
{
    private array $request = array();
    private array $headers = array();
    private Response $response;
    private string $sql_query;

    public function __construct($request)
    {
        $this->request = $request;
        $this->response = new Response();
        $this->headers = getallheaders();
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCookies(): array
    {
        return $_COOKIE;
    }
    public function getToken():string {
        return $_COOKIE['token'];
    }
    public function getSecretKey():string {
        global $token_settings;
        return $token_settings['loginSecretKey'];
    }
    public function getUsername():string{
        try {
        global $token_settings;
        $token = $_COOKIE['token'];
        $payload = JWT::decode($token, $token_settings['loginSecretKey'], array('HS256'));
        return @$payload->user_data->usuario;
        } catch (Exception $e) {
            $this->response->printError('Autentificación Invalida: ' . $e->getMessage(), 401);
        }
    }

    public function setSQLQuery($sql_query){
        $this->sql_query = $sql_query;
    }
    public function getSQLQuery():string {
        return $this->sql_query;
    }
    public function checkAdminToken()
    {
        if (!isset($_COOKIE['token'])) {
            $this->response->printError('Usuario no autentificado', 401);
        }
        global $token_settings;
        $token = $_COOKIE['token'];
        try {
            $payload = JWT::decode($token, $token_settings['loginSecretKey'], array('HS256'));
            if (@$payload->user_data->rol !== Roles::ADMIN) {
                $this->response->printError('No tienes permiso para hacer esto', 401);
            }
        } catch (Exception $e) {
            $this->response->printError('Autentificacion Invalida: ' . $e->getMessage(), 401);
        }
    }

    public function getAsArray():array
    {
        return $this->request;
    }

    public function getValue($key)
    {
        if (!isset($this->request[$key])) {
            $this->response->printError("Internal Server Error, el campo '$key' no existe en la peticion");
        }
        return $this->request[$key];
    }

    public function setValue($key, $value)
    {
        $this->request[$key] = $value;
    }

    public function validateDataTypes($array, $die_on_empty_field = false, $not_required = array())
    {
        $message = '';
        $error = false;
        foreach ($array as $key => $value) {
            if (!isset($this->request[$key])) {

                $message = "El campo '$key' no se ha encontrado en la solicitud";
                $error = true;
                break;

            } else {
                $datatype = gettype($this->request[$key]);
                if ($value == DataTypes::number && ($datatype == DataTypes::integer || $datatype == DataTypes::double)) {
                    $datatype = DataTypes::number;
                }
                if ($datatype != $value) {
                    $message = "El tipo de dato del campo '$key' en la solicitud debe ser '$value' pero fue recibido '$datatype'";
                    $error = true;
                    break;
                }
                if ($datatype == DataTypes::array && count($this->request[$key]) == 0) {
                    $message = "El campo $key en la solicitud esta vacio, no se puede proceder";
                    $error = true;
                    break;
                }
                if ($datatype == DataTypes::string && $die_on_empty_field &&/* !in_array($key, $exluce_empty) &&*/ $this->request[$key] == '') {
                    $message = "El campo '$key' en la solicitud tiene un string vacio";
                    $error = true;
                    break;
                }
            }
        }
        if ($error) {
            //(new Log(MessageTypes::Grave, $message))->write();
            $this->response->printError($message, 400);
        }
    }

    public function checkArrayDataTypes($key, $datatypes)
    {
        if (isset($this->request[$key]) && gettype($this->request[$key]) === DataTypes::array && count($this->request[$key]) !== 0) {
            foreach ($this->request[$key] as $value) {
                $type = gettype($value);
                if (!in_array($type, $datatypes)) {
                    (new Log(MessageTypes::Grave, "Todos los valores del arreglo '$key' deben ser string, el valor introducido '$value' es de tipo $type"))->write();
                }
            }
            return true;
        }
        (new Log(MessageTypes::Grave, "El arreglo '$key' esta vacio"))->write();
    }

    public function checkRecieveOptions($key, $fields)
    {
        if (isset($this->request[$key]) && gettype($this->request[$key]) == DataTypes::array && count($this->request[$key]) !== 0) {
            foreach ($this->request[$key] as $value) {
                $type = gettype($value);
                if ($type != DataTypes::string) {
                    if ($type == DataTypes::array) {
                        $value = implode(', ', $value);
                    }
                    (new Log(MessageTypes::Grave, "Todos los valores del arreglo '$key' deben ser string, el valor introducido '$value' es de tipo $type"))->write();
                }
                if (!in_array($value, $fields)) {
                    $str_fields = '[' . implode(', ', $fields) . ']';
                    $this->response->printError("El campo '$value' que desea recivir no es valido, las opciones para este endpoint son: $str_fields", 400);
                }
            }
            return true;
        }
        return false;
    }

    public function checkArrayOneType($key, $dataType)
    {
        if (isset($this->request[$key]) && gettype($this->request[$key]) == DataTypes::array) {
            foreach ($this->request[$key] as $val) {
                $type = gettype($val);
                if ($type != $dataType) {
                    $this->response->printError("El campo '$key' debe ser un array que contenga solo datos de tipo '$dataType' ", 400);
                }
            }
        }
    }

    public function roundDecimal(string $key, int $decimal)
    {
        if (isset($this->request[$key])) {
            $valtype = gettype($this->getValue($key));
            if ($valtype === DataTypes::integer || $valtype === DataTypes::double) {
                $this->setValue($key, (double)number_format($this->getValue($key), $decimal, '.', ''));
            }
        }
    }

}




