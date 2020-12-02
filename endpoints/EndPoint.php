<?php
chdir(__DIR__);
include_once '../database/DataBase.php';
chdir(__DIR__);
include_once 'Crud.php';
include_once 'CrudOperations.php';
chdir(__DIR__);
include_once '../utils/ServerData.php';
chdir(__DIR__);

class EndPoint extends DataBase implements Crud
{

    private string $endpoint;
    private array $db_fields;
    private array $db_table_info;
    private string $server_IP;

    public function __construct($request, $response, $actions, $db_fields, $table, $endpoint)
    {
        parent::__construct($request, $response, $table);
        if ($actions !== null) {
            $actions = array_merge(CrudOperations::operations, $actions);
        }
        $this->endpoint = $endpoint;
        $this->db_fields = $db_fields;
        $this->db_table_info = $this->getFullDBTypes();
        $server_data = new ServerData();
        $this->server_IP = $server_data->getIp();
        $action = parent::getRequest()->getValue('action');
        if ($actions != null && !in_array($action, $actions)) {
            $str_actions = '[' . implode(', ', $actions) . ']';
            parent::getResponse()->printError("La accion '$action' para el endpoint indicado no es valida, las posibles son: $str_actions", 400);
        }
    }

    protected function onFail($message)
    {
        (new Log(MessageTypes::Grave, $message))->write();
    }

    public function getIPAddress()
    {
        return $this->server_IP;
    }

    public function getEndPointName()
    {
        return $this->endpoint;
    }

    public function getDbFields()
    {
        return $this->db_fields;
    }

    public function validateDBtypes($full_array)
    {
        $db_types_array = $this->db_table_info;
        $db_types_array_keys = array_keys($db_types_array);
        foreach ($full_array as $key => $val) {
            if (in_array($key, $db_types_array_keys)) {
                if ($db_types_array[$key] === DataTypes::double && gettype($val) === DataTypes::integer) {
                    $double_val = floatval($val);
                } else {
                    $double_val = $val;
                }
                $actual_type = gettype($double_val);
                $right_type = $db_types_array[$key][0];
                if ($actual_type != $right_type) {
                    parent::getResponse()->printError("El tipo de dato para '" . $key . "' debe ser " . $right_type . " pero fue recibido '" . $actual_type . "'");
                }
                $max_length = $db_types_array[$key][1];
                if (gettype($val) === DataTypes::string) {
                    if (strlen($val) > intval($max_length)) {
                        $this->getResponse()->printError('Ha superado el límite de caracteres para: ' . $key . "(" . $max_length . ")");
                    }
                }
            }
        }
    }

    public function getFullArray()
    {
        $whitelist = $this->db_fields;
        $full_array = array();
        $input_array_content = parent::getRequest()->getAsArray();
        foreach ($input_array_content as $key => $val) {
            if ($val != "" && in_array($key, $whitelist)) {
                if ($key != "id") {
                    $full_array[$key] = $val;
                }
            } else {
                if ($val === "") {
                    $this->getResponse()->printError('Ha ingresado contenido inválido.');
                }
            }
        }
        $this->validateDBtypes($full_array);
        return $full_array;
    }

    public function addNewRecord($onError, $onSuccess)
    {
        $full_array = $this->getFullArray();
        if (is_callable($onSuccess)) {
            $full_array = $onSuccess($full_array);
        }
        $fields = array_keys($full_array);
        $data = array_values($full_array);
        if (count($data) === 0) {
            parent::getResponse()->printError('No se han detectado datos de entrada válidos para ser actualizados.');
        }
        parent::setRecords($fields, $data, $onError);
    }

    public function getAllRecords($sub_fields = null)
    {
        $fields = ($sub_fields === null) ? $this->db_fields : $sub_fields;
        $recieve_data = parent::getRecords($fields);
        if (count($recieve_data) > 0) {
            return $recieve_data;
        } else {
            parent::getResponse()->printError("Ha ocurrido un error al obtener los registros");
        }
    }

    public function getRecordsByField($campo, $data, $sub_field = null, $endpointname)
    {
        $fields = ($sub_field === null) ? $this->db_fields : $sub_field;
        $recieve_data = parent::getRecords($fields, "WHERE $campo=$data");
        if (count($recieve_data) > 0) {
            parent::getResponse()->addValue($endpointname, $recieve_data);
            parent::getResponse()->addValue('status', true);
            parent::getResponse()->printResponse();
        } else {
            parent::getResponse()->printError('No se ha podido encontrar el registro solicitado');
        }
    }

    public function deleteRecordsByField($field_condition, $identificador)
    {
        parent::deleteRecords($field_condition, $identificador);
    }

    public function updateRecordsByField($field_condition, $identificador)
    {
        $full_array = $this->getFullArray();
        $fields = array_keys($full_array);
        $data = array_values($full_array);
        if (count($data) === 0) {
            parent::getResponse()->printError('No se han detectado datos de entrada válidos para ser actualizados.', 400);
        }
        parent::updateRecords($fields, $data, $field_condition, $identificador);
    }
}