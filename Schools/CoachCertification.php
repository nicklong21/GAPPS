<?php
namespace ElevenFingersCore\GAPPS\Schools;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Resources\ResourceImage;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\Question;


class CoachCertification{
    use MessageTrait;
    use InitializeTrait;

    protected $database;
    protected $DATA;
    protected $id;

    protected $CoachAcct;

    protected $test_results;

    protected $profile_results;

    protected $Photo;

    protected $DateStarted;

    protected $DateCompleted;

    static $db_table = 'coach_certification';
    static $template = array(
        'id'=>0,
        'acct_id'=>0,
        'school_year'=>'',
        'approved'=>null,
        'status'=>'',
        'date_started'=>'',
        'date_completed'=>'',
        'test'=>'',
        'profile'=>'',
        'photo'=>'',
        'payment'=>null,
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }


    function getSchoolYear():string{
        return $this->DATA['school_year'];
    }

    function getCoachAcct():Coach{
        if(empty($this->CoachAcct)){
            $this->CoachAcct = new Coach($this->database, $this->DATA['acct_id']);
        }
        return $this->CoachAcct;
    }

    function setSchoolYear(string $school_year){
        $this->DATA['school_year'] = $school_year;
    }

    function setAcctID(int $acct_id){
        $this->DATA['acct_id'] = $acct_id;
    }

    function getStatus():string{
        return $this->DATA['status'];
    }

    function getTestResults():array{
        if(empty($this->test_results)){
            $this->test_results = !empty($this->DATA['test'])?json_decode($this->DATA['test'],true):array();
        }
        return $this->test_results;
    }


    /*
    [
    27 => [question_id=>27, question=>'', answer=>'']
    ]
    */

    function getProfileResults():array{
        if(empty($this->profile_results)){
            $this->profile_results = !empty($this->DATA['profile'])?json_decode($this->DATA['profile'],true):array();
        }
        return $this->profile_results;
    }

    function setTestResults(array $results){
        $this->test_results = $results;
    }

    function setProfileResults(array $results){
        $this->profile_results = $results;
    }

    function addResponse(Question $Question, null|string|array $value){
        $question = $Question->getQuestion();
        $question_id = $Question->getID();
        $test_results = $this->getTestResults();
        $test_results[$question_id] = array('question_id'=>$question_id,'question'=>$question, 'answer'=>$value);
        $this->setTestResults($test_results);
        $insert = array(
            'id'=>$this->getID(),
            'test'=>json_encode($test_results),
        );
        $this->database->insertArray(static::$db_table,$insert,'id');
    }

    function addProfileResponse(Question $Question, null|string|array $value){
        $question = $Question->getQuestion();
        $question_id = $Question->getID();
        $test_results = $this->getProfileResults();
        $test_results[$question_id] = array('question_id'=>$question_id,'question'=>$question, 'answer'=>$value);
        $this->setProfileResults($test_results);
        $insert = array(
            'id'=>$this->getID(),
            'profile'=>json_encode($test_results),
        );
        $this->database->insertArray(static::$db_table,$insert,'id');
    }

    function getPhoto():?ResourceImage{
        if(empty($this->Photo)){
            if(!empty($this->DATA['photo'])){
                $this->Photo = new ResourceImage($this->database, $this->DATA['photo']);
            }else{
                $this->Photo = null;
            }
        }
        return $this->Photo;
    }

    function setPhoto(ResourceImage $Resource){
        $this->Photo = $Resource;
    }

    function getDateStarted(?string $format = null):null|DateTimeImmutable|string{
        $date = null;
        if(empty($this->DateStarted)){
            if(!empty($this->DATA['date_started'])){
                $this->DateStarted = new DateTimeImmutable($this->DATA['date_started']);
            }
            $date = $this->DateStarted;
        }
        if(!empty($format) && !empty($date)){
            $date = $date->format($format);
        }
        return $date;
    }

    function getDateCompleted(?string $format = null):null|DateTimeImmutable|string{
        $date = null;
        if(empty($this->DateCompleted)){
            if(!empty($this->DATA['date_completed'])){
                $this->DateCompleted = new DateTimeImmutable($this->DATA['date_completed']);
            }
            $date = $this->DateCompleted;
        }
        if(!empty($format) && !empty($date)){
            $date = $date->format($format);
        }
        return $date;
    }

    function isCompleted():bool{
        $DateCompleted = $this->getDateCompleted();
        return !empty($DateCompleted)?true:false;
    }

    function get(string $key):null|string|int{
        $val = null; 
        if(isset($this->DATA[$key])){
            $val = $this->DATA[$key];
        }
        return $val;
    }

    /**
     * @param \ElevenFingersCore\Database\DatabaseConnectorPDO $DB
     * @param array $filter
     * @return CoachCertification[]
     */
    public static function findCertifications(DatabaseConnectorPDO $DB, array $filter):array{
        $certification_data = $DB->getArrayListByKey(static::$db_table,$filter);
        $Certifications = array();
        foreach($certification_data AS $data){
            $Certifications[] = new CoachCertification($DB, null, $data);
        }
        return $Certifications;
    }

}