<?php
namespace ElevenFingersCore\GAPPS;

use DateTimeImmutable;
use ElevenFingersCore\Accounts\User;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
class ChangeLog{

    protected $database;
    protected $school_id;
    protected $acct_id;

    protected $log_value_string = '';

    protected $logs = [];

    static $schema = [
        'id'=>0,
        'table_name'=>'',
        'row_id'=>0,
        'acct_id'=>0,
        'school_id'=>0,
        'date'=>'',
        'change_type'=>'',
        'value'=>'',
    ];
    static $db_table = 'change_log';


    function __construct(DatabaseConnectorPDO $DB,?int $school_id = null, ?int $acct_id = null){
        $this->database = $DB;
        $this->school_id = $school_id;
        $this->acct_id = $acct_id;
    }

    public function addLog(string $table,  string $change_type, int $record_id, string $value){
        $log = [
            'table_name'=>$table,
            'acct_id'=>$this->acct_id,
            'school_id'=>$this->school_id,
            'date'=>date('Y-m-d H:i:s'),
            'change_type'=>$change_type,
            'row_id'=>$record_id,
            'value'=>$value,
        ];

        $this->logs[] = $log;
    }

    public function processLogs(){
        foreach($this->logs AS $log){
            $log['id'] = 0;
            $this->database->insertArray(static::$db_table,$log,'id');
        }
        $this->logs = [];
    }

    public function getLogs(DateTimeImmutable $start_date, ?DateTimeImmutable $end_date = null, ?int $school_id = 0, ?string $log_type = null):array{
        $params = [
            'date'=>['>='=>$start_date->format('Y-m-d H:i:s')],
        ];
        if(!empty($end_date)){
            $params['date'] = [
                '>='=>$start_date->format('Y-m-d H:i:s'),
                'AND'=>['<='=>$end_date->format('Y-m-d H:i:s')]
            ];
        }
        if(!empty($school_id)){
            $params['school_id'] = $school_id;
        }
        if(!empty($log_type)){
            $params['table_name'] = $log_type;
        }
        $records = $this->database->getArrayListByKey(static::$db_table,$params, 'date DESC');
        return $records;
    }

    public function setLogValue(string $value){
        $this->log_value_string = $value;
    }
    public function getLogValue():string{
        return $this->log_value_string;
    }

}