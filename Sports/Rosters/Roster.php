<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;

use DateTimeImmutable;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\StudentFactory;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\GAPPS\InitializeTrait;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Schools\SchoolFactory;
use ElevenFingersCore\GAPPS\Schools\Student;
use ElevenFingersCore\GAPPS\Sports\Sport;
use ElevenFingersCore\GAPPS\Sports\Season;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;
use ElevenFingersCore\Utilities\SortableObject;

class Roster{
    use MessageTrait;
    use InitializeTrait;
    protected $RosterStudents = array();
    protected $RosterStudentFactory;
    protected $season_id;
    protected $school_id;
    protected $RosterTable;

    function __construct(array $DATA ){
        $this->initialize($DATA);
    }

    public function initialize(array $DATA){
        $this->DATA = array_merge($this->DATA,$DATA);
        $this->id = $this->DATA['id']??0;
        $this->season_id = $this->DATA['season_id']??0;
        $this->school_id = $this->DATA['school_id']??0;
    }

    public function getSeasonID():int{
        return $this->season_id;
    }

    public function setSeasonID(int $id){
        $this->season_id = $id;
    }

    public function getSchoolID():int{  
        return $this->school_id;
    }

    public function setSchoolID(int $id){
        $this->school_id = $id;
    }

    public function getStatus():string{
        return $this->DATA['status']??'';
    }

    public function getNotes():string{
        return $this->DATA['notes']??'';
    }

    /** @return RosterStudent[] */
    public function getRosterStudents():array{
        if(empty($this->RosterStudents)){
            $Factory = $this->getRosterStudentFactory();
            $this->RosterStudents = $Factory->getRosterStudents($this->id);
        }
        return $this->RosterStudents;
    }

    public function setRosterStudents(array $RosterStudents){
        $this->RosterStudents = $RosterStudents;
    }

    public function previewRosterChanges(array $in_roster, array $roster_data, StudentFactory $studentFactory){
        $RosterStudents = $this->getRosterStudentFactory()->getRosterStudentsByStudentIDs($this->getID(),$in_roster);
        $RosterStudentsByStudentID = [];
        foreach($RosterStudents AS $RosterStudent){
            $RosterStudentsByStudentID[$RosterStudent->getStudentID()] = $RosterStudent;
        }
        foreach($in_roster AS $student_id){
            $new_roster_data = isset($roster_data[$student_id])?json_decode($roster_data[$student_id],true):array();
            if(isset($RosterStudentsByStudentID[$student_id])){
                $RosterStudent = $RosterStudentsByStudentID[$student_id];
            }else{
                $RosterStudent = $this->getRosterStudentFactory()->getRosterStudent(0);
                $Student = $studentFactory->getStudent($student_id);
                $RosterStudent->initializeFromStudent($Student);
                $RosterStudentsByStudentID[$Student->getID()] = $RosterStudent;
            }
            $RosterStudent->initialize($new_roster_data);
        }
        $RosterStudents = array_values($RosterStudentsByStudentID);
        $Sortable = new SortableObject('getName');
        $Sortable->Sort($RosterStudents);
        $this->setRosterStudents($RosterStudents);
    }

    public function getRosterTable():RosterTable{
        $Students = $this->getRosterStudents();
        $RosterTable = $this->RosterTable;
        $RosterTable->setStudents($Students);
        return $RosterTable;
    }

    public function setRosterTable(RosterTable $RosterTable){
        $this->RosterTable = $RosterTable;
    }

    public function getRosterStudentFactory():RosterStudentFactory{
        $Factory = $this->RosterStudentFactory;
        return $Factory;
    }

    public function setRosterStudentFactory(RosterStudentFactory $rosterStudentFactory){
        $this->RosterStudentFactory = $rosterStudentFactory;
    }

    public function getEnrollmentHTML(array $EnrollmentByGrade):string{
        $html = '';
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
                                <td><span class="responsive-label">Age: </span>'.$Student->getCurrentAge().'</td>
                                <td><span class="responsive-label">Grade: </span>'.$Student->getGrade().'</td>
                                <td><span class="responsive-label">Is AES: </span>'.($Student->isAES()?'Yes':'No').'</td>
                                </tr>';
            }
            $student_table .= '</tbody></table>';
            
            $html_body .= '<div class="tab-pane fade" id="enrollment_grade_pane_'.$grade.'" role="tabpanel" aria-labelledby="enrollement_grade_tab_'.$grade.'">'.$student_table.'</div>';
            $html_tabs .= '<li class="nav-item" role="presentation"><button class="nav-link" id="enrollement_grade_tab_'.$grade.'" data-bs-toggle="tab" data-bs-target="#enrollment_grade_pane_'.$grade.'" type="button" role="tab" aria-controls="enrollment_grade_pane_'.$grade.'" aria-selected="false">'.$grade.'</button></li>';
        }
        
        $html_tabs .= '</ul>';
        $html .= $html_tabs;
        $html .= $html_body;
        return $html;
    }



}

