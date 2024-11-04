<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableChorus extends RosterTableGroups{

    protected $groups = [
        'Unassigned'=>[],
        'Small'=>[],
        'Large'=>[],
    ];

    protected $instructions = '<p>Middle School and High Schools participating in Chorus events may enter 2 teams: Small and Large Groups. Small Groups must be between 6-16 participants. There is no limit on large groups.</p>
    <p>Students may participate in both Large and Small groups.</p>
    <p>Select the participating students from your school enrollment and indicate whether they are participating in the Large or Small Group chourus. If a student is participating in both groups, add them twice.</p>';

    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Sex',
        'jersey_number'=>'Group',
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
                if($jersey_number == 'Small'){
                    $groups['Small'][] = $Student;
                }else{
                    $groups['Large'][] = $Student;
                }
            }else{
                $groups['Unassigned'][] = $Student;
            }
        }
        
        foreach($groups AS $group => $Students){
            if(!empty($Students)){
                $html .= '<table class="data-table roster-table">';
                $html .= '<caption>'.$group.' Group</caption>';
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
    
}