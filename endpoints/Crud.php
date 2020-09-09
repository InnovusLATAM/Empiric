<?php
interface Crud{
    public function crear($onError,$onSuccess);
    public function obtener_todos($sub_fields);
    public function obtener_by_field($campo,$data,$sub_field,$endpointname);
    public function eliminar_by_field($field_condition,$identificador);
    public function actualizar_by_field($field_condition,$identificador);
}