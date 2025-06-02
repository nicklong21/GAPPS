<?php
namespace ElevenFingersCore\GAPPS\ItemList;
use ElevenFingersCore\Database\DatabaseConnector;
use ElevenFingersCore\ItemList\Item;

class Official extends Item{
    protected $type = 'official';

    protected $related_tags = [
        'schools'=>'list',
    ];

    private $schema_map = [
            'id'=>'id','title'=>'name','meta_title'=>'association','text'=>'full_address','excerpt'=>'schools','attr1'=>'varsity','publish'=>'publish','attr2'=>'sport'
        ];

    private $Sports = [];

    static $template = [
        'id'=>0,
        'name'=>'',
        'association'=>'',
        'publish'=>'',
        'sport'=>'',
        'varsity'=>'',
        'address'=>'',
        'city'=>'',
        'state'=>'',
        'zip'=>'',
        'email'=>'',
        'phone'=>'',
    ];


    public function __construct(DatabaseConnector $DB, ?Int $id = 0, ?Array $DATA = array(), ?Array $options = array()){
        $this->database = $DB;
        $this->DATA = static::$template;
        if($id){
            $args = array('id'=>$id);
            $DATA = $this->database->getArrayByKey(static::$table_name,$args);
        }
        $this->mapDataRowToTemplate($DATA);
        $this->id = $this->DATA['id'];

    }

    protected function mapDataRowToTemplate(array $row){
        $DATA = array_merge(static::$template,$this->DATA,);
        $data_schema = $this->schema_map;
        foreach($data_schema AS $field=>$key){
            if(array_key_exists($field,$row)){
                if($key == 'full_address'){
                    
                    $address_ar = json_decode($row[$field],true);
                    $DATA['address'] = $address_ar['address']??'';
                    $DATA['city'] = $address_ar['city']??'';
                    $DATA['state'] = $address_ar['state']??'';
                    $DATA['zip'] = $address_ar['zip']??'';
                    $DATA['email'] = $address_ar['email']??'';
                    $DATA['phone'] = $address_ar['phone']??'';
                }else if($key == 'schools'){
                    $DATA['schools'] = json_decode($row[$field],true);
                }else{
                    $DATA[$key] = $row[$field];
                }   
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
                if($key=='full_address' || $key == 'schools'){
                    $value = json_encode($DATA[$key]);
                }else{
                    $value = $DATA[$key];
                }
                $row[$field] = $value;
            }
        }
        if(array_key_exists('address',$DATA)){
            $full_address = [
                'address'=>$DATA['address'],
                'city'=>$DATA['city'],
                'state'=>$DATA['state'],
                'zip'=>$DATA['zip'],
                'email'=>$DATA['email'],
                'phone'=>$DATA['phone'],
            ];
            $row['text'] = json_encode($full_address);
        }
        $schools = [];
        $schools_string = isset($DATA['related_schools'])?$DATA['related_schools']:'';
        if(!empty($schools_string)){
            $schools = json_decode($schools_string,true);
            if(!empty($schools) && is_array($schools)){
                $schools = array_unique($schools);
                asort($schools);
            }
        }
        $row['excerpt'] = json_encode(array_values($schools));
        return $row;
    }

    public function getForEditor(): array{
        
        $DATA = $this->getDATA();
        
        $re = [
            'id'=>$this->id,
            'itemDATA'=>$DATA,
            'related'=>[
                'schools'=>$this->getSchools(),
            ],
        ];
        return $re;
    }

    public function getForList():array{
        $DATA = $this->DATA;
        $DATA['title'] = $this->getName();
        $DATA['astrisk'] = $this->getAssociation();
        $DATA['approved'] = 1;
        $item = $DATA;
        $item['data'] = $DATA;
        return $item;
    }

    public function Save(array $DATA):bool{
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
            $success = $this->database->insertArray(static::$table_name,$insert_data,'id');
            if($success){
                $this->mapDataRowToTemplate($insert_data);
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

    public function getName():string{
        return $this->DATA['name'];
    }

    public function getAssociation():string{
        return $this->DATA['association'];
    }

    public function getAddress():string{
        return $this->DATA['address'];
    }

    public function getCity():string{
        return $this->DATA['city'];
    }

    public function getState():string{
        return $this->DATA['state'];
    }

    public function getZip():string{
        return $this->DATA['zip'];
    }

    public function getEmail():string{
        return $this->DATA['email'];
    }

    public function getPhone():string{
        return $this->DATA['phone'];
    }

    public function getVarsity():string{
        return $this->DATA['varsity'];
    }

    public function getSportGroup():string{
        return $this->DATA['sport'];
    }

    public function setSportsList(array $Sports){
        $this->Sports = $Sports;
    }

    public function getSchools():array{
        return !empty($this->DATA['schools'])?$this->DATA['schools']:[];
    }
    

    
}