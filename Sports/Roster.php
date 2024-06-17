<?php
namespace ElevenFingersCore\GAPPS\Sports;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\InitializeTrait;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Schools\Student;
use ElevenFingersCore\Utilities\SortableObject;

class Roster{
    use MessageTrait;
    use InitializeTrait;
    protected $database;
    protected $id = 0;
    protected $DATA;
    protected $RosterStudents = array();
    protected $Sport;
    protected $Season;
    protected $School;

    protected $RosterTable;
    static $db_table = 'rosters';
    static $template = array(
        'id'=>0,
        'season_id'=>0,
        'school_id'=>0,
        'title'=>'',
        'status'=>NULL,
    );

    function __construct(DatabaseConnectorPDO $DB, ?int $id = 0, ?array $DATA = array()){
        $this->database = $DB;
        $this->initialize($id,$DATA);
    }

    public function getID():int{
        return $this->id;
    }

    public function getDATA():array{
        return $this->DATA;
    }

    public function getSeasonID():int{
        return $this->DATA['season_id'];
    }

    public function getSchoolID():int{  
        return $this->DATA['school_id'];
    }

    public function getStatus():?string{
        return $this->DATA['status'];
    }

    public function getSeasonDate(?string $format = null):string|DateTimeImmutable{
        $Season = $this->getSeason();
        $SeasonDate = $Season->getDateStart($format);
        return $SeasonDate;
    }

    /** @return RosterStudent[] */
    public function getRosterStudents():array{
        if(empty($this->RosterStudents)){
            $RosterStudents = RosterStudent::getRosterStudents($this->database, $this->id);
            $School = $this->getSchool();
            $Enrollment = $School->getEnrollment();
            $StudentsByID = array();
            foreach($Enrollment AS $Student){
                $StudentsByID[$Student->getID()] = $Student;
            }
            foreach($RosterStudents AS $RosterStudent){
                if(isset($StudentsByID[$RosterStudent->getStudentID()])){
                    $RosterStudent->setStudent($StudentsByID[$RosterStudent->getStudentID()]);
                }
            }
            $Sortable = new SortableObject('getName');
            $Sortable->Sort($RosterStudents);
            $this->RosterStudents = $RosterStudents;
        }
        return $this->RosterStudents;
    }

    public function setRosterStudents(array $RosterStudents){
        $this->RosterStudents = $RosterStudents;
    }

    public function previewRosterChanges(array $in_roster, array $roster_data){
        $RosterStudents = array();
        foreach($in_roster AS $student_id){
            $new_roster_data = isset($roster_data[$student_id])?json_decode($roster_data[$student_id],true):array();
            $RosterStudent = RosterStudent::findRosterStudent($this->database, $this->id, $student_id);
            $SeasonDate = $this->getSeasonDate();
            if(!$RosterStudent->getID()){
                $RosterStudent->initializeFromStudent($SeasonDate);
            }
            $RosterStudent->setDATA($new_roster_data);
            $RosterStudents[] = $RosterStudent;
        }
        $Sortable = new SortableObject('getName');
        $Sortable->Sort($RosterStudents);
        $this->setRosterStudents($RosterStudents);
    }

    public function updateRosterStudents($roster_data):bool{
        $RosterStudents = $this->getRosterStudents();
        $any_error = false;
        foreach($RosterStudents AS $RosterStudent){
            $student_id = $RosterStudent->getStudentID();
            if(isset($roster_data[$student_id])){
                $new_student_data = json_decode($roster_data[$student_id],true);
                $success = $RosterStudent->Save($new_student_data);
                if(!$success){
                    $any_error = true;
                    $this->addErrorMsg($RosterStudent->getErrorMsg());
                }
                unset($roster_data[$student_id]);
            }else{
                $RosterStudent->Delete();
            }
        }
        if(!empty($roster_data)){
            $SeasonDate = $this->getSeasonDate();
            foreach($roster_data AS $student_id=>$jsonstr){
                $data = json_decode($jsonstr,true);
                $data['roster_id'] = $this->id;
                $data['date_added'] = date('Y-m-d H:i:s');
                $RosterStudent = new RosterStudent($this->database);
                $RosterStudent->setDATA($data);
                $RosterStudent->initializeFromStudent($SeasonDate);
                $success = $RosterStudent->Save();
                if(!$success){
                    $any_error = true;
                    $this->addErrorMsg($RosterStudent->getErrorMsg());
                }
            }
        }
        $this->RosterStudents = null;
        return !$any_error;
    }

    public function getSport():Sport{
        if(empty($this->Sport)){
            $Season = $this->getSeason();
            $this->Sport = $Season->getSport();
        }
        return $this->Sport;
    }

    public function setSport(Sport $Sport){
        $this->Sport = $Sport;
    }

    public function getSeason():Season{
        if(empty($this->Season)){
            $season_id = $this->DATA['season_id'];
            $this->Season = new Season($this->database, $season_id);
        }
        return $this->Season;
    }
    public function setSeason(Season $Season){
        $this->Season = $Season;
        $this->DATA['season_id'] = $Season->getID();
    }

    public function getSchool():School{
        if(empty($this->School)){
            $school_id = $this->DATA['school_id'];
            $this->School = new School($this->database, $school_id);
        }
        return $this->School;
    }

    public function setSchool(School $School){
        $this->School = $School;
        $this->DATA['school_id'] = $School->getID();
    }

    public function getRosterTable():RosterTable{
        if(empty($this->RosterTable)){
            $this->RosterTable = new RosterTable($this);
        }
        return $this->RosterTable;
    }

    public function setRosterTable(RosterTable $RosterTable){
        $RosterTable->setRoster($this);
        $this->RosterTable = $RosterTable;
    }

    public function getEnrollmentHTML():string{
        $html = '';
        $grades = array('PreK',1,2,3,4,5,6,7,8,9,10,11,12);
        $School = $this->getSchool();
        if(!empty($School)){
            $EnrollmentByGrade = $School->getEnrollmentByGrade();
            $Season = $this->getSeason();
            $StartDate = $Season->getDateStart();
            $html_tabs = '<ul class="nav nav-tabs" role="tablist">';
            $html_body = '<div class="tab-content">';
            foreach($EnrollmentByGrade AS $grade=>$Students){
                $student_table = '<table class="data-table student-add-to-roster">';
                $student_table .= '<thead><tr>
                                        <th>Add to Roster</th>
                                        <th>Student Name</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Grade</th>
                                        <th>AES</th>
                                        </tr></thead>';
                $student_table .= '<tbody>';
                /** @var Student $Student */
                foreach($Students AS $Student){
                    $student_table .= '<tr>';
                    $student_table .= '<td><span class="responsive-label">Add To Roster: </span><input type="checkbox" name="in_roster[]" value="'.$Student->getID().'"></td>';
                    $student_table .= '<td>'.$Student->getFullName().'</td>
                                    
                                    <td><span class="responsive-label">Gender: </span>'.$Student->getGender().'</td>
                                    <td><span class="responsive-label">Age: </span>'.$Student->getCurrentAge($StartDate).'</td>
                                    <td><span class="responsive-label">Grade: </span>'.$Student->getGrade().'</td>
                                    <td><span class="responsive-label">Is AES: </span>'.($Student->isAES()?'Yes':'No').'</td>
                                    </tr>';
                }
                $student_table .= '</tbody></table>';
                
                $html_body .= '<div class="tab-pane fade" id="enrollment_grade_pane_'.$grade.'" role="tabpanel" aria-labelledby="enrollement_grade_tab_'.$grade.'">'.$student_table.'</div>';
                $html_tabs .= '<li class="nav-item" role="presentation"><button class="nav-link" id="enrollement_grade_tab_'.$grade.'" data-bs-toggle="tab" data-bs-target="#enrollment_grade_pane_'.$grade.'" type="button" role="tab" aria-controls="enrollment_grade_pane_'.$grade.'" aria-selected="false">'.$grade.'</button></li>';
            }
        }
        $html_tabs .= '</ul>';
        $html .= $html_tabs;
        $html .= $html_body;
        return $html;
    }

/** @return Roster[] */
    static function getRosters(DatabaseConnectorPDO $DB, Array $filter){
        $data = $DB->getArrayListByKey(static::$db_table, $filter);
        $Rosters = array();
        foreach($data AS $DATA){
            $Rosters[] = new static($DB, null, $DATA);
        }
        return $Rosters;
    }

    static function findRoster(DatabaseConnectorPDO $DB, Array $filter):?Roster{
        $Roster = null;
        $data = $DB->getArrayByKey(static::$db_table, $filter);
        if($data){
            $Roster = new static($DB, null, $data);
        }
        return $Roster;
    }


}

