<?php
namespace ElevenFingersCore\GAPPS;

class DatabaseConnectorPDOLog extends \ElevenFingersCore\Database\DatabaseConnectorPDO{
    private $changelog_queue = [];
    private $acct_id;

    private $log_table = 'change_log';

    private $start_logging = false;

    private $log_table_schema = [
        'id'=>0,
        'table'=>'',
        'row_id'=>0,
        'acct_id'=>0,
        'date'=>null,
        'change'=>'',
        'value'=>'',
    ];

    public function setAcctID(int $id){
        $this->acct_id = $id;
    }

    public function setLogging(bool $status){
        $this->start_logging = $status;
    }

    public function insertArray(String $table_name,Array &$array, String $key_name):bool{

        $is_changed = false;
        if($this->start_logging){
            $id = $array['id']?? 0;
            if($id){
                $current_state = $this->getArrayByKey($table_name,['id'=>$id]);
            
                $is_changed = false;
                $change_type = 'UPDATE';
                foreach($array AS $col=>$val){
                    if($current_state[$col] !== $val){
                        $is_changed = true;
                    }
                }
            }else{
                $is_changed = true;
                $change_type = 'INSERT';
                $current_state = $array;
            }
        }
        $ret = parent::insertArray($table_name,$array, $key_name);
        
        if($this->start_logging && $ret && $is_changed){
            $row_id = $array[$key_name];
            $new_log = [
                'table'=>$table_name,
                'row_id'=>$row_id,
                'acct_id'=>$this->acct_id,
                'date'=>date('Y-m-d H:i:s'),
                'change'=>$change_type,
                'value'=>json_encode($current_state),
            ];
            $this->addToQueue($new_log);
        }
        
        return $ret;
    }

    public function deleteByKey(String $table_name, Array $args):bool{
        
        if($this->start_logging){
            $id = $args['id'] ?? 0;
            $multi_row = false;
            if(!empty($id)){
                if(is_int($id)){
                    $current_state = $this->getArrayByKey($table_name,['id'=>$id]);
                }else{
                    $current_state = $this->getArrayListByKey($table_name,['id'=>$id]);
                    $multi_row = true;
                }
            }else{
                $current_state = $this->getArrayListByKey($table_name,$args);
                $multi_row = true;
            }
            
            if($multi_row){
                foreach($current_state AS $state){
                    $row_id = $state['id'];
                    $new_log = [
                        'table'=>$table_name,
                        'row_id'=>$row_id,
                        'acct_id'=>$this->acct_id,
                        'date'=>date('Y-m-d H:i:s'),
                        'change'=>'DELETE',
                        'value'=>json_encode($state),
                    ];
                    $this->addToQueue($new_log);
                }
            }else{
                $new_log = [
                    'table'=>$table_name,
                    'row_id'=>$current_state['id'],
                    'acct_id'=>$this->acct_id,
                    'date'=>date('Y-m-d H:i:s'),
                    'change'=>'DELETE',
                    'value'=>json_encode($current_state),
                ];
                $this->addToQueue($new_log);
            }
        }
        return parent::deleteByKey($table_name, $args);
    }

    public function addToQueue($new_log){
        $this->changelog_queue[] = $new_log;
    }

    public function logQueue(){
        foreach($this->changelog_queue AS $log){
            $this->insertArray($this->log_table,$log,'id');
        }
        $this->changelog_queue = [];
    }

    public function getChangedRows(string $table_name, array $row_ids){
        $sql = 'SELECT t1.*
                FROM '.$this->log_table.' t1
                JOIN (
                    SELECT `row_id`, MAX(`date`) AS max_date
                    FROM '.$this->log_table.'
                    WHERE `table` = "'.$table_name.'" 
                    AND `row_id` IN ('.implode(',',$row_ids).')
                    GROUP BY `row_id`
                ) t2
                ON t1.`row_id` = t2.`row_id` 
                AND t1.`date` = t2.`max_date`
                WHERE t1.`table` = "x"
                AND t1.`row_id` IN ('.implode(',',$row_ids).');';
        $changed_logs = $this->getResultArrayList($sql);
        $logs_by_row_id = [];
        foreach($changed_logs AS $log){
            $row_id = $log['row_id'];
            $logs_by_row_id[$row_id] = $log;
        }
        return $logs_by_row_id;
    }

    public function getChangesForRow(string $table_name,int $row_id){
        $keys = ['table_name' =>$table_name, 'row_id'=>$row_id];
        $logs = $this->getArrayListByKey($this->log_table,$keys,'date DESC');
        return $logs;
    }
}