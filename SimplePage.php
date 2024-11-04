<?php
namespace ElevenFingersCore\GAPPS;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;

class SimplePage{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;

    protected $group = 'default';

    static $db_table = 'list_items';
    static $template = array(
        'id'=>0,
        'title'=>'',
        'meta_title'=>'',
        'zgroup'=>'',
        'text'=>'',
        'publish'=>1,
        'type'=>'page',
    );

    public function __construct(DatabaseConnectorPDO $DB, ?int $id = null, ?array $DATA = null){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function setGroup(string $group){
        $this->group = $group;
    }

    public function getTitle():?string{
        return $this->DATA['title'];
    }

    protected function prepareForSave(?array $DATA = null):?array{
        if(!empty($DATA)){
            $DATA['type'] = 'page';
            $DATA['zgroup'] = $this->group;
        }
        if(isset($DATA['title'])){
            $DATA['meta_title'] = UtilityFunctions::MakeSafeName($DATA['title']);
        }
        return $DATA;
    }


    public static function getPageFromSlug(DatabaseConnectorPDO $DB, string $slug, ?string $group = 'default'):?SimplePage{
        $data = $DB->getArrayByKey(static::$db_table, array('meta_title'=>$slug,'zgroup'=>$group, 'type'=>'page'));
        $Page = null;
        if($data){
            $Page = new static($DB, null, $data);
        }
        return $Page;
    }

    /**
     *  
     * @param \ElevenFingersCore\Database\DatabaseConnectorPDO $DB
     * @param mixed $filter
     * @param null|array|string $order_by
     * @return SimplePage[];
     */
    static function getPages(DatabaseConnectorPDO $DB, ?array $filter = array(), null|array|string $order_by = null):array{
        $filter['type'] = 'page';
        $page_data = $DB->getArrayListByKey(static::$db_table,$filter,$order_by);
        $Pages = array();
        foreach($page_data AS $data){
            $Pages[] = new static($DB, null, $data);
        }
        return $Pages;
    }

}