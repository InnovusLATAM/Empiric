<?php

class Response
{
    private $response = array();
    public function __construct()
    {
        header('Content-Type:application/json;utf-8');
    }

    public function addValue($key, $value)
    {
        $this->response[$key] = $value;
    }

    public function removeValue($key)
    {
        unset($this->response[$key]);
    }

    public function printError($message = 'Error no especificado', $error_code = 500)
    {
        $this->response = array();
        $this->addValue('status', false);
        $this->addValue('message', $message);
        http_response_code($error_code);
        echo json_encode($this->response, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
        die();
    }

    public function printResponse($withDefaul = true)
    {
        if (count($this->response) > 0) {
            if ($withDefaul) {
                $this->addValue('status', true);
            }
            $this->response = array_reverse($this->response);
        } else {
            $this->addValue('status', false);
            $this->addValue('message', 'El servidor no ha mostrado una respuesta');
        }
        echo json_encode($this->response, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
        die();
    }


}