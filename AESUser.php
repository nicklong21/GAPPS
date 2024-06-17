<?php
namespace ElevenFingersCore\GAPPS;
use ElevenFingersCore\Accounts\User;
use ElevenFingersCore\Authorization\SessionAuthorization;

class AESUser extends User{
    static $user_type = 4;
    public function Save(?array $DATA = null):bool{
        /* Enforces Email as Username */
        $new_transaction = false;
        $success = true;
        if(!$this->database->inTransaction()){
            $this->database->beginTransaction();
            $new_transaction = true;
        }
        $insert = array();
        if(is_null($DATA)){
            $insert = $this->DATA;
        }else{
            foreach(static::$template AS $key=>$val){
                if(array_key_exists($key,$DATA)){
                    $insert[$key] = !empty($DATA[$key])?$DATA[$key]:$val;
                }    
            }
        }
        $insert['id'] = $this->id;
        if(array_key_exists('username',$insert) && empty($insert['username'])){
            $insert['username'] = $DATA['email'];
        }
        if(isset($insert['username'])){
            $insert['username'] = trim($insert['username']);
            $is_unique = $this->testForUniqueValue('username',$insert['username'], false);
            if(!$is_unique){
                $this->addErrorMsg('That email addresss is already in use.', 'NOTICE',array('acct_id'=>$insert['id'],'username'=>$insert['username']),'UNIQUE_USERNAME');
                $success = false;
            }
        }
        if($success && isset($DATA['password'])){
            if(!empty(trim($DATA['password']))){
                $insert['password'] = SessionAuthorization::generatePasswordHash($DATA['password']);

            }else{
                unset($insert['password']);
                unset($insert['salt']);
            }
        }
        if($success){
            $AccountType = $this->getAccountType();
            $insert['usertype'] = $AccountType->getID();
            $success = $this->database->insertArray(static::$table_name, $insert, 'id');
            $this->id = $insert['id'];
            if($success){
                $success = $this->updateProfile($DATA);
            }else{
                $this->addErrorMsg('Error: Unable to Save User DATA','ERROR',array('DATA'=>$DATA),'SAVE_ERROR');
            }
        }
        if($new_transaction){
            if($success){
                $this->database->commitTransaction();
            }else{
                $this->database->rollbackTransaction();
            } 
        }
        if($success){
            
            $this->initialize($this->id);
        }
        return $success;
    }

}
