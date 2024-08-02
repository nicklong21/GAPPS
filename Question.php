<?php
namespace ElevenFingersCore\GAPPS;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\Utilities\MessageTrait;

class Question{
    use MessageTrait;
    use InitializeTrait;
    protected $id;
    protected $database;
    protected $question_set;
    protected $question;
    protected $answers;

    protected $title = '';

    protected $response_data;

    protected $DATA;

    static $db_table = 'questions';
    static $template = array(
        'id'=>0,
        'qset'=>null,
        'question'=>'',
        'answer_type'=>null,
        'validation_type'=>null,
        'answers'=>'',
        'zorder'=>0,
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }


    public function getQuestion():string{
        if(empty($this->question)){
            $this->question = $this->DATA['question'];
        }
        return $this->question;
    }

    public function getAnswerType():?string{
        return $this->DATA['answer_type'];
    }

    public function getValidationType():?string{
        return $this->DATA['validation_type'];
    }

    public function getTitle():string{
        return $this->title;
    }

    public function setTitle(string $title){
        $this->title = $title;
    }

    public function getOrder():int{
        return $this->DATA['zorder'];
    }

    public function getNextQuestion():?Question{
        $Question = static::findQuestion($this->database, array('zorder'=>array('>'=>$this->DATA['zorder'])),'zorder ASC');
        return $Question;
    }

    public function getPreviousQuestion():?Question{
        $Question = static::findQuestion($this->database, array('zorder'=>array('<'=>$this->DATA['zorder'])),'zorder DESC');
        return $Question;
    }

    public function isLastQuestion():bool{
        $NextQuestion = $this->getNextQuestion();
        return empty($NextQuestion)?true:false;
    }

    public function isFirstQuestion():bool{
        $PreviousQuestion = $this->getPreviousQuestion();
        return empty($PreviousQuestion)?true:false;
    }

    public function getAnswers():array{
        if(empty($this->answers)){
            $this->answers = json_decode($this->DATA['answers'],true);
        }
        return $this->answers;
    }


    public function testAnswer(string|array $response):?bool{
        global $logger;
        $possibleAnswers = $this->getAnswers();
        $correctAnswers = array_filter($possibleAnswers, function($answer) {
                return $answer['is_correct'];
            });
        
        if (empty($correctAnswers)) {
            return null;
        }
        $logger->debug('testAnswer: correctAnswers '.pa($correctAnswers,true));
        $logger->debug('testAnswer: response '.pa($response,true));
        // CASE 1: Response is a string
        if (is_string($response)) {
            if (count($correctAnswers) == 1 && $response == reset($correctAnswers)['answer']) {
                return true;
            }
            return false;
        }
        
        // CASE 2: Response is an array of values
        if (is_array($response)) {
            $correctValues = array_map(function($answer) {
                return $answer['answer'];
            }, $correctAnswers);
    
            $incorrectValues = array_map(function($answer) {
                return $answer['answer'];
            }, array_filter($possibleAnswers, function($answer) {
                return !$answer['is_correct'];
            }));
    
            // Check if all values in response match correct values
            foreach ($response as $value) {
                if (!in_array($value, $correctValues)) {
                    return false;
                }
            }
    
            // Check if no values in response match incorrect values
            foreach ($response as $value) {
                if (in_array($value, $incorrectValues)) {
                    return false;
                }
            }
    
            // Check if no correct values are not matched
            foreach ($correctValues as $value) {
                if (!in_array($value, $response)) {
                    return false;
                }
            }
    
            return true;
        }
    
        return false;

    }

    protected function prepareForSave(?array $data):null|bool|array{
        
        if(empty($data['question'])){
            $this->addErrorMsg('No Question Set');
            return false;
        }
        $answers = !empty($data['test_answers'])?json_decode($data['test_answers']):array();
        $data['answers'] = json_encode($answers);
        if(empty($this->id)){
            $zorder = $this->database->getResult('SELECT max(zorder) AS max FROM '.static::$db_table.' WHERE qset=:qset',array(':qset'=>$data['qset']));
            $data['zorder'] = $zorder+1;
        }
        return $data;
    }

    /** @return Question[] */
    public static function getQuestions(DatabaseConnectorPDO $DB, ?array $filter = array(), null|array|string $order_by = 'zorder'):array{
        $question_data = $DB->getArrayListByKey(static::$db_table, $filter, $order_by);
        $Questions = array();
        foreach($question_data AS $data){
            $Questions[] = new Question($DB, null, $data);
        }
        return $Questions;


    }

    public static function findFirstQuestion($DB, ?array $filter = array()):Question{
        return static::findQuestion($DB, $filter, 'zorder ASC');
    }

    public static function findLastQuestion($DB, ?array $filter = array()):Question{
        return static::findQuestion($DB, $filter, 'zorder DESC');
    }

    public static function findQuestion(DatabaseConnectorPDO $DB, array $filter, null|array|string $order_by=null):?Question{
        $data = $DB->getArrayByKey(static::$db_table,$filter,$order_by);
        $Question = null;
        if($data){
            $Question = new Question($DB, null, $data);
        }
        return $Question;
    }

    public static function updateQuestionOrder(DatabaseConnectorPDO $DB, array $zorder, ?array $filter = array()):bool{
        $question_data = $DB->getArrayListByKey(static::$db_table,$filter);
        foreach($zorder AS $z=>$id){
            foreach($question_data AS $data){
                if($data['id'] == $id){
                    $update = array('id'=>$data['id'],'zorder'=>$z);
                    $DB->insertArray(static::$db_table,$update,'id');
                }
            }
        }
        return true;
    }


}