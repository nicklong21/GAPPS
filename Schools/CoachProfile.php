<?php
namespace ElevenFingersCore\GAPPS\Schools;
use ElevenFingersCore\Accounts\UserProfile;

class CoachProfile extends UserProfile{

    static $template = array(
        'firstname'=>'', 
        'lastname'=>'',
        'email'=>'',
        'phone'=>'',
        'phone2'=>'', 
        'school_id'=>'',
    );

}