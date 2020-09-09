<?php
chdir(__DIR__);
include_once '../debug/Log.php';
chdir(__DIR__);
include_once '../debug/MessageTypes.php';
chdir(__DIR__);

class ServerData
{

    public function getDate($fulldate = false)
    {
        date_default_timezone_set('America/Mexico_City');
        if ($fulldate) {
            return date('d/m/Y H:i:s');
        }
        return date('d/m/Y');
    }

    public function getIp()
    {
        return (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    }

    public function cehckDate($date)
    {
        $date_array = explode('/', $date);
        if (count($date_array) != 3) {
            return false;
        }
        if (!is_numeric($date_array[0]) || !is_numeric($date_array[1]) || !is_numeric($date_array[2])) {
            return false;
        }
        $dia = $date_array[0];
        $mes = $date_array[1];
        $year = $date_array[2];
        return checkdate($mes, $dia, $year);
    }

    public function getDifferenceDates($date1, $date2)
    {
        try {
            $array_date_1 = explode('/', $date1);
            $array_date_2 = explode('/', $date2);
            $new_date_1 = $array_date_1[2] . '/' . $array_date_1[1] . '/' . $array_date_1[0];
            $new_date_2 = $array_date_2[2] . '-' . $array_date_2[1] . '-' . $array_date_2[0];
            $date1 = new DateTime($new_date_1);
            $date2 = new DateTime($new_date_2);
            return $date1->diff($date2)->format('%r%a');
        } catch (Exception $e) {
            (new Log(MessageTypes::Grave, 'Error interno de fechas: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()))->write();
        }
    }

    public function convertDaysToYears($days)
    {
        return (int)($days / 365);
    }

    public function checkemail($str)
    {
        return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
    }

}