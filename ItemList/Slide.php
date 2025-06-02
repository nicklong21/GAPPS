<?php
namespace ElevenFingersCore\GAPPS\ItemList;

use ElevenFingersCore\Database\DatabaseConnector;
use ElevenFingersCore\ItemList\Item;
use ElevenFingersCore\Resources\ResourceImage;
use ElevenFingersCore\Resources\ResourceFactory;
use ElevenFingersCore\Utilities\UtilityFunctions;
use ElevenFingersCore\Files\FileHandlerImage;


class Slide extends Item{
    private $resource_folder_id;

    protected $type = 'slide';

    protected $related_tags = [
            'slide'=>'image',
        ];

    private $newUploadedSlideResource = 0;

    private $schema_map = [
            'id'=>'id','title'=>'title','publish'=>'publish','zorder'=>'zorder','property_id'=>'image','attr1'=>'media'
        ];

    static $template = [
        'id'=>0,
        'title'=>'',
        'media'=>'',
        'image'=>'',
        'publish'=>'',
        'zorder'=>0,
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
                $DATA[$key] = $row[$field];
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
                $row[$field] = $DATA[$key];
            }
        }
        if(!empty($this->newUploadedSlideResource)){
            $row['property_id'] = $this->newUploadedSlideResource;
        }
        return $row;
    }

    public function getForEditor(): array{
        
        $DATA = $this->getDATA();
        
        $re = [
            'id'=>$this->id,
            'itemDATA'=>$DATA,
            'related'=>[
                'slide'=>$this->getRelatedSlide()
            ],
        ];
        return $re;
    }

    public function getForList():array{
        $DATA = $this->DATA;
        $DATA['approved'] = 1;
        return $DATA;
    }

    public function getImage():?ResourceImage{
        $Resource = null;
        if(!empty($this->DATA['image'])){
            $resource_id = $this->DATA['image'];
            $Resource = ResourceFactory::InitializeResource($this->database, $resource_id);
        }
        return $Resource;
    }

    public function uploadSlideImage(){
        $Resource = new ResourceImage($this->database);
        $resource_folder = $this->getResourceFolderID();
        $Resource->setParent($resource_folder);
        $FileHandler = $Resource->getFileHandler();
        $FileHandler->setImageMax(3200);
        $FileHandler->setAutoConvertFile('image/webp');
        $FileHandler->setThumbnailDimensions(300,200);
        $success = $Resource->UploadFile('slideshow_image');
        
        if($success){
            $Resource->Save();
            $this->newUploadedSlideResource = $Resource->getID();
        }else{
            $this->addErrorMsg($Resource->getErrorMsg());
        }
    }

    public function getSlideSrc():string{
        $src = '';
        if(!empty($this->DATA['image'])){
            $Resource = ResourceFactory::InitializeResource($this->database, $this->DATA['image']);
            if(!empty($Resource->getID())){
                $src = $Resource->getURL();
            }
        }
        return $src;
    }

    private function getRelatedSlide():array{
        $related = [];
        if(!empty($this->DATA['image'])){
            $Resource = ResourceFactory::InitializeResource($this->database, $this->DATA['image']);
            if(!empty($Resource->getID())){
                $related = [
                    [
                        'reference'=>[
                            'id'=>0,
                            'resource_type'=>'image',
                            'resource_id'=>$Resource->getID(),
                            'name'=>$Resource->getName(),
                        ],
                        'resource_id'=>$Resource->getID(),
                        'type'=>'image',
                        'resource'=>$Resource->getDATA(),
                    ],
                ];
            }
        }
        
        return $related;
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

    private function saveSlideImage(string $related_post_string):int{
    
    $resource_id = 0;
    if(!empty($related_post_string)){
        $related_post_data = json_decode($related_post_string,true);
        if(!empty($related_post_data)){
            $resources = $related_post_data['resources'];
            if(!empty($resources)){
                $related_resource = current($resources);
                $resource_id = $related_resource['resource_id'];
                if(!empty($related_resource['imgBase64'])){
                    $resource_id = $this->saveBase64Upload($related_resource['imgBase64'],$related_resource['name']);
                }
            }
        }
    }
    return $resource_id;
    }

    public function setResourceFolderID(int $root_id){
        $name = 'slideshow';
        $ArtworkImageFolder = ResourceFactory::findResource($this->database, $name, $root_id,'folder' ,false);
        if(!$ArtworkImageFolder){
            $ArtworkImageFolder = ResourceFactory::InitializeResource($this->database,0,[
                'name'=>$name,
                'type'=>'folder',
                'parent'=>$root_id,
                'owner'=>0,
                'date'=>time(),
            ]);
            $ArtworkImageFolder->Save();
        }
        $folder_id = $ArtworkImageFolder->getID();
        
        $this->resource_folder_id = $folder_id;
    }

    public function getResourceFolderID():int{

        return $this->resource_folder_id;
    }


    private function saveBase64Upload(string $imgBase64, string $name):int{

        $resource_id = 0;
        $folder_id = $this->getResourceFolderID();
        $FileHandler = new FileHandlerImage();
        $FileHandler->setAutoConvertFile('image/webp');
        $options = [
            'name'=>$name,
        ];
        $success = $FileHandler->saveFromBase64($imgBase64,$options);
        if($success){
            $ResourceImage = new ResourceImage($this->database);
            $ResourceImage->setFileHandler($FileHandler);
            $DATA = [
                'parent'=>$folder_id,
                'access'=>0,
                'date'=>time(),
            ];
            $success = $ResourceImage->Save($DATA);
            if($success){
                
                $resource_id = $ResourceImage->getID();
            }else{
                $this->addErrorMsg($ResourceImage->getErrorMsg());
            }
        }else{
            $this->addErrorMsg($FileHandler->getErrorMsg());
        }
        return $resource_id;
    }

}