<?php
interface Crud{
    public function addNewRecord($onError, $onSuccess);
    public function getAllRecords($sub_fields);
    public function getRecordsByField($campo, $data, $sub_field, $endpointname);
    public function deleteRecordsByField($field_condition, $identificador);
    public function updateRecordsByField($field_condition, $identificador);
}