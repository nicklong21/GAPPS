<?php
namespace ElevenFingersCore\GAPPS\Schools;
use ElevenFingersCore\Accounts\UserProfile;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Sports\Sport;

class Coach extends \ElevenFingersCore\Accounts\User{

    protected $School;
    protected $Sports;

    protected $Certification;


    static $db_sport_xref = 'sports_coaches';


    public function Save(?Array $DATA = null):bool{
        if(empty($DATA)){
            $DATA = $this->DATA;
        }
        if(array_key_exists('email', $DATA)){
            $DATA['username'] = $DATA['email'];
        }
        return parent::Save($DATA);

    }
    function getProfileObj():CoachProfile{
        if(empty($this->Profile)){
            $AccountType = $this->getAccountType();
            $account_type_name = $AccountType->getName();
            switch($account_type_name){
                default:
                $this->Profile = new CoachProfile($this->database, $this->getID());
                break;
        }
    }
    return $this->Profile;
    }
    
    public function getCertification(string $school_year):?CoachCertification{
        if(empty($this->Certification)){
            $Certifications = CoachCertification::findCertifications($this->database, array('acct_id'=>$this->id, 'school_year'=>$school_year));
            if(!empty($Certifications)){
                $this->Certification = $Certifications[0];
            }else{
                $this->Certification = null;
            }
        }
        return $this->Certification;
    }

    public function setCertification(CoachCertification $Certification){
        $this->Certification = $Certification;
    }

    public function isLayCoach():bool{
        $Profile = $this->getProfileObj();
        return $Profile->getValue('is_lay_coach')?true:false;
    }

    public function getSchool():School{
        if(empty($this->School)){
            $Profile = $this->getProfileObj();
            $school_id = $Profile->getValue('school_id');
            $this->School = new School($this->database, $school_id);
        }
        return $this->School;
    }

    public function getSchoolID():?int{
        if(!empty($this->School)){
            $school_id = $this->School->getID();
        }else{
            $Profile = $this->getProfileObj();
            $school_id = $Profile->getValue('school_id');
        }
        return $school_id;
    }

    public function getSchoolName():string{
        $School = $this->getSchool();
        return $School->getTitle();
    }

    /** @return Sport[] */
    public function getSports():array{
        if(empty($this->Sports)){
            $this->Sports = array();
            $data = $this->database->getResultListByKey(static::$db_sport_xref,array('coach_id'=>$this->getID()),'sport_id');
            if(!empty($data)){
            $Sports = Sport::getSports($this->database, array('id'=>array('IN'=>$data)));
            $this->Sports = $Sports;
            }
    }
    return $this->Sports;
    }

    public function hasSport(int $sport_id):bool{
        $Sports = $this->getSports();
        $re = false;
        foreach($Sports as $Sport){
            if($Sport->getID() == $sport_id){
                $re = true;
                break;  
            }
        }
        return $re;
    }

    /** @return Coach[] */
    static function getSchoolStaff(DatabaseConnectorPDO $DB, int $school_id, ?array $filter = array()):array{
        $data = $DB->getResultListByKey('account_profile',array('field'=>'school_id','value'=> $school_id),'acct_id');
        $Coaches = array();
        if(!empty($data)){
            $filter['id'] = array('IN'=>$data);
            $coach_data = $DB->getArrayListByKey(static::$table_name,$filter);
            foreach($coach_data as $coach){
                $Coaches[] = new static($DB, null, $coach);
        }
    }
    return $Coaches;
    }
}
