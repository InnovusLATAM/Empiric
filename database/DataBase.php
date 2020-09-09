<?php
chdir(__DIR__);
include_once '../debug/Log.php';
chdir(__DIR__);
include_once '../debug/MessageTypes.php';
chdir(__DIR__);
include_once '../api/DataTypes.php';
chdir(__DIR__);
include_once 'MysqlErrorMessage.php';
chdir(__DIR__);
include_once '../config.php';
chdir(__DIR__);

abstract class DataBase
{
    private string $host;
    private string $database;
    private string $user;
    private string $password;
    private string $table;
    private Response $response;
    private Request $request;
    private mysqli $con;
    private MysqlErrorMessage $mysqlErrorMessage;

    public function __construct($request, $response, $table)
    {
        global $database_settings;
        $this->host = $database_settings['host'];
        $this->user = $database_settings['user'];
        $this->password = $database_settings['password'];
        $this->database = $database_settings['database'];
        $this->request = $request;
        $this->response = $response;
        $this->mysqlErrorMessage = new MysqlErrorMessage();
        $this->table = $table;
        $this->con = @new mysqli($this->host, $this->user, $this->password, $this->database);
        if ($this->con->connect_error) {
            $this->response->printError('Error al conectarse con la base de datos');
        }
        mysqli_set_charset($this->con, 'utf8');
    }

    private function getFields($field_array)
    {
        if (count($field_array) == 1 && $field_array[0] == '*') {
            return '*';
        }
        return implode(',', $field_array);
    }

    private function getData($data_array)
    {
        if (count($data_array) == 1 && $data_array[0] == '*') {
            return '*';
        }
        for ($i = 0; $i < count($data_array); $i++) {
            $data_array[$i] = $this->escapeString($data_array[$i]);
        }
        return implode("','", $data_array);
    }


    function checkEnumValue($col, $val)
    {
        $col = $this->escapeString($col);
        $res = $this->con->query("SHOW COLUMNS FROM `$this->table` LIKE '$col'");
        if (!$res->num_rows) return array();
        preg_match_all('~\'([^\']*)\'~', $res->fetch_array()['Type'], $matches);
        if (!in_array($val, $matches[1])) {
            $valids = '[' . implode(',', $matches[1]) . ']';
            $this->response->printError("El campo $col solo admite los siguientes valores: $valids", 400);
        }
    }

    protected function getFullDBTypes()
    {
        $query = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$this->database' AND TABLE_NAME = '$this->table'";
        $db_fields_array = array();
        $result = $this->con->query($query, MYSQLI_USE_RESULT);
        if ($result) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $aux_length = $row['COLUMN_TYPE'];
                preg_match_all('!\d+!', $aux_length, $matches);
                $new_length = $matches[0];
                switch ($row['DATA_TYPE']) {
                    case "enum":
                    case "text":
                    case "varchar":
                        $db_fields_array[$row['COLUMN_NAME']] = array(DataTypes::string, $row['CHARACTER_MAXIMUM_LENGTH']);
                        break;
                    case "int":
                        $db_fields_array[$row['COLUMN_NAME']] = array(DataTypes::integer, $row['CHARACTER_MAXIMUM_LENGTH'], $new_length);
                        break;
                    case "double":
                        $db_fields_array[$row['COLUMN_NAME']] = array(DataTypes::double, $row['CHARACTER_MAXIMUM_LENGTH']);
                        break;
                    default:
                        $db_fields_array[$row['COLUMN_NAME']] = array($row['DATA_TYPE'], $row['CHARACTER_MAXIMUM_LENGTH']);
                        break;
                }
            }
        }
        return ($db_fields_array);
    }


    public function getRecords($field_array, $condition = '')
    {
        $str_fields = $this->getFields($field_array);
        $query = "SELECT $str_fields FROM $this->table $condition";
        $result = $this->con->query($query, MYSQLI_USE_RESULT);
        if ($result) {
            $data = array();
            while ($row = $result->fetch_array()) {
                $aux_array = array();
                foreach ($field_array as $value) {
                    $aux_array[$value] = $row[$value];
                }
                array_push($data, $aux_array);
            }
            return $data;
        }
        (new Log(MessageTypes::Grave, $this->con->error))->write();
    }

    public function setRecords($field_array, $data_array, $onError)
    {
        $str_fields = $this->getFields($field_array);
        $str_data = $this->getData($data_array);
        $query = "INSERT INTO $this->table ($str_fields) VALUES ('$str_data')";
        $this->request->setSQLQuery($query);
        if (!$this->con->query($query, MYSQLI_USE_RESULT)) {
            if (is_callable($onError)) {
                $onError();
            }
            $this->response->printError((new MysqlErrorMessage())->getMessage($this->con));
        }
    }

    protected function setBitacoraRecords($field_array, $data_array)
    {
        $str_fields = $this->getFields($field_array);
        $str_data = $this->getData($data_array);
        $query = "INSERT INTO bitacora ($str_fields) VALUES ('$str_data')";
        if (!$this->con->query($query, MYSQLI_USE_RESULT)) {
            $this->response->printError((new MysqlErrorMessage())->getMessage($this->con));
        }
    }

    public function updateRecords($field_array, $data_array, $field_condition, $identificador)
    {
        if (!$this->existsField(array($field_condition => $identificador))) {
            $this->getResponse()->printError('El registro que desea actualizar no existe');
        }
        $str_fields = $field_array;
        $str_data = $data_array;
        $array_update_data = array();
        for ($i = 0; $i < count($str_data); $i++) {
            $array_update_data[$i] = [$str_fields[$i] . "='" . $str_data[$i] . "'"];
        }
        $string_data = implode(',', array_map(function ($el) {
            return $el[0];
        }, $array_update_data));
        $query = "UPDATE $this->table SET $string_data WHERE $field_condition = '$identificador'";
        $this->request->setSQLQuery($query);
        if (!$this->con->query($query, MYSQLI_USE_RESULT)) {
            $this->getResponse()->printError((new MysqlErrorMessage())->getMessage($this->con));
        }
    }

    public function deleteRecords($field_condition, $identificador)
    {
        /*if (!$this->existsField(array($field_condition => $identificador))) {
            $this->getResponse()->printError('El registro o uno de los registros que desea eliminar no existe');
        }*/
        $query = "DELETE FROM $this->table WHERE $field_condition = '$identificador'";
        $this->request->setSQLQuery($query);
        if (!$this->con->query($query, MYSQLI_USE_RESULT)) {
            $this->getResponse()->printError((new MysqlErrorMessage())->getMessage($this->con));
        }
        //(new Log(MessageTypes::Grave, $this->con->error))->write();
    }

    public function countAll(string $condition = ''): int
    {
        $query = "SELECT COUNT(id) as rs FROM $this->table $condition";
        $rs = $this->con->query($query);
        if (!$rs) {
            $this->response->printError((new MysqlErrorMessage())->getMessage($this->con));
        }
        return $rs->fetch_array()['rs'];
    }

    public function existsField($options)
    {
        $str = '';
        foreach ($options as $key => $val) {

            if (gettype($key) === DataTypes::string) {
                if (gettype($val) === DataTypes::string) {
                    $val = $this->escapeString($val);
                }
                $str .= $key . '=' . "'$val'" . ' ';
            } else {
                $str .= $val . ' ';
            }

        }
        $query = "SELECT * FROM $this->table WHERE $str";
        $result = $this->con->query($query);
        if ($result) {
            return $result->num_rows != 0;
        }
        (new Log(MessageTypes::Grave, $this->con->error))->write();
    }

    protected function setDBcode($field_array, $data_array)
    {
        $str_fields = $this->getFields($field_array);
        $str_data = $this->getData($data_array);
        $query = "INSERT INTO $this->table ($str_fields) VALUES ('$str_data')";
        $this->request->setSQLQuery($query);
        if (!$this->con->query($query, MYSQLI_USE_RESULT)) {
            return $this->con->errno;
        } else {
            return true;
        }
    }

    protected function getTable(): string
    {
        return $this->table;
    }

    protected function getResponse()
    {
        return $this->response;
    }

    protected function getRequest()
    {
        return $this->request;
    }

    public function getConnection()
    {
        return $this->con;
    }

    protected function getError()
    {
        return $this->con->error;
    }

    protected function getErrorNo()
    {
        return $this->con->errno;
    }

    protected function escapeString($string)
    {
        return $this->con->escape_string($string);
    }
}