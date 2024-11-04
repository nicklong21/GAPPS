<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables;

use ElevenFingersCore\GAPPS\Schools\Student;
use ElevenFingersCore\GAPPS\Schools\StudentFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
use ElevenFingersCore\Utilities\MessageTrait;

class RosterTable{
    use MessageTrait;
    protected $Students;
    protected $display_style = 'default';
    protected $title = '';
    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Sex',
        'jersey_number'=>'Jersey #',
        'is_aes'=>'Is AES',
        'is_jv'=>'JV/Varsity',
    );

    protected $instructions = '';

    protected $is_varsity = true;

    protected $varsity_values = array(
        'Varsity',
        'JV'
    );
    protected $varsity_label = 'JV/Varsity';

    protected $StudentFactory;


    public function __construct(){
    }

    public function setStudents(array $Students){
        $this->Students = $Students;
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

        foreach($this->html_table_labels AS $k=>$label){
            if($k == 'is_jv'){
                $html .= '<th>'.$this->getVarsityLabel().'</th>';
            }else{
                $html .= '<th>'.$label.':</th>';
            }
        }

        if($this->display_style == 'admin'){
            $html .= '<th>Date Added</th>';
        }
        $html .= '</tr></thead>
        <tbody>';
        
        
        foreach($this->Students AS $Student){
            
            if($this->display_style == 'open' || $this->display_style == 'admin'){
                $html .= $this->getStudentRow_form($Student);
            }else{
                $html .= $this->getStudentRow($Student);
            }
        }
        
        $html .= '</tbody>
        </table>';

        return $html;
    }

    protected function getStudentRow(RosterStudent $Student):string{
        $status = $Student->getStatus();
        $student_data = $Student->getDATA();
        if($Student->isAES()){
                //$status .= '<br/>AES: '.$student->aes_status;
        }
        $html = '<tr class="member">';
        foreach($this->html_table_labels AS $key=>$label){
            $html .= '<td>';
            if($key != 'name' && $key != 'lastname' && $key != 'firstname' && $key != 'is_jv'){
                $html .'<span class="responsive-label">'.$label.':</span>';
            }
            if($key == 'name'){
                $html .= $student_data['lastname'].', '.$student_data['firstname'];
            }else if($key == 'is_jv'){
                $is_jv = !empty($student_data['is_jv'])?1:0;
                $html .= '<span class="responsive-label">'.$this->html_table_labels['is_jv'].'</span>';
                $html .=  $this->getVarsityValue($is_jv);
            }else if(!empty($student_data[$key])){
                $html .= $student_data[$key];
            }
                    
            $html .= '</td>';
        }
        
        $html .= '</td>';
        $html .= '</tr>';
        return $html;
    }

    protected function getStudentRow_form(RosterStudent $Student):string{

        $html = '';
        $student_data = $Student->getDATA();
        $student_roster_id = $Student->getID();
        $student_id = $Student->getStudentID();
        $html .= '<tr class="member" data-id="'.$student_roster_id.'" data-student_id="'.$student_id.'">';
        foreach($this->html_table_labels AS $key=>$label){
            $html .= '<td>';
            if($key != 'name' && $key != 'lastname' && $key != 'firstname' && $key != 'is_jv'){
                $html .= '<span class="responsive-label">'.$label.':</span>';
            }
            if ($key == 'jersey_number'){
                $jersey_number = isset($student_data['jersey_number'])?$student_data['jersey_number']:'';
                $html .= '<input type="text" size="2" class="" name="jersey_number" value="'.$jersey_number.'">';
            }elseif($key == 'name'){
                $html .='<button class="jd-ui-button trash button me-3" data-id="'.$student_id.'" data-jd-click="removeRosterStudent"></button>&nbsp;'. $student_data['lastname'].', '.$student_data['firstname'];
            }else if($key == 'is_jv'){
                $is_jv = !empty($student_data['is_jv'])?1:0;
                $html .= '<span class="responsive-label">'.$this->getVarsityLabel().'</span>';
                $html .= $this->getVarsitySelect($is_jv);
            }else if(!empty($student_data[$key])){
                $html .= $student_data[$key];
            }else{
                $html .= '';
            }
            $html .= '</td>';
        }
        if($this->display_style == 'admin'){
            $html .= $this->appendAdminRow($Student);
        }
        $html .= '</tr>';
        return $html;
    }

    protected function appendAdminRow(RosterStudent $Student):string{
        $student_id = $Student->getStudentID();
        $student_data = $Student->getDATA();
        $is_new_student = $this->getStudentFactory()->getIsNewStudent($student_id);
        $html = '<td>'.$student_data['date_added'];
        if($is_new_student){
            $html .= '&nbsp;<span class="new-student-alert alert jd-ui-button clickable" data-jd-click="removeNewStudentAlert" data-id="'.$student_id.'">(!)</span>';
        }
        $html .= '</td>';
        return $html;
    }

    public function getIsVarsity():bool{
        return $this->is_varsity;
    }

    public function setIsVarsity(bool $value){
        $this->is_varsity = $value;
    }

    public function getVarsityValues():array{
        return $this->varsity_values;
    }

    public function setVarsityValues(array $values){
        $this->varsity_values = $values;
    }

    public function getVarsityLabel():string{
        return $this->varsity_label;
    }

    public function setVarsityLabel(string $label){
        $this->varsity_label = $label;
    }

    protected function getVarsityValue(int $is_jv):string{
        $varsity_values = $this->getVarsityValues();
        $value  = isset($varsity_values[$is_jv])?$varsity_values[$is_jv]:$varsity_values[0];
        return $value;
    }

    protected function getVarsitySelect(int $is_jv):string{
        $html = '<select name="is_jv">';
        $varsity_values = $this->getVarsityValues();
        foreach($varsity_values AS $k=>$value){
            $selected = $is_jv == $k?'selected="selected"':'';
            $html .= '<option value="'.$k.'" '.$selected.'>'.$value.'</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public function setStudentFactory(StudentFactory $StudentFactory){
        $this->StudentFactory = $StudentFactory;
    }

    public function getStudentFactory():StudentFactory{
        return $this->StudentFactory;
    }

    public function setTableLabels(array $labels){
        $this->html_table_labels = $labels;
    }

    public function getTableLabels():array{
        return $this->html_table_labels;
    }

    public function hideAESStatus(){
        $html_table_labels = $this->getTableLabels();
        if(isset($html_table_labels['is_aes'])){
            unset($html_table_labels['is_aes']);
        }
        $this->setTableLabels($html_table_labels);
    }

    public function setDisplayStyle(string $style){
        $this->display_style = $style;  
    }
    public function setTitle(string $title){
        $this->title = $title;
    }

    public function getTitle():string{
        return $this->title;
    }

    public function setInstructions(string $instructions){
        $this->instructions = $instructions;
    }

    public function getInstructions():string{
        return $this->instructions;
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