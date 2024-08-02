<?php
namespace ElevenFingersCore\GAPPS\Schools;


class SchoolAccount extends \ElevenFingersCore\Accounts\User{

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
        $Profile->Save($DATA);
        return $re;
    }
    
}