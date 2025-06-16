<?php
namespace ElevenFingersCore\GAPPS;

use ElevenFingersCore\Database\DatabaseConnectorPDO;

Trait FactoryTrait{

    protected $database;
    protected $item_class;
    protected array $customFilters = [];
    protected array $nullableFields = [];

    protected function getItem(?int $id = null, ?array $DATA = array()){
        if(!empty($id)){
            $DB = $this->getDatabaseConnector();
            $DATA = $DB->getArrayByKey($this->db_table, ['id'=>$id]);
            }
        $item_data = [];
        foreach($this->schema AS $key=>$val){
            $item_data[$key] = $DATA[$key]??$val;
        }
        $item_class = $this->getItemClass();
        $Item = new $item_class($item_data);
        return $Item;
    }

    protected function getDatabaseConnector():DatabaseConnectorPDO{
        return $this->database;
    }

    public function getItemClass():string{
        return $this->item_class;
    }

    protected function setItemClass(string $classname){
        $this->item_class = $classname;
    }

    protected function saveItem(array $DATA, int $id):array{
        $insert = [];
        foreach($this->schema AS $key=>$val){
            if(isset($DATA[$key])){
                $insert[$key] = $DATA[$key]??$val;
            }
        }
        if(!empty($insert)){
            $mapped_data = $this->mapDATAForSave($insert);
            $mapped_data['id'] = $id;
            $DB = $this->getDatabaseConnector();
            $DB->insertArray($this->db_table, $mapped_data, 'id');
        }
        return $mapped_data;
    }

    protected function copyItem($Item, array $new_data){
        $old_data = $Item->getDATA();
        $data = array_merge($old_data, $new_data);
        $insert = $this->saveItem($data,0);
        $Copy = $this->getItem(null, $insert);
        return $Copy;
    }

    protected function deleteItem(int $id):bool{
        $DB = $this->getDatabaseConnector();
        return $DB->deleteByKey($this->db_table, ['id'=>$id]);
    }

    protected function mapDATAForSave(array $data):array{
        $schema = $this->schema;
        $return = [];
        foreach($schema AS $field=>$default){
            if(array_key_exists($field, $data)){
                $value = $this->transformValueByDefault($field,$data[$field],$default);
                $return[$field] = $value;
            }
        }
        return $return;
    }

    protected function transformValueByDefault(string $field, mixed $value, mixed $default): mixed {
        if (array_key_exists($field, $this->nullableFields) && $value === null) {
            return null;
        }

        if (isset($this->customFilters[$field])) {
            return call_user_func($this->customFilters[$field], $value);
        }

        if(empty($value) && is_null($default)){
            return null;
        }

        // Type-based fallback logic
        if (is_int($default)) {
            return $this->toInteger($value);
        } elseif (is_bool($default)) {
            return $this->toBoolean($value);
        } elseif (is_string($default)) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $default)) {
                return $this->toDateString($value, strlen($default) > 10 ? 'Y-m-d H:i:s' : 'Y-m-d');
            }
            return $this->sanitizeString($value);
        }

        return $value; // fallback to raw
    }

    /**
     * Example helper: safely trim and normalize string values.
     */
    protected function sanitizeString(?string $value): ?string {
        return isset($value) ? trim($value) : '';
    }

    /**
     * Example helper: coerce boolean values from common input formats.
     */
    protected function toBoolean(mixed $value): bool {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    /**
     * Example helper: ensure integer conversion, null-safe.
     */
    protected function toInteger(mixed $value): ?int { 
        return is_numeric($value) ? (int) $value : 0;
    }

    protected function toDateString(mixed $value, string $format = 'Y-m-d H:i:s'): ?string {
        if (empty($value)) return null;
        try {
            $dt = new \DateTime($value);
            return $dt->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Register a custom filter callback for a specific field.
     *
     * @param string $field
     * @param callable $callback
     */
    public function registerCustomFilter(string $field, callable $callback): void {
        $this->customFilters[$field] = $callback;
    }

    /**
     * Mark a field as nullable (skip transformation if value is null).
     *
     * @param string $field
     */
    public function markFieldNullable(string $field): void {
        $this->nullableFields[$field] = true;
    }
}




