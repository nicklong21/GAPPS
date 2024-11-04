<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Views;
use ElevenFingersCore\Utilities\MessageTrait;

class GameTennisView extends GameView{

    protected $LineDefaultResult = [
        'Line 1'=>'Single',
        'Line 2'=>'Single',
        'Line 3'=>'Single',
        'Line 4'=>'Doubles',
        'Line 5'=>'Doubles',
    ];


    public function getResultsTable():string{
        $game_type = $this->getGameType();
        $teams_data = $this->getTeamInfo();
        $html = '';
        switch($game_type){
            case 'regular35':
                case 'regional35':
                case 'semi-final35':
                case 'championship35':
                    $html .= $this->getBestOfResultsTable($teams_data);
                    break;
                case 'regular8':
                case 'regional8':
                case 'semi-final8':
                case 'championship8': 
                    $html .= $this->get8GameProResultsTable($teams_data);
                    break;
                default:
                    break;
        }
        return $html;
    }

    public function getBestOfResultsTable(array $teams_data):string{
        
        $home = $teams_data['hometeam'];
        $away = $teams_data['awayteam'];
        $home_roster = $home['students'];
        $away_roster = $away['students'];
        $html = '<table class="table game-scores tennis-scores table-bordered table-striped my-3">';
        $html .= '<thead>
            <tr>
            <th rowspan="2" colspan="3">
                <span class="">H - '.$home['school'].' - '.$home['result'].'</span><br/>
                <span class="">A - '.$away['school'].' - '.$away['result'].'</span>
            </th>
            <th colspan="3"># Games Won per Set</th><th rowspan="2">Match</th>
            </tr>
            <tr><th>Set<br/>1</th><th>Set<br/>2</th><th>Set<br/>3</th></tr>
        </thead>';
        $html .= '<tbody>';
        $home_line_data = $home['data'];
        $away_line_data = $away['data'];
        $line_default = $this->LineDefaultResult;
        foreach($line_default AS $line=>$type){
            $html .= '<tr><th rowspan=2>'.$line.' ('.$type.')</th>';
            $home_data = $home_line_data[$line]??[];
            if(!empty($home_data['roster_student_id']) && isset($home_roster[$home_data['roster_student_id']])){
                $home_data['student_name'] = $home_roster[$home_data['roster_student_id']];
            }
            if(!empty($home_data['roster_student_id_2']) && isset($home_roster[$home_data['roster_student_id_2']])){
                $home_data['student_name_2'] = $home_roster[$home_data['roster_student_id_2']];
            }
            $html .= '<td>H</td>'.$this->getBestOfLine($home_data,$type).'</tr>';
            $away_data = $away_line_data[$line]??[];
            if(!empty($away_data['roster_student_id']) && isset($away_roster[$away_data['roster_student_id']])){
                $away_data['student_name'] = $away_roster[$away_data['roster_student_id']];
            }
            if(!empty($away_data['roster_student_id_2']) && isset($away_roster[$away_data['roster_student_id_2']])){
                $away_data['student_name_2'] = $away_roster[$away_data['roster_student_id_2']];
            }
            $html .= '<tr><td>A</td>'.$this->getBestOfLine($away_data,$type).'</tr>';
            $html .= '<tr><td colspan="7" class="bg-primary p-1"></td></tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    protected function getBestOfLine(array $line_data, string $type):string{
        $html = '';
        $student_id = $line_data['student_id']??0;
        $student_name = $line_data['student_name']??'';
        $line_result = $line_data['result']??'';
        if($type == 'Doubles'){
            $student_id_2 = $line_data['student_id_2']??0;
            $student_name_2 = $line_data['student_name_2']??'';
            $html .= '<td class="'.$line_result.'">'.$student_name.'<br/>
                '.$student_name_2.'
                </td>
                ';
        }else{
            $html .= '<td class="'.$line_result.'">'.$student_name.'
                </td>
                ';
        }
        
        $line_scores = $line_data['score']??[];
        for($i = 0; $i < 3; $i++){
            $score = $line_scores[$i]??'';
            $html .= '<td >'.$score.'</td>';
        }
        $html .= '<td class="'.$line_result.'">'.$line_result.'</td>';
        return $html;
    }
    public function get8GameProResultsTable(array $teams_data):string{
        $home = $teams_data['hometeam'];
        $away = $teams_data['awayteam'];
        $home_roster = $home['students'];
        $away_roster = $away['students'];
        
        $html = '<table class="table game-scores tennis-scores table-bordered table-striped my-3">';
        $html .= '<thead>
            <tr>
            <th colspan="2" class="col-6">
                <span class="">H - '.$home['school'].' - '.$home['result'].'</span><br/>
                <span class="">A - '.$away['school'].' - '.$away['result'].'</span>
            </th>
            <th colspan="2"  class="col-6"># Games Won</th>
            <th>Match</th>
            </tr>
        </thead>';
        $html .= '<tbody>';
        $home_line_data = $home['data'];
        $away_line_data = $away['data'];
        $line_default = $this->LineDefaultResult;
        foreach($line_default AS $line=>$type){
            $html .= '<tr><th rowspan=2>'.$line.' ('.$type.')</th>';
            $home_data = $home_line_data[$line]??[];
            if(!empty($home_data['roster_student_id']) && isset($home_roster[$home_data['roster_student_id']])){
                $home_data['student_name'] = $home_roster[$home_data['roster_student_id']];
            }
            if(!empty($home_data['roster_student_id_2']) && isset($home_roster[$home_data['roster_student_id_2']])){
                $home_data['student_name_2'] = $home_roster[$home_data['roster_student_id_2']];
            }
            $html .= '<td>H</td>'.$this->getProLine($home_data,$type).'</tr>';
            $away_data = $away_line_data[$line]??[];
            if(!empty($away_data['roster_student_id']) && isset($away_roster[$away_data['roster_student_id']])){
                $away_data['student_name'] = $away_roster[$away_data['roster_student_id']];
            }
            if(!empty($away_data['roster_student_id_2']) && isset($away_roster[$away_data['roster_student_id_2']])){
                $away_data['student_name_2'] = $away_roster[$away_data['roster_student_id_2']];
            }
            $html .= '<tr><td>A</td>'.$this->getProLine($away_data,$type).'</tr>';
            $html .= '<tr><td colspan="5" class="bg-primary p-1"></td></tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    protected function getProLine(array $line_data, string $type){
        $html = '';
        $student_id = $line_data['student_id']??0;
        $student_name = $line_data['student_name']??'';
        $line_result = $line_data['result']??'';
        if($type == 'Doubles'){
            $student_id_2 = $line_data['student_id_2']??0;
            $student_name_2 = $line_data['student_name_2']??'';
            $html .= '<td class="'.$line_result.'">'.$student_name.'<br/>
                '.$student_name_2.'
                </td>
                ';
        }else{
            $html .= '<td class="'.$line_result.'">'.$student_name.'
                </td>
                ';
        }
        $score = $line_data['score'][0]??'';
        $html .= '<td >'.$score.'</td>';
        $html .= '<td class="'.$line_result.'">'.$line_result.'</td>';
        return $html;
    }


    public function getEditScoreForm():string{
        $game_type = $this->getGameType();
        $teams_data = $this->getTeamInfo();
        $html = '<form class="edit-scores">';
        switch($game_type){
            case 'regular35':
                case 'regional35':
                case 'semi-final35':
                case 'championship35':
                    $html .= $this->getBestOfEditForm($teams_data);
                    break;
                case 'regular8':
                case 'regional8':
                case 'semi-final8':
                case 'championship8': 
                    $html .= $this->get8GameProEditForm($teams_data);
                    break;
                default:
                    break;
        }
        $html .= '</form>';
        return $html;
    }

    protected function getBestOfEditForm(array $teams_data):string{
        $home = $teams_data['hometeam'];
        $away = $teams_data['awayteam'];
        
        
        $html = '<table class="table game-scores tennis-scores table-bordered table-striped my-3">';
        $html .= '<thead>
            <tr>
            <th rowspan="2" colspan="3">
                <span class="">H - '.$home['school'].'</span><br/>
                <span class="">A - '.$away['school'].'</span>
            </th>
            <th colspan="3"># Games Won per Set</th>
            </tr>
            <tr><th>Set<br/>1</th><th>Set<br/>2</th><th>Set<br/>3</th></tr>
        </thead>';
        $html .= '<tbody>';
        $home_line_data = $home['data'];
        $away_line_data = $away['data'];
        $line_default = $this->LineDefaultResult;
        foreach($line_default AS $line=>$type){
            $html .= '<tr><th rowspan=2>'.$line.' ('.$type.')</th>';
            $home_data = $home_line_data[$line]??[];
            $html .= '<td>H</td>'.$this->getBestOfFormLine($home, $line, $home_data,$type).'</tr>';
            $away_data = $away_line_data[$line]??[];
            $html .= '<tr><td>A</td>'.$this->getBestOfFormLine($away, $line, $away_data,$type).'</tr>';
            $html .= '<tr><td colspan="7" class="bg-primary p-1"></td></tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        $html .= '</form>';

        return $html;
    }

    protected function getBestOfFormLine(array $team_data, string $line, array $line_data, string $type):string{
        $html = '';
        
        $team_id = $team_data['id'];
        $student_id = $line_data['roster_student_id']??0;
        $student_name = $line_data['student_name']??'';
        $students = $team_data['students'];
        if(!empty($students)){
            $input = '<select name="line['.$team_id.']['.$line.'][roster_student_id]">';
            $input .= '<option value="0">--- Select Student ---</option>';
            foreach($students AS $id=>$name){
                $selected = ($student_id == $id)?'selected="selected"':'';
                $input .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
            }
            $input .= '</select>';
        }else{
            $input = '<input type="hidden" name="line['.$team_id.']['.$line.'][roster_student_id]" value = "0">
            <input type="text" name="line['.$team_id.']['.$line.'][student_name]" value="'.$student_name.'">';
        }

        if($type == 'Doubles'){
            $student_id_2 = $line_data['roster_student_id_2']??0;
            $student_name_2 = $line_data['student_name_2']??'';

            if(!empty($students)){
                $input2 = '<select name="line['.$team_id.']['.$line.'][roster_student_id_2]">';
                $input2 .= '<option value="0">--- Select Student ---</option>';
                foreach($students AS $id=>$name){
                    $selected = ($student_id_2 == $id)?'selected="selected"':'';
                    $input2 .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
                }
                $input2 .= '</select>';
            }else{
                $input2 = '<input type="hidden" name="line['.$team_id.']['.$line.'][roster_student_id_2]" value = "0">
                <input type="text" name="line['.$team_id.']['.$line.'][student_name_2]" value="'.$student_name_2.'">';
            }

            $html .= '<td>'.$input.'<br/>
                '.$input2.'
                </td>
                ';
        }else{
            $html .= '<td>'.$input.'</td>
                ';
        }
        
        $line_scores = $line_data['score']??[];
        for($i = 0; $i < 3; $i++){
            $score = $line_scores[$i]??'';
            $input = '<input type="number" name="line['.$team_id.']['.$line.'][score]['.$i.']" value="'.$score.'">';
            $html .= '<td >'.$input.'</td>';
        }
        return $html;
    }

    public function get8GameProEditForm(array $teams_data):string{
        $home = $teams_data['hometeam'];
        $away = $teams_data['awayteam'];
        
        $html = '<table class="table game-scores tennis-scores table-bordered table-striped my-3">';
        $html .= '<thead>
            <tr>
            <th colspan="2" class="col-6">
                <span class="">H - '.$home['school'].'</span><br/>
                <span class="">A - '.$away['school'].'</span>
            </th>
            <th colspan="2"  class="col-6"># Games Won</th>
            </tr>
        </thead>';
        $html .= '<tbody>';
        $home_line_data = $home['data'];
        $away_line_data = $away['data'];
        $line_default = $this->LineDefaultResult;
        foreach($line_default AS $line=>$type){
            $html .= '<tr><th rowspan=2>'.$line.' ('.$type.')</th>';
            $home_data = $home_line_data[$line]??[];
            $html .= '<td>H</td>'.$this->getProFormLine($home, $line, $home_data,$type).'</tr>';
            $away_data = $away_line_data[$line]??[];
            $html .= '<tr><td>A</td>'.$this->getProFormLine($away, $line, $away_data,$type).'</tr>';
            $html .= '<tr><td colspan="5" class="bg-primary p-1"></td></tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        return $html;
    }

    protected function getProFormLine(array $team_data, string $line, array $line_data, string $type){
        $html = '';
        $team_id = $team_data['id'];
        $student_id = $line_data['roster_student_id']??0;
        $student_name = $line_data['student_name']??'';
        $students = $team_data['students'];
        if(!empty($students)){
            $input = '<select name="line['.$team_id.']['.$line.'][roster_student_id]">';
            $input .= '<option value="0">--- Select Student ---</option>';
            foreach($students AS $id=>$name){
                $selected = ($student_id == $id)?'selected="selected"':'';
                $input .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
            }
            $input .= '</select>';
        }else{
            $input = '<input type="hidden" name="line['.$team_id.']['.$line.'][roster_student_id]" value = "0">
            <input type="text" name="line['.$team_id.']['.$line.'][student_name]" value="'.$student_name.'">';
        }
        if($type == 'Doubles'){
            $student_id_2 = $line_data['roster_student_id_2']??0;
            $student_name_2 = $line_data['student_name_2']??'';
            if(!empty($students)){
                $input2 = '<select name="line['.$team_id.']['.$line.'][roster_student_id_2]">';
                $input2 .= '<option value="0">--- Select Student ---</option>';
                foreach($students AS $id=>$name){
                    $selected = ($student_id_2 == $id)?'selected="selected"':'';
                    $input2 .= '<option value="'.$id.'" '.$selected.'>'.$name.'</option>';
                }
                $input2 .= '</select>';
            }else{
                $input2 = '<input type="hidden" name="line['.$team_id.']['.$line.'][roster_student_id_2]" value = "0">
                <input type="text" name="line['.$team_id.']['.$line.'][student_name_2]" value="'.$student_name_2.'">';
            }
            $html .= '<td>'.$input.'<br/>
                '.$input2.'
                </td>
                ';
        }else{
            $html .= '<td>'.$input.'</td>
                ';
        }
        $score = $line_data['score'][0]??'';
        $input = '<input type="number" name="line['.$team_id.']['.$line.'][score][0]" value="'.$score.'">';
        $html .= '<td >'.$input.'</td>';
        return $html;
    }


    protected function getTeamInfo():array{
        if(empty($this->teams_info)){
            $Teams = $this->getTeams();
            $Scores = $this->getGameScores();
            $this->teams_info = ['hometeam'=>[],'awayteam'=>[]];
            $roster_students = $this->getTeamRosters();
            foreach($Teams AS $Team){
                $school = $Team->getSchoolName();
                $TeamScore = $Scores[$Team->getID()]??null;
                $score = !empty($TeamScore)?$TeamScore->getScore():'';
                $result = !empty($TeamScore)?$TeamScore->getResult():'';
                $additional_data = !empty($TeamScore)?$TeamScore->getAdditionalData():[];
                $students = $roster_students[$Team->getID()]??[];
                
                $team_data = [
                    'id'=>$Team->getID(),
                    'school'=>$school,
                    'score'=>$score,
                    'result'=>$result,
                    'data'=>$additional_data,
                    'students'=>$students,
                ];
                if($Team->isHomeTeam()){
                    $this->teams_info['hometeam'] = $team_data;
                }else{
                    $this->teams_info['awayteam'] = $team_data;
                }
            }
        }
        return $this->teams_info;
    }


}