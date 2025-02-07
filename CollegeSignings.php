<?php
namespace ElevenFingersCore\GAPPS;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
class CollegeSignings{
    
    var $database = null;
    var $sport_id = 0;
    var $school_id = 0;
    var $list = array();
    var $order_by = 'year DESC, college_name, student_lastname, student_firstname';
    var $display_style = 'default';

    protected $AllSports;
    static $table = 'college_signings';
    static $template = array(
        'id'=>0,
        'sport_id'=>0,
        'school_id'=>0,
        'college_name'=>'',
        'student_firstname'=>'',
        'student_lastname'=>'',
        'year'=>'',
    );
    
    /*
    CREATE TABLE `gicaasports-dev`.`college_signings` ( `id` INT NOT NULL AUTO_INCREMENT , `sport_id` INT NULL , `school_id` INT NULL , `college_name` VARCHAR(56) NULL , `student_firstname` VARCHAR(56) NULL , `student_lastname` VARCHAR(56) NULL , `year` INT NULL , PRIMARY KEY (`id`), INDEX `sport_id` (`sport_id`), INDEX `school_id` (`school_id`)) ENGINE = InnoDB;
    */
    
    function __construct(DatabaseConnectorPDO $DB, $sport_id = null){
        $this->database = $DB;
        $this->sport_id = $sport_id;
    }

    public function setAllSports(array $AllSports){
        $this->AllSports = $AllSports;
    }
    
    function getList($limit = null, $start=0){
        if(empty($this->list)){
            $sql = 'SELECT * FROM '.static::$table.' WHERE 1=1';
            $args = array();
            
            if($this->sport_id){
                $sql .= ' AND sport_id = :sport_id';
                $args[':sport_id'] = $this->sport_id;
            }
            
            if($this->school_id){
                $sql .= ' AND school_id = :school_id';
                $args[':school_id'] = $this->school_id;
            }
            
            $sql .= ' ORDER BY '.$this->order_by;
            
            if(!empty($limit)){
                $sql .= ' LIMIT '.$start.','.$limit;      
            }
            
            $list = $this->database->getResultArrayList( $sql, $args);
            $this->list = $list;
        }
        return $this->list;
    }
    
    function loadListHTML($limit = null, $start=0){
        global $debug;
        $response = '';
        $list_items = $this->getList($limit,$start);
        $debug[] = $list_items;
        $style = !empty($this->display_style)?$this->display_style:'default';

        if(method_exists($this,'loadListHTML_'.$style)){
            $method = 'loadListHTML_'.$style;
        }else{
            $method = 'loadListHTML_default';
        }

        $response = $this->$method($list_items);

        return $response;
        
    }
    
    function loadListHTML_default($list_items = array()){
        $response = '';
        return $response;
    }
    
    function loadListHTML_admin($list_items = array()){
        
        $AllSports = $this->AllSports;
        $response = '';

         foreach($list_items AS $item)
            {
            $sport_name = isset($AllSports[$item['sport_id']])?$AllSports[$item['sport_id']]->getTitle():'Sport Not Found';  
            $response .= '<li class="item published" id="jd_announcement_'.$item['id'].'"  data-sport_id="'.$item['sport_id'].'" data-id="'.$item['id'].'" data-school_id="'.$item['school_id'].'" data-college_name="'.$item['college_name'].'" data-year="'.$item['year'].'"><div class="title">'.$item['student_lastname'].', '.$item['student_firstname'].' <span class="date">'.$item['college_name'].' -  '.$sport_name.' '.$item['year'].'</span></div>';

            $response.='</li>';
            }

        return $response;
        
    }
    
    function getItem($id){
        $sql = 'SELECT * FROM '.static::$table.' WHERE id = :id';
        $args = array(':id'=>$id);
        $item = $this->database->getResultArray($sql,$args);
        return $item;
    }
    
    function Save($DATA){
        
        $template = array();
        foreach(static::$template AS $key=>$val){
            
            if(isset($DATA[$key])){
                $template[$key] = $DATA[$key];
            }
        }
        if(!empty($template)){
            $this->database->insertArray(static::$table, $template, 'id');
        }
    }
    
    function Delete($id){
        
        $sql = 'DELETE FROM '.static::$table.' WHERE id = :id';
        $args = array(':id'=>$id);
        $this->database->query($sql, $args);
        
    }
    
    function setOrderBy($order_by){
        $this->order_by = $order_by;
    }
    
    function setDisplayStyle($display_style){
        $this->display_style = $display_style;
    }
    
    function setSchoolID($id){
        $this->school_id = $id;
    }
    
    public static function getColleges($DB){
        
        $sql = 'SELECT DISTINCT college_name FROM '.static::$table.' ORDER BY college_name';
        return $DB->getResultList($sql, 'college_name', null);
        
    }
    
}


?>