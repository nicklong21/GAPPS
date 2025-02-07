<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Accounts\UserProfile;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Sports\Sport;
use ElevenFingersCore\GAPPS\Sports\SportFactory;
use ElevenFingersCore\Utilities\UtilityFunctions;

class Coach extends \ElevenFingersCore\Accounts\User{

    protected $School;
    protected $sports_coached;

    protected $SportFactory;

    protected $Certification;

    protected $current_school_year;


    static $db_sport_xref = 'sports_coaches';

    public function getAccountData():array{
        $response = parent::getAccountData();
        $response['profile']['certification-date'] = $this->getCertificationDate('Y-m-d');
        $response['profile']['gapps-certification'] = $this->getCertificationYear();
        return $response;
    }

    public function Save(?Array $DATA = null):bool{
        if(empty($DATA)){
            $DATA = $this->DATA;
        }
        if(array_key_exists('email', $DATA)){
            //$DATA['username'] = $DATA['email'];
        }
        $success = parent::Save($DATA);

        if($success){
            $head_sports = isset($DATA['head_coach'])?$DATA['head_coach']:array();
            $asst_sports = isset($DATA['asst_coach'])?$DATA['asst_coach']:array();
            if(!empty($head_sports) || !empty($asst_sports)){
                $current_sports = $this->getSportsCoachedDATA();
                $new_sports = array();
                foreach($asst_sports AS $sport_id){
                    $new_sports[$sport_id] = array('id'=>0, 'sport_id'=>$sport_id,'position'=>'AC');
                }
                foreach($head_sports AS $sport_id){
                    $new_sports[$sport_id] = array('id'=>0, 'sport_id'=>$sport_id,'position'=>'HC');
                }
                $removed_sports = array();
                foreach($current_sports AS $data){
                    if(isset($new_sports[$data['sport_id']])){
                        $new_sports[$data['sport_id']]['id'] = $data['id'];
                    }else{
                        $removed_sports[] = $data['sport_id'];
                    }
                }
                foreach($new_sports AS $insert){
                    $insert['coach_id'] = $this->getID();
                    $this->database->insertArray(static::$db_sport_xref,$insert,'id');
                }
                if(!empty($removed_sports)){
                    $sql = 'DELETE FROM '.static::$db_sport_xref.' WHERE coach_id = :coach_id AND sport_id IN ('.implode(',',$removed_sports).')';
                    $this->database->query($sql,array(':coach_id'=>$this->getID()));
                }
            }
        }
        return $success;
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

    function getCertificationDate(?string $format = null):null|string|DateTimeImmutable{
        $certfication_date = $this->getProfileValue('certification-date');
        if(!empty($certfication_date)){
            $CertificationDate = new DateTimeImmutable($certfication_date);
            if(!empty($format)){
                $certfication_date = $CertificationDate->format($format);
            }else{
                $certfication_date = $CertificationDate;
            }
        }else{
            $certfication_date = null;
        }
        return $certfication_date;
    }

    function getCertificationYear():?string{
        return $this->getProfileValue('gapps-certification');
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

    public function getStatus():string{
        $status = $this->DATA['status'];
        if($status != 'LOCKED'){
            $type = $this->getAccountTypeName();
            if($type == 'Coach'){
                $status = $this->getCertificationStatus();
            }
        }
        return $status;
    }

    public function getAccountStatus():string{
        return $this->DATA['status'];
    }

    public function getCertificationStatus():string{
        $status = $this->DATA['status'];
        if($status != 'LOCKED'){
            $certification_year = $this->getProfileValue('gapps-certification');
            if(empty($certification_year) || $certification_year != $this->getSchoolYear()){
                $status = 'NOT APPROVED';
            }else{
                $certification_date = $this->getProfileValue('certification-date');
                if(empty($certification_date)){
                    $status = 'PENDING';
                }elseif($this->DATA['status'] != 'Active'){
                    $status = 'APPROVED';
                }else{
                    $status = 'Active';
                }
            }
        }
        return $status;
    }

    public function isLayCoach():bool{
        $Profile = $this->getProfileObj();
        return $Profile->getValue('is_lay_coach')?true:false;
    }

    public function getEmployeeStatus():string{
        $Profile = $this->getProfileObj();
        $lay_coach = $Profile->getValue('is_lay_coach');
        switch($lay_coach){
            case 2:
                $re = 'Lay Coach - Certification Paid by School';
            break;
            case 1:
                $re = 'Lay Coach - Certification Paid by Coach';
            break;
            case 0:
            default:
                $re = 'Full Time Employee';
            break;
        }
        return $re;
    }



    public function getSchoolID():?int{
        if(!empty($this->School)){
            $school_id = $this->School->getID();
        }else{
            $Profile = $this->getProfileObj();
            $school_id = $Profile->getValue('school_id');
        }
        $school_id = !empty($school_id)?intval($school_id):null;
        
        return $school_id;
    }


    public function getSportsCoachedDATA():array{
        if(empty($this->sports_coached)){
            $this->sports_coached = $this->database->getArrayListByKey(static::$db_sport_xref,array('coach_id'=>$this->getID()));
        }
        return $this->sports_coached;
    }

    public function hasSport(int $sport_id):bool{
        $sports_coached = $this->getSportsCoachedDATA();
        $re = false;
        foreach($sports_coached as $sport){
            if($sports_coached['sport_id'] == $sport_id){
                $re = true;
                break;  
            }
        }
        return $re;
    }

    public function isHeadCoach(?int $sport_id = null):bool{
        $coached = $this->getSportsCoachedDATA();
        $is_head = false;
        foreach($coached AS $sport){
            if($sport['position'] == 'HC'){
                if(!empty($sport_id) && $sport['sport_id'] == $sport_id){
                    $is_head = true;
                    break;
                }elseif(empty($sport_id)){
                    $is_head = true;
                    break;
                }
            }
        }
        return $is_head;
    }

    public function setSchoolYear(string $school_year){
        $this->current_school_year = $school_year;
    }
    
    public function getSchoolYear():string{
        if(empty($this->current_school_year)){
            $this->current_school_year = UtilityFunctions::formatSchoolYear();
        }
        return $this->current_school_year;
    }

    /** @return Coach[] */
    static function getSchoolStaff(DatabaseConnectorPDO $DB, int $school_id, ?array $filter = array()):array{
        $data = $DB->getResultListByKey('account_profile',array('field'=>'school_id','value'=> $school_id),'acct_id');
        $Coaches = array();
        if(!empty($data)){
            $filter['id'] = array('IN'=>$data);
            if(empty($filter['usertype'])){
                $filter['usertype'] = array('!='=>17);
            }
            $coach_data = $DB->getArrayListByKey(static::$table_name,$filter);
            foreach($coach_data as $coach){
                $Coaches[] = new static($DB, null, $coach);
        }
    }
    return $Coaches;
    }
    /**
     *  
     * @param \ElevenFingersCore\Database\DatabaseConnectorPDO $DB
     * @param int $sport_id
     * @param mixed $filter
     * @return Coach[]
     */
    static function getSportCoaches(DatabaseConnectorPDO $DB, int $sport_id, ?array $filter = array(), ?array $xref_filter = array()):array{
        $xref_filter = $xref_filter?$xref_filter:array();
        $xref_filter['sport_id'] = $sport_id;
        $data = $DB->getResultListByKey(static::$db_sport_xref,$xref_filter,'coach_id');
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
