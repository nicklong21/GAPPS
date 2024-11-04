<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableGroups extends RosterTable{

    protected $groups = [
        'Unassigned'=>[],
    ];

    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Sex',
        'jersey_number'=>'Group',
        'attr1'=>'Alternate',
        'is_aes'=>'Is AES',
    );


    protected function getHTML_default():string{

        $html = '';
        $table_title = !empty($this->title)?'<caption>'.$this->title.'</caption>':'';
        //$thead = '<table class="data-table roster-table">';
        //$thead .= $table_title;
        $thead = '<thead><tr>';

        foreach($this->html_table_labels AS $label){
            $thead .= '<th>'.$label.':</th>';
        }

        if($this->display_style == 'admin'){
            $thead .= '<th>Date Added</th>';
        }
        $thead .= '</tr></thead>
        <tbody>';

        $groups = $this->groups;
        /** @var RosterStudent $Student */
        foreach($this->Students AS $Student){
            $jersey_number = $Student->getDataValue('jersey_number');
            if(!empty($jersey_number)){
                if(empty($groups[$jersey_number])){
                    $groups[$jersey_number] = [];
                }
                $groups[$jersey_number][] = $Student;
            }else{
                $groups['Unassigned'][] = $Student;
            }
        }
        
        foreach($groups AS $group => $Students){
            if(!empty($Students)){
                $html .= '<table class="data-table roster-table">';
                $html .= '<caption>'.$group.'</caption>';
                $html .= $thead;
                foreach($Students AS $Student){
                    if($this->display_style == 'open' || $this->display_style == 'admin'){
                        $html .= $this->getStudentRow_form($Student);
                    }else{
                        $html .= $this->getStudentRow($Student);
                    }
                }
            }
            $html .= '</tbody>
            </table>';
        }
        return $html;
    }

    protected function getStudentRow_form(RosterStudent $Student):string{
        $student_data = $Student->getDATA();
        $student_roster_id = $Student->getID();
        $student_id = $Student->getStudentID();

        $html = '<tr class="member" data-id="'.$student_roster_id.'" data-student_id="'.$student_id.'">';
        foreach($this->html_table_labels AS $key=>$label){
            $html .= '<td>';
            if($key != 'name' && $key != 'lastname' && $key != 'firstname'){
                $html .'<span class="responsive-label">'.$label.':</span>';
            }
            if($key == 'name'){
                $html .= '<button class="jd-ui-button trash button me-3" data-id="'.$student_id.'" data-jd-click="removeRosterStudent"></button>&nbsp;'.$student_data['lastname'].', '.$student_data['firstname'];
            }else if($key == 'jersey_number'){
                $group_select = '<select name="jersey_number">';
                $groups = array_keys($this->groups);
                foreach($groups AS $group_name){
                    $selected = $group_name == $student_data['jersey_number']?'selected="selected"':'';
                    $group_select .= '<option value="'.$group_name.'" '.$selected.'>'.$group_name.'</option>';
                }
                $group_select .= '</select>';
                $html .= $group_select;
            }else if($key == 'attr1'){
                $value = !empty($student_data['attr1'])?'Yes':'No';
                $alternate_select = '<select name="attr1">';
                $alternate_select .= '<option value="No" '.($value=='No'?'selected="selected"':'').'>No</option>';
                $alternate_select .= '<option value="Yes" '.($value=='Yes'?'selected="selected"':'').'>Yes</option>';
                $alternate_select .= '</select>';
                $html .= $alternate_select;
            }else if(!empty($student_data[$key])){
                $html .= $student_data[$key];
            }
            $html .= '</td>';
        }
        if($this->display_style == 'admin'){
            $html .= $this->appendAdminRow($Student);
        }
        $html .= '</tr>';
        return $html;
    }

    public function getGroups():array{
        return $this->groups;
    }
    public function setGroups(array $groups){
        $this->groups = $groups;
    }
    
}