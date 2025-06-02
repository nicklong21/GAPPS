<?php
namespace ElevenFingersCore\GAPPS\ItemList;

use ElevenFingersCore\ItemList\Item;

class TournamentBracket extends Item{


    protected $type = 'tour-bracket';
    protected $related_tags = [
        'bracket'=>'bracket',
    ];

    protected $sport_id = 0;

    private $schema_map = ['id'=>'id','title'=>'division','meta_title'=>'region','text'=>'brackets','zorder'=>'zorder','publish'=>'publish','property_id'=>'sport_id'];

    static $template = [
        'id'=>0,
        'sport_id'=>0,
        'division'=>'',
        'region'=>'',
        'zorder'=>0,
        'brackets'=>[],
    ];


    public function __construct(\ElevenFingersCore\Database\DatabaseConnector $DB, int|null $id = 0, array|null $DATA = array(), array|null $options = array()) {
        $this->database = $DB;
        $this->DATA = static::$template;
        if($id){
            $args = ['id'=>$id];
            $DATA = $this->database->getArrayByKey(static::$table_name,$args);
        }
        $this->mapDataRowToTemplate($DATA);
        $this->id = $this->DATA['id'];
        $this->sport_id = $this->DATA['sport_id'];
    }

    protected function mapDataRowToTemplate(array $row){
        $DATA = array_merge(static::$template,$this->DATA,);
        $data_schema = $this->schema_map;
        foreach($data_schema AS $field=>$key){
            if(array_key_exists($field,$row)){
                if($key == 'brackets'){
                    $value = json_decode($row[$field],true);
                }else{
                    $value = $row[$field];
                }
                $DATA[$key] = $value;
            }
        }
        $this->DATA = $DATA;
        $this->id = $this->DATA['id'];
    }

     protected function mapTemplateToDataRow(array $DATA):array{
        $data_schema = array_flip($this->schema_map);
        $row = [];
        foreach($data_schema AS $key=>$field){
            if(array_key_exists($key,$DATA)){
                if($key == 'brackets'){
                    $value = json_encode($DATA[$key]);
                }else{
                    $value = $DATA[$key];
                }
                $row[$field] = $value;
            }
        }
        $brackets = [];
        $bracket_string = isset($DATA['related_bracket'])?$DATA['related_bracket']:'';
        if(!empty($bracket_string)){
            $brackets = json_decode($bracket_string,true);
           
        }
        $row['text'] = json_encode($brackets);
        
        return $row;
     }

     public function getForEditor(): array{
        
        $DATA = $this->getDATA();
        
        $re = [
            'id'=>$this->id,
            'itemDATA'=>$DATA,
            'related'=>[
                'bracket'=>$this->getBrackets()
            ],
        ];
        return $re;
    }

    public function setSportID(int $id){
        $this->sport_id = $id;
    }

    public function getSportID():int{
        return $this->sport_id;
    }

    public function getDivision():string{
        return $this->DATA['division'];
    }

    public function getRegion():string{
        return $this->DATA['region'];
    }

    public function getOrder():int{
        return $this->DATA['zorder'];
    }

    public function getBrackets():array{
        return !empty($this->DATA['brackets'])?$this->DATA['brackets']:[];
    }

    public function getForList():array{
        $DATA = $this->DATA;
        $DATA['approved'] = 1;
        return $DATA;
    }


    public function Save(array $DATA):bool{
        global $debug;
        $debug[] = $DATA;
        $success = false;
        $new_transaction = false;
        if(!$this->database->inTransaction()){
            $this->database->beginTransaction();
            $new_transaction = true;
        }
        try{
            $insert_data = $this->mapTemplateToDataRow($DATA);
            $insert_data['id'] = $this->id; 
            $insert_data['type'] = $this->type;  
            $insert_data['property_id'] = $this->getSportID(); 
            $debug[] = $insert_data;       
            $success = $this->database->insertArray(static::$table_name,$insert_data,'id');
            if($success){
                $this->mapDataRowToTemplate($insert_data);
                $debug[] = $this->DATA;
            }
        }catch(\Exception $e){
            if($new_transaction){
                $this->database->rollbackTransaction();
            }
            throw new \RuntimeException('Unable to Save Item: '.$e->getMessage(),0,$e);
        }

        if(!$success){
            if($new_transaction){
                $this->database->rollbackTransaction();
            }
            $this->addErrorMsg('Unable to Save Item.');
        }else if($new_transaction){
            $this->database->commitTransaction();
        }
        return $success;
    }

}