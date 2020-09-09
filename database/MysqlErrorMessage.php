<?php

class MysqlErrorMessage
{
    public function getMessage(mysqli $con)
    {
        switch ($con->errno) {
            case 2002:
                $message = 'No se puede conectar a la base de datos, notifique al administrador';
                break;
            case 1064:
                $message = "Error interno en la sintaxis de la consulta, notifique al proveedor";
                break;
            case 1062:
                //$rawMessage = $con->error;
                /*$matches = array();
                preg_match('\'([^\']*)\'', $rawMessage, $matches);
                print_r($matches);*/
                //$message = "No se puede ingresar el valor '$matches[0]' mas de una vez para el campo $matches[1], elimine el registro anterior con este valor";
            case 1451:
                $message = "El recurso que intenta eliminar se utiliza como dependencia en otros sectores de informacion, primero elimine aquellas dependencias para poder eliminar este recurso";
                break;
            default:
                $message = "[$con->errno]" . $con->error;
        }
        return $message;
    }

}