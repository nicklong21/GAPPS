<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Views;

use ElevenFingersCore\GAPPS\Sports\Games\Scores\Dependencies\PitchCountFactory;
use ElevenFingersCore\Utilities\MessageTrait;

class GameBaseBallView extends GameView{


    function getEditPitchCountForm(PitchCountFactory $PitchCountFactory):string{
        $game_id = $this->getGameDetail('id');
        $pitch_count_records = $PitchCountFactory->getGamePitchCount($game_id);
        $Teams = $this->getTeams();
        $roster_data = [];
        foreach($Teams AS $Team){
            $school_name = $Team->getSchoolName();
            $Roster = $Team->getRoster();
            if(empty($Roster)){
                continue;
            }
            $Students = $Roster->getRosterStudents();
            $roster_data[$school_name] = [];
            foreach($Students AS $Student){
                $data = $Student->getDATA();
                $roster_student_id = $data['id'];
                $pitch_count_record = $pitch_count_records[$roster_student_id]?$pitch_count_records[$roster_student_id]:['id'=>0,'count'=>0];
                $roster_data[$school_name][] = [
                        'id'=>$data['id'],
                        'firstname'=>$data['firstname'],
                        'lastname'=>$data['lastname'],
                        'student_id'=>$data['student_id'],
                        'pitch_count'=>$pitch_count_record,
                                ];
            }
        }
        $html = '<form class="edit-pitchcount">
        ';
        foreach($roster_data AS $school_name=>$students){
            $html .= '<table class="table table-bordered table-striped mb-3">
            <thead><tr><th colspan="2"><span class="h3 d-block py-3">'.$school_name.'</span></th></tr></thead>
            <tbody>';
            foreach($students AS $student){
                $html .= '<tr>
                <th class="ps-3">'.$student['lastname'].', '.$student['firstname'].'</th>
                <td><input type="number" name="pitch_count['.$student['id'].']" value="'.$student['pitch_count']['count'].'"></td></tr>';
            }
            $html .= '</tbody>';
            $html .= '</table>';
        }
        
        $html .= '</form>';

        return $html;
    }

}