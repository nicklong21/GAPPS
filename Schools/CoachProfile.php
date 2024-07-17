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
        'is_lay_coach'=>'',
        'concussions_in_sports'=> '',
        'sudden_cardiac_arrest'=> '',
        'heat-illness-prevention'=> '',
        'cpr-aed-certification'=> '',
        'criminal-background-check'=> '',
        'gapps-certification'=>'',
        'certification-date'=>'',
    );

}