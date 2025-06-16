<?php
namespace ElevenFingersCore\GAPPS\Schools;

use ElevenFingersCore\Database\DatabaseConnectorPDO;

class SchoolAccount extends \ElevenFingersCore\Accounts\User{

    static $user_type = 17;

    public function Save(?array $DATA = null):bool{
        if(!empty($DATA['password'])){
            $DATA['open_pass'] = $DATA['password'];
        }
        return parent::Save($DATA);
    }

    public function resetPassword(?String $password = null):?String{
        $re = parent::resetPassword($password);
        $DATA = array('open_pass'=>$re);
        $Profile = $this->getProfileObj();
        $Profile->setUserID($this->getID());
        $Profile->Save($DATA);
        return $re;
    }

    public static function fetchAccountFromSchoolID(DatabaseConnectorPDO $DB, int $school_id){
        $acct_ids = $DB->getResultListByKey('account_profile',['field'=>'school_id','value'=>$school_id],'acct_id');
        if(!empty($acct_ids)){
            $data_row = $DB->getArrayByKey('accounts',['id'=>['IN'=>$acct_ids],'usertype'=>17]);
        }else{
            $data_row = [];
        }
        
        
        return new SchoolAccount($DB, null, $data_row);
    }

    public function getOpenPassword():?string{
        $password = $this->database->getResultByKey('account_profile',['acct_id'=>$this->getID(),'field'=>'open_pass'],'value');
        return $password;
    }
    
}