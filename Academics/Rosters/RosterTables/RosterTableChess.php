<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableChess extends RosterTableGroups{

    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Sex',
        'jersey_number'=>'Team',
        'attr1'=>'Alternate',
        'is_aes'=>'Is AES',
    );

    protected $instructions = '
        <p>Schools participating in Chess events may enter multiple teams. Teams may have 4 participants and multiple alternates.</p>
        <p>Select the participating students from your school enrollment and indicate a name for each team that will perform separately and whether this student is a participant or alternate.</p>';


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
                $value = !empty($student_data['jersey_number'])?$student_data['jersey_number']:'';
                $html .= '<input type="text" name="jersey_number" value="'.$value.'">';
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

}