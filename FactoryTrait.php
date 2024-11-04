<?php
namespace ElevenFingersCore\GAPPS;

use ElevenFingersCore\Database\DatabaseConnectorPDO;

Trait FactoryTrait{

    protected $database;
    protected $item_class;

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
            $insert['id'] = $id;
            $DB = $this->getDatabaseConnector();
            $DB->insertArray($this->db_table, $insert, 'id');
        }
        return $insert;
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
}




