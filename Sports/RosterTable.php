<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\GAPPS\Schools\Student;
use ElevenFingersCore\Utilities\MessageTrait;

class RosterTable{
    use MessageTrait;
    protected $Roster;
    protected $display_style = 'default';
    protected $title = '';
    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Gender',
        'jersey_number'=>'Jersey #',
        'is_aes'=>'Is AES',
    );
    protected $is_varsity = true;


    public function __construct(?Roster $Roster = null){
        $this->Roster = $Roster;
    }

    public function getHTML():string{
        $html = '';
        $style = !empty($this->display_style)?$this->display_style:'default';
        if(method_exists($this,'getRosterTableHtml_'.$style)){
            $method = 'getHTML_'.$style;
        }else{
            $method = 'getHTML_default';
        }
        $html = $this->$method();
        return $html;
    }

    protected function getHTML_default():string{

        $html = '';
        $table_title = !empty($this->title)?'<caption>'.$this->title.'</caption>':'';
        $html .= '<table class="data-table roster-table">';
        $html .= $table_title;
        $html .= '<thead><tr>';

        foreach($this->html_table_labels AS $label){
            $html .= '<th>'.$label.':</th>';
        }
        $varsity = $this->getIsVarsityJV();
        $html .= '<th>'.$varsity['label'].'</th>';
        if($this->display_style == 'admin'){
            $html .= '<th>Date Added</th>';
        }
        $html .= '</tr></thead>
        <tbody>';
        $Students = $this->Roster->getRosterStudents();
        
        foreach($Students AS $Student){
            
            if($this->display_style == 'open' || $this->display_style == 'admin'){
                $html .= $this->getStudentRow_form($Student, $varsity);
            }else{
                $html .= $this->getStudentRow($Student, $varsity);
            }
        }
        
        $html .= '</tbody>
        </table>';

        return $html;
    }

    protected function getStudentRow(RosterStudent $Student, array $Varsity):string{
        $status = $Student->getStatus();
        $student_data = $Student->getStudentInfo();
        if($Student->isAES()){
                //$status .= '<br/>AES: '.$student->aes_status;
        }
        $html = '<tr class="member">';
        foreach($this->html_table_labels AS $key=>$label){
            $html .= '<td>';
            if($key != 'name' && $key != 'lastname' && $key != 'firstname'){
                $html .'<span class="responsive-label">'.$label.':</span>';
            }
            if($key == 'name'){
                $html .= $student_data['lastname'].', '.$student_data['firstname'];
            }else if(!empty($student_data[$key])){
                $html .= $student_data[$key];
            }
                    
            $html .= '</td>';
        }
        $is_jv = isset($student_data['is_jv'])?$student_data['is_jv']:0;
        $option = isset($Varsity['select']['options'][$is_jv])?$Varsity['select']['options'][$is_jv]:'';
        $html .= '<td><span class="responsive-label">'.$Varsity['select']['label'].'</span>';
        $html .= $option;
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }

    protected function getStudentRow_form(RosterStudent $Student, array $Varsity):string{

        $html = '';
        $status = $Student->getStatus();
        $student_data = $Student->getStudentInfo();
        $student_roster_id = $Student->getID();
        $student_id = $Student->getStudentID();
        $html .= '<tr class="member" data-id="'.$student_roster_id.'" data-student_id="'.$student_id.'">';
        foreach($this->html_table_labels AS $key=>$label){
            $html .= '<td>';
            if($key != 'name' && $key != 'lastname' && $key != 'firstname'){
                $html .= '<span class="responsive-label">'.$label.':</span>';
            }
            if ($key == 'jersey_number'){
                $jersey_number = isset($student_data['jersey_number'])?$student_data['jersey_number']:'';
                $html .= '<input type="text" size="2" class="small" name="jersey_number" value="'.$jersey_number.'">';
            }elseif($key == 'name'){
                $html .='<button class="jd-ui-button trash button me-3" data-id="'.$student_id.'" data-jd-click="removeRosterStudent"></button>&nbsp;'. $student_data['lastname'].', '.$student_data['firstname'];
            }else if(!empty($student_data[$key])){
                $html .= $student_data[$key];
            }else{
                $html .= '';
            }
            $html .= '</td>';
        }
        $is_jv = isset($student_data['is_jv'])?$student_data['is_jv']:0;
        $html .= '<td><span class="responsive-label">'.$Varsity['select']['label'].'</span>';
        $html .= '<select name="is_jv">
        ';
        foreach($Varsity['select']['options'] AS $i=>$option){
            $selected = ($is_jv == $i)?' selected="selected" ':'';
            $html .= '<option value="'.$i.'" '.$selected.'>'.$option.'</option>';
        }
        $html .= '</select>';
        $html .= '</td>';
        if($this->display_style == 'admin'){
            $html .= '<td>'.$student_data['date_added'];
            if($Student->isNewStudent()){
                $html .= '&nbsp;<span class="new-student-alert alert jd-ui-button clickable" data-jd-click="removeNewStudentAlert" data-id="'.$student_id.'">(!)</span>';
            }
            $html .= '</td>';
        }
        $html .= '</tr>';
        return $html;
    }

    protected function getIsVarsityJV():array{
        $ret = array('label'=>'','select'=>array());
        if($this->is_varsity){
            $ret['label'] = 'JV/Varsity';
            $ret['select'] = array('label'=>'JV/Varsity',
                        'options'=>array(0=>'Varsity', 1=>'JV')
                        );
        }else{
            $ret['label'] = 'A/B Team';
            $ret['select'] = array(
                        'label'=>'A-Team/B-Team',
                        'options'=>array(0=>'A-Team',1=>'B-Team'),
            );
        }
        return $ret;
    }

    public function setRoster(Roster $Roster){
        $this->Roster = $Roster;
    }

    public function setTableLabels(array $labels){
        $this->html_table_labels = $labels;
    }

    public function setIsVarsity(bool $v){
        $this->is_varsity = $v;
    }

    public function setDisplayStyle(string $style){
        $this->display_style = $style;  
    }
    public function setTitle(string $title){
        $this->title = $title;
    }

    public static function UIXEnrollmentAddToRoster(){
        $html = '';
        
        $html .= '<div id="EnrollmentAddToRoster" class="PopupInter">
        <div class="PopupPanel">
        <span class="hide"></span>
        <div class="title"><h3>Add Students to Current Roster</h3></div>
        ';
        
        $html .= '<form id="student_list" class="tabbed-content">
        <p class="instruction">Select Grade</p>
        <ul class="tabs" data-tabgroup="students"></ul>
        <div class="tabgroup"></div>
        </div>
        </form>';

        $html .= '
        
        <div class="submit clear"><div class="container">
        <span class="button jd-ui-button cancel" data-jd-click="hide"><span>CANCEL</span></span>
                    <span class="spacer"></span>
                    <span class="button jd-ui-button update" data-jd-click="previewRosterChanges"><span>ADD SELECTED TO ROSTER</span></span>
                </div>
        
        </form>
        </div>
        </div>';
            
        return $html;
    }


}