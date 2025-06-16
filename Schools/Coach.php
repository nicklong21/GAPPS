<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Accounts\AccountType;
use ElevenFingersCore\Accounts\UserProfile;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Sports\Sport;
use ElevenFingersCore\GAPPS\Sports\SportFactory;
use ElevenFingersCore\Utilities\UtilityFunctions;

class Coach extends \ElevenFingersCore\Accounts\User{

    protected $School;
    protected $sports_coached = null;

    protected $SportFactory;

    protected $Certification;

    protected $current_school_year;

    protected $is_head_coach = null;


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
            $this->sports_coached = null;
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



    public function getCertificationStatus(string $school_year):string{
        $status = $this->DATA['status'];
        if($status != 'LOCKED'){
            $certification_year = $this->getProfileValue('gapps-certification');
            if(empty($certification_year) || $certification_year != $school_year){
                $status = 'NOT APPROVED';
            }else{
                $certification_date = $this->getProfileValue('certification-date');
                if(empty($certification_date)){
                    $status = 'PENDING';
                }else{
                    $status = 'APPROVED';
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


    public function getSportsCoachedDATA(?bool $refresh = false):array{
        if($refresh || $this->sports_coached === null){
            $this->sports_coached = $this->database->getArrayListByKey(static::$db_sport_xref,array('coach_id'=>$this->getID()));
        }
        return $this->sports_coached;
    }

    public function setSportsCoachedDATA(array $DATA){
        $this->sports_coached = $DATA;
    }

    public function getSportsCoachedIDs():array{
        $sport_ids = [];
        $sports_coached = $this->getSportsCoachedDATA();
        foreach($sports_coached AS $d){
            $sport_ids[] = $d['sport_id'];
        }
        return $sport_ids;
    }

    public function hasSport(int $sport_id):bool{
        $sports_coached = $this->getSportsCoachedDATA();
        $re = false;
        foreach($sports_coached as $sport){
            if($sport['sport_id'] == $sport_id){
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
            $this->current_school_year = SCHOOL_YEAR;
        }
        return $this->current_school_year;
    }

    /** @return Coach[] */
    static function getSchoolStaff(DatabaseConnectorPDO $DB, ?int $school_id, ?array $filter = array()):array{
        $acct_data = [];
        $acct_profiles = [];
        if($school_id){
            $profile_data = $DB->getArrayListByKey('account_profile',['field'=>'school_id','value'=>$school_id]);
            
            $acct_profiles = [];
            foreach($profile_data AS $data){
                $acct_id = $data['acct_id'];
                if(empty($acct_profiles[$acct_id])){
                    $acct_profiles[$acct_id] = [];
                }
                $acct_profiles[$acct_id][$data['field']] = $data['value'];
            }
            $acct_ids = array_keys($acct_profiles);
            
            if(!empty($acct_ids)){
                $filter['id'] = ['IN'=>$acct_ids];
            }
            if(empty($filter['usertype'])){
                $filter['usertype'] = ['!='=>17];
            }
            $acct_data = $DB->getArrayListByKey(static::$table_name,$filter);
           
        }else{
            if(empty($filter['usertype'])){
                $filter['usertype'] = ['IN'=>[11,13,14,15,16]];
            }
            $acct_data = $DB->getArrayListByKey(static::$table_name,$filter,[],['key_name'=>'id']);
            $acct_ids = array_keys($acct_data);
            $profile_data = $DB->getArrayListByKey('account_profile',['acct_id'=>['IN'=>$acct_ids]]);
            $acct_profiles = [];
            foreach($profile_data AS $data){
                $acct_id = $data['acct_id'];
                if(empty($acct_profiles[$acct_id])){
                    $acct_profiles[$acct_id] = [];
                }
                $acct_profiles[$acct_id][$data['field']] = $data['value'];
            }
        }

        $Accounts = [];
        $Usertypes = [];
        foreach($acct_data AS $data){
            $usertype = $data['usertype'];
            if(empty($Usertypes[$usertype])){
                //echo 'load Usertype: '.$usertype;
                $Usertypes[$usertype] = new AccountType($DB, $usertype);
            }
            //pa(array_keys($Usertypes));
            $UserType = $Usertypes[$usertype];
            $Account = new static($DB,null,$data);
            $acct_id = $Account->getID();
            $Account->setAccountType($UserType);
            $profile_class = $UserType->getUserProfileClass();
            $Account->setProfileClass($profile_class);
            $profile_data = isset($acct_profiles[$acct_id])?$acct_profiles[$acct_id]:[];
            $Account->setProfile($profile_data);
            
            $Accounts[$Account->getID()] = $Account;
        }
        
    return $Accounts;
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
