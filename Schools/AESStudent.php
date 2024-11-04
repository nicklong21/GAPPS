<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Resources\ResourceFile;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;

class AESStudent{
    use InitializeTrait;
    use MessageTrait;

    protected $database; 
    protected $id;

    protected $submission_year;

    protected $parent_id;

    protected $DATA;

    protected bool $has_changed = false;

    protected $vital_info = array(
        'date_of_birth'=>NULL,
        'current_age'=>NULL,
        'gender'=>NULL,
        'bc_approved'=>NULL,
    );
    protected $contact_info = array(
        'parent_firstname'=>'',
        'parent_lastname'=>'',
        'relationship'=>'',
        'contact_phone'=>'',
        'email'=>'',
        'address1'=>'',
        'address2'=>'',
        'city'=>'',
        'county'=>'',
        'state'=>'',
        'zip'=>'',
    );
    protected $education_info = array(
        'entered_9th'=>'',
        'grade'=>'',
        'education_history'=>array(),
        'enrolled'=>'',
        'enrolled_school'=>'',
        'repeated_grade'=>'',
        'repeated_explanation'=>'',
        'age_comparison'=>'',
        
    );
    protected $uploaded_files = array(
        'transcript'=>array(),
        'birth_certificate'=>array(),
        'pg_file'=>array(),
        'doe_documentation'=>array(),
    );
    protected $activity_info = array(
        'GHSA_sports_participation'=>array(),
        'GAPPS_sports_participation'=>array(),
        'GHSA_arts_participation'=>array(),
        'GAPPS_arts_participation'=>array(),
    );
    protected $agreement_info = array(
        'agreement'=>NULL,
        'understanding'=>NULL,
    );

    static $db_table = 'students_aes_2024';
    static $template = array(
        'id'=>0,
        'status'=>NULL,
        'student_id'=>NULL,
        'school_id'=>NULL,
        'firstname'=>'',
        'lastname'=>'',
        'requested_school'=>0,
        'submission_year'=>NULL,
        'date_added'=>NULL,
        'date_updated'=>NULL,
        'date_approved'=>NULL,
        'vital_info'=>'',
        'contact_info'=>'',
        'education_info'=>'',
        'uploaded_files'=>'',
        'activity_info'=>'',
        'agreement_info'=>'',
        'parent_id'=>0,
        'payment_id'=>0,
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = NULL, ?array $data=NULL){
        $this->database = $DB;
        $this->initialize($id,$data);
    }

    protected function initialize(?int $id = 0, ?array $DATA = array()){
        if(!empty($id)){
            $DATA = $this->database->getArrayByKey(static::$db_table,array('id'=>$id));
        }
        if(empty($DATA)){
            $DATA = array();
        }
        foreach(static::$template AS $key=>$val){
            if(array_key_exists($key, $DATA)){
                $this->DATA[$key] = $DATA[$key];
            }elseif(empty($this->DATA[$key])){
                $this->DATA[$key] = $val;
            }
        }
        $this->id = $this->DATA['id'];
        $this->parent_id = $this->DATA['parent_id'];
        $this->submission_year = $this->DATA['submission_year'];

        $vital_info = json_decode($this->DATA['vital_info'],true);
        $contact_info = json_decode($this->DATA['contact_info'],true);
        $education_info = json_decode($this->DATA['education_info'],true);
        $uploaded_files = json_decode($this->DATA['uploaded_files'],true);
        $activity_info = json_decode($this->DATA['activity_info'],true);
        $agreement_info = json_decode($this->DATA['agreement_info'],true);
        
        if(!empty($vital_info)){
            foreach($this->vital_info as $key=>$val){
                if(array_key_exists($key, $vital_info) && !empty($vital_info[$key])){
                    $this->vital_info[$key] = $vital_info[$key];
                }
            }
            if($this->vital_info['gender'] == 'M'){
                $this->vital_info['gender'] = 'Male';
            }elseif($this->vital_info['gender'] == 'F'){
                $this->vital_info['gender'] = 'Female';
            }
        }
        if(!empty($contact_info)){
            foreach($this->contact_info as $key=>$val){
                if(array_key_exists($key, $contact_info) && !empty($contact_info[$key])){
                    $this->contact_info[$key] = $contact_info[$key];
                }
            }
        }
        if(!empty($education_info)){
            foreach($this->education_info as $key=>$val){
                if(array_key_exists($key, $education_info) && !empty($education_info[$key])){
                    $this->education_info[$key] = $education_info[$key];
                }
            }
        }
        if(!empty($uploaded_files)){
            foreach($this->uploaded_files as $key=>$val){
                if(array_key_exists($key, $uploaded_files) && !empty($uploaded_files[$key])){
                    $this->uploaded_files[$key] = $uploaded_files[$key];
                }
            }
        }
        if(!empty($activity_info)){
            foreach($this->activity_info as $key=>$val){
                if(array_key_exists($key, $activity_info) && !empty($activity_info[$key])){
                    $this->activity_info[$key] = $activity_info[$key];
                }
            }
        }
        if(!empty($agreement_info)){
            foreach($this->agreement_info as $key=>$val){
                if(array_key_exists($key, $agreement_info) && !empty($agreement_info[$key])){
                    $this->agreement_info[$key] = $agreement_info[$key];
                }
            }
        }
    }

    public function setSubmissionYear(string $submission_year){
        $this->submission_year = $submission_year;
    }

    public function getSubmissionYear():string{
        return $this->submission_year;
    }

    public function getStatus(string $submission_year):string{
        $status = $this->DATA['status'];
        $current_submission_year = $this->DATA['submission_year'];
        if($status == 'ASSIGNED' || $status == 'APPROVED'){
            if($current_submission_year < $submission_year){
                $status = 'PENDING RENEWAL';
            }
        }
        return $status;
    }

    public function getDATA():array{
        $DATA = $this->DATA;
        $DATA['vital_info'] = $this->vital_info;
        $DATA['contact_info'] = $this->contact_info;
        $DATA['education_info'] = $this->education_info;
        $DATA['uploaded_files'] = $this->uploaded_files;
        $DATA['activity_info'] = $this->activity_info;
        $DATA['agreement_info'] = $this->agreement_info;
        return $DATA;
    }

    protected function prepareForSave(?array $data):?array{
        
        if(is_null($data)){
            $data = $this->DATA;
        }
        
        if(array_key_exists('requested_school',$data) && $data['requested_school'] == 'NULL'){
            $data['requested_school'] = 0;
        }
        $data['vital_info'] = json_encode($this->prepareVitalInfoForSave($data));
        $data['contact_info'] = json_encode($this->prepareContactInfoForSave($data));
        $data['education_info'] = json_encode($this->prepareEducationInfoForSave($data));
        $data['uploaded_files'] = json_encode($this->prepareUploadedFilesForSave($data));
        $data['activity_info'] = json_encode($this->prepareActivityInfoForSave($data));
        $data['agreement_info'] = json_encode($this->prepareAgreementInfoForSave($data));
        $data['parent_id'] = $this->parent_id;
        $data['submission_year'] = $this->getSubmissionYear();
        
        return $data;
    }

    protected function prepareVitalInfoForSave(array $data):array{
        $vital_info = $this->vital_info;
        foreach($vital_info AS $key=>$val){
            if(array_key_exists($key, $data) && !empty($data[$key]) && $data[$key] != $vital_info[$key]){
                if($key == 'date_of_birth'){
                    try{
                        $DateTime = new DateTimeImmutable($data[$key]);
                        $vital_info[$key] = $DateTime->format('Y-m-d');
                    }catch(\Exception $e){
                        throw new \RuntimeException('Error: Birth Date must be in a valid format YYYY-MM-DD');
                    }
                }else{
                    $vital_info[$key] = $data[$key];
                }
                
                $this->has_changed = true;
            }
        }
        return $vital_info;
    }
    
    protected function prepareContactInfoForSave(array $data):array{
        $contact_info = $this->contact_info;
        foreach($contact_info AS $key=>$val){
            if(array_key_exists($key, $data) && !empty($data[$key]) && $data[$key] != $contact_info[$key]){
                $contact_info[$key] = $data[$key];
                $this->has_changed = true;
            }
        }
        return $contact_info;
    }
    protected function prepareEducationInfoForSave(array $data):array{
        $education_info = $this->education_info;
        foreach($education_info AS $key=>$val){
            if(array_key_exists($key, $data) && !empty($data[$key]) && $data[$key] != $education_info[$key]){
                if($key == 'education_history'){continue;}
                $education_info[$key] = $data[$key];
                $this->has_changed = true;
            }
        }
        $history = $education_info['education_history'];
        $new_history = isset($data['education'])? $data['education']:array();
        foreach($new_history AS $school_year=>$options){
            $year = array();
            if(!empty($options)){
                foreach($options AS $option){
                $value = isset($data['education_location'][$school_year][$option])? $data['education_location'][$school_year][$option]:NULL;
                $year[$option] = $value;
                }
            }
            $history[$school_year] = $year;
        }
        $education_info['education_history'] = $history;

        

        return $education_info;
    }
    protected function prepareActivityInfoForSave(array $data):array{
        $activity_info = $this->activity_info;
        $submission_year = $this->getSubmissionYear();
        if(array_key_exists('sport_participation_val',$data)){
            $activity_info['GHSA_sports_participation'][$submission_year] = array('school'=>'','sports'=>'');
                if($data['sport_participation_val'] == 'Yes'){
                    $activity_info['GHSA_sports_participation'][$submission_year]['school'] = $data['sport_participation_school'];
                    $activity_info['GHSA_sports_participation'][$submission_year]['sports'] = $data['sport_participation_sport'];
                }
            }
        if(array_key_exists('arts_participation_val',$data)){
            $activity_info['GHSA_arts_participation'][$submission_year] = array('school'=>'','activity'=>'');
                if($data['arts_participation_val'] == 'Yes'){
                    $activity_info['GHSA_arts_participation'][$submission_year]['school'] = $data['arts_participation_school'];
                    $activity_info['GHSA_arts_participation'][$submission_year]['activity'] = $data['arts_participation_activity'];
                }
            }


        if(array_key_exists('sports',$data)){
            $activity_info['GAPPS_sports_participation'][$submission_year] = $data['sports'];
        }
        if(array_key_exists('arts',$data)){
            $activity_info['GAPPS_arts_participation'][$submission_year] = $data['arts'];
        }
        return $activity_info;
    }
    protected function prepareUploadedFilesForSave(array $data):array{
        $uploaded_files = $this->uploaded_files;
        if(!empty($data['transcript'])){
            $uploaded_files['transcript'] = json_decode($data['transcript'],true);
        }
        if(!empty($data['bc_file'])){
            $uploaded_files['birth_certificate'] = json_decode($data['bc_file'],true);
        }
        $submission_year = $this->getSubmissionYear();
        if(empty($uploaded_files['doe_documentation'][$submission_year])){
            $uploaded_files['doe_documentation'][$submission_year] = array();
        }
        if(array_key_exists('ga_file',$data)){
            $uploaded_files['doe_documentation'][$submission_year] = json_decode($data['ga_file'],true);
        }
        return $uploaded_files;
    }
    protected function prepareAgreementInfoForSave(array $data):array{
        $agreement_info = $this->agreement_info;
        if(array_key_exists('agree',$data)){
            $agreement_info['agreement'] = !empty($data['agree'])?date('Y-m-d H:i:s'):null;
        }
        if(array_key_exists('understand',$data)){
            $agreement_info['understanding'] = !empty($data['understand'])?date('Y-m-d H:i:s'):null;
        }
        return $agreement_info;
    }

    public function setParentID(int $parent_id){
        $this->parent_id = $parent_id;
    }

    public function getFullName():string{
        $fullname = $this->DATA['lastname'].', '.$this->DATA['firstname'];
        return $fullname;
    }

    public function get($key){
        $value = null;
        if(array_key_exists($key, $this->vital_info)){
            $value = $this->vital_info[$key];
        }elseif(array_key_exists($key, $this->education_info)){
            $value = $this->education_info[$key];
        }elseif(array_key_exists($key, $this->contact_info)){
            $value = $this->contact_info[$key];
        }elseif(array_key_exists($key, $this->uploaded_files)){
            $value = $this->uploaded_files[$key];
        }elseif(array_key_exists($key, $this->activity_info)){
            $value = $this->activity_info[$key];
        }elseif(array_key_exists($key, $this->agreement_info)){
            $value = $this->agreement_info[$key];
        }elseif(array_key_exists($key, $this->DATA)){
            $value = $this->DATA[$key];
        }
        return $value;
    }

    public function isBirthCertificateApproved():bool{
        return !empty($this->vital_info['bc_approved'])?true:false;
    }

    public function getEducationSelections($school_year):array{
        $education = array();
        if(!empty($this->education_info['education_history'][$school_year])){
            $education = $this->education_info['education_history'][$school_year];
        }
        return $education;
    }

    /** @return ResourceFile[] */
    public function getDocuments(?string $type = null, ?string $school_year = null):array{  
        $documents = array();
        $ResourceFiles = array();
        if($type){
            if(!empty($this->uploaded_files[$type])){
                $type_documents = $this->uploaded_files[$type];
                if(!empty($school_year)){
                    $documents = !empty($type_documents[$school_year])? $type_documents[$school_year] : array();
                }else{
                    $documents = $type_documents;
                }
            }
        }else{
            $all_documents = $this->uploaded_files;
            if(!empty($school_year)){
                foreach($all_documents AS $type=>$years){
                    if(array_key_exists($school_year, $years)){
                        $documents[$type] = $all_documents[$type][$school_year];
                    }
                }
            }else{
                $documents = $all_documents;
            }   
        }
        foreach($documents AS $resource_id){
            $ResourceFiles[] = new ResourceFile($this->database, $resource_id);
        }
        return $ResourceFiles;
    }

    public function getCurrentAge(?DateTimeImmutable $Today = null):?int{
        $age = null;
        $dob = $this->vital_info['date_of_birth'];
        if(!empty($dob)){
            try{
                $BirthDate = new DateTimeImmutable($dob);
                if(empty($Today)){
                    $Today = new DateTimeImmutable();
                }
                $AgeInterval = $Today->diff($BirthDate);
                $age = $AgeInterval->format('%y');
            }catch(\Exception $e){
                $this->addErrorMsg('Invalid Format for Date of Birth','Error');
            }
        }
        return $age;
    }

    public function getGrade(?DateTimeImmutable $Today = null):?string{
        $grade = null;
        if(!empty($this->education_info['entered_9th'])){
            $year_entered_9th = $this->education_info['entered_9th'];
            if(!empty($year_entered_9th)){
                if(empty($Today)){
                    $Today = new DateTimeImmutable();
                }
                $this_year = $Today->format('Y');
                $this_month = $Today->format('n');
                if($this_month <= 6){
                    $this_year = $this_year-1;
                }
                $grade = 9 - ($year_entered_9th - $this_year);
                if($grade < 1){
                    $grade = 'PreK';
                }else if($grade > 12){
                    $grade = 'Graduate';
                }
            }
        }
        return $grade;
    }

    public function getPaymentInfo(){
        $payment_info = array('date'=>null,'amount'=>'','type'=>'');
        if(!empty($this->DATA['payment_id'])){
            $data = $this->database->getArrayByKey('student_payments',array('id'=>$this->DATA['payment_id']));
            if($data){
                $payment_info['date'] = $data['payment_date'];
                $payment_info['amount'] = $data['payment_amount'];
                $payment_info['type'] = $data['payment_type'];
            }
        }
        return $payment_info;
    }

    public function approveDocument(string $type):bool{
        $success = false;
        $update = array();
        switch($type){
            case 'birth_certificate':
                $success = $this->deleteBirthCertificateFile();
                if($success){
                    $update = array('bc_file'=>json_encode(array()), 'bc_approved'=>1);
                }
                break;
                default:
                break;
        }
        if($success){
            $success = $this->Save($update);
        }
        return $success;
    }

    private function deleteBirthCertificateFile():bool{
        $success = true;
        $Resources = $this->getDocuments('birth_certificate');
        if(!empty($Resources)){
            /** @var ResourceFile $Resource */
            foreach($Resources AS $Resource){   
                $FileHandler = $Resource->getFileHandler();
                $success = $FileHandler->Delete();
                if($success){
                    $success = $Resource->Delete();
                    if($success){
                        $this->addErrorMsg($Resource->getErrorMsg());
                    }
                }else{
                    $this->addErrorMsg($FileHandler->getErrorMsg());
                }
            }
        }
        return $success;
    }

    public function rejectStudentRecord():bool{
        if($this->DATA['student_id']){
            $school_id = $this->DATA['school_id'];
            $update = array(
                'id'=>$this->DATA['student_id'],
                'school_id'=>0,
                'status'=>'REMOVED',
                'aes_status'=>'Not Approved',
            );
            $this->database->insertArray('students',$update,'id');
            if($school_id){
                $sql = 'DELETE FROM school_students WHERE school_id = :school_id AND student_id = :student_id';
                $params = array(':school_id'=>$school_id, ':student_id'=>$this->DATA['student_id']);
                $this->database->query($sql,$params);
            }
        }
        return true;
    }

    public function removeFromSchool():bool{
        $success = true;
        if(empty($this->getID())){
            $success = false;
            $this->addErrorMsg('AES Student Record not found');
        }
        if($success){
            $student_id = $this->get('student_id');
            if($student_id){
                $update = array('id'=>$student_id,'school_id'=>0,);
                $success = $this->database->insertArray('students',$update,'id');
                $school_id = $this->get('school_id');
                if($school_id){
                    $sql = 'DELETE FROM school_students WHERE school_id = :school_id AND student_id = :student_id';
                    $params = array(':school_id'=>$school_id, ':student_id'=>$student_id);
                    $this->database->query($sql,$params);
                }
            }
        }
        if($success){
            $update = array('id'=>$this->getID(),'status'=>'APPROVED','school_id'=>0);
            $success = $this->database->insertArray(static::$db_table,$update,'id');
        }
        return $success;
    }

    public function updateStudentRecord():bool{
        $DATA = $this->getDATA();
        $aes_status = $DATA['status'] == 'APPROVED' || $DATA['status'] == 'ASSIGNED' ? 'Approved' : 'Not Approved';
        $student_record =  array(
            'id'=>$this->get('student_id'),
            'status'=>'ELIGIBLE',
            'school_id'=>$this->get('school_id'),
            'firstname'=>$this->get('firstname'),
            'lastname'=>$this->get('lastname'),
            'gender'=>$this->get('gender'),
            'age'=>$this->getCurrentAge(),
            'dob'=>$this->get('date_of_birth'),
            'is_aes'=>'AES',
            'aes_status'=>$aes_status,
            'aes_record'=>$this->getID(),
            'entered9th'=>$this->get('entered_9th'),
        );
        if($DATA['status'] != 'ASSIGNED'){
            $student_record['status'] = 'INELIGIBLE';
        }
        if($DATA['status'] == 'REJECTED'){
            $student_record['status'] = 'REMOVED';
        }
        $success = $this->database->insertArray('students',$student_record,'id');
        $student_id = $student_record['id'];
        $status = $student_record['status'];
        $school_id = $student_record['school_id'];
        /*
        $sql = 'INSERT INTO school_students (school_id, student_id, status) VALUES (:school_id, :student_id, :status) ON DUPLICATE KEY UPDATE status = VALUES(status)';
        $params = array(':school_id'=>$school_id,':student_id'=>$student_id,':status'=>$status);
        $this->database->query($sql,$params);
        */
        $xref_id = $this->database->getResultByKey('school_students',array('school_id'=>$school_id,'student_id'=>$student_id),'id');
        $insert = array(
            'id'=>$xref_id,
            'student_id'=>$student_id,
            'school_id'=>$school_id,
            'status'=>$status,
        );
        $this->database->insertArray('school_students',$insert, 'id');
        /*
        $Student = new Student($this->database,$DATA['student_id']);
        $Student->Save($student_record);
        $student_id = $Student->getID();
        */
        if($success){
            $student_id = $student_record['id'];
            $insert = array('id'=>$this->id,'student_id'=>$student_id);
            $success = $this->database->insertArray(static::$db_table,$insert,'id');
        }
        if(!$success){
            $this->addErrorMsg('Unable to Save Student Record',$DATA);
        }
        return  $success;
    } 

    public function enrollStudentRecord(string $school_year):bool{
        $EnrollmentYear = SchoolEnrollment::getDateTimeFromSchoolYear($school_year);

        $insert = [
            'school_id'=>$this->get('school_id'),
            'school_year'=>$school_year,
            'student_id'=>$this->get('student_id'),
            'grade'=>$this->getGrade($EnrollmentYear),
            'age'=>$this->getCurrentAge($EnrollmentYear),
            'status'=>'ELIGIBLE',
        ];
        return $this->database->insertArray('school_enrollment', $insert,'id');
    }

    public function getSportsSelections(string $school_year):array{
        
        $participation = array(
            'GHSA'=>array(),
            'GAPPS'=>array(),
        );
        if(!empty($this->activity_info['GHSA_sports_participation'][$school_year])){
            $participation['GHSA'] = $this->activity_info['GHSA_sports_participation'][$school_year];
        }
        if(!empty($this->activity_info['GAPPS_sports_participation'][$school_year])){
            $participation['GAPPS'] = $this->activity_info['GAPPS_sports_participation'][$school_year];
        }
        return $participation;
    }

    public function getArtsSelections(string $school_year):array{
        $participation = array(
            'GHSA'=>array(),
            'GAPPS'=>array(),
        );
        if(!empty($this->activity_info['GHSA_arts_participation'][$school_year])){
            $participation['GHSA'] = $this->activity_info['GHSA_arts_participation'][$school_year];
        }
        if(!empty($this->activity_info['GAPPS_arts_participation'][$school_year])){
            $participation['GAPPS'] = $this->activity_info['GAPPS_arts_participation'][$school_year];
        }
        return $participation;
    }

    /** @return AESStudent[] */
    public static function getStudents(DatabaseConnectorPDO $DB, ?Array $args = array(), ?Array $order_by = array('firstname','lastname')):array{
        $students = array();
        $results = $DB->getArrayListByKey(static::$db_table,$args,$order_by);
        foreach($results AS $result){
            $students[] = new static($DB,null,$result);
        }
        return $students;
    }

    public static function getSubmissionYears(DatabaseConnectorPDO $DB) :array{
        $years = $DB->getResultList('SELECT DISTINCT submission_year AS year FROM '.static::$db_table.' ORDER BY submission_year DESC');
        return $years;
    }



}