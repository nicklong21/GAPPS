<?php
namespace ElevenFingersCore\GAPPS\Reports;
use ElevenFingersCore\GAPPS\Sports\SportRegistry;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
require_once(WEB_ROOT.'/vendor/autoload.php');

class ReportParticipation extends Report{
    
    var $season_year = '';
    var $school_id = '';

    private $SportRegistry;

    
    
    function configureReport($values){
        
       $this->season_year = !empty($values['season'])?$values['season']:0;
       $this->school_id = !empty($values['school_id'])?$values['school_id']:0;
       $this->SportRegistry = $values['SportRegistry'];
    }
    
    function generateReport(){
        
        $sports_ordered = $this->database->getResultArrayList('SELECT * FROM sports ORDER BY FIELD(agroup, "Sports", "Academics", "Fine Arts" ), FIELD(zgroup, "High School", "Middle School", "Elementary"), title;',[],'id');
        $sport_order_index = array_keys($sports_ordered);
        
        $seasons_by_id = $this->database->getArrayListByKey('sports_seasons',['year'=>$this->season_year],'id',['key_name'=>'id']);

        $season_ids = array_keys($seasons_by_id);
        $school_param = [];
        if(!empty($this->school_id)){
            $school_param = ['id'=>$this->school_id];
        }
        

        $schools = $this->database->getArrayListByKey('schools',$school_param,'title',['key_name'=>'id']);
        $school_ids = array_keys($schools);

        if(!empty($school_ids) && !empty($season_ids)){

            $school_seasons = $this->database->getArrayListByKey('school_seasons',['school_id'=>['IN'=>$school_ids],'season_id'=>['IN'=>$season_ids]]);

            foreach($school_seasons AS $sch_season){
                $school_id = $sch_season['school_id'];
                $season_id = $sch_season['season_id'];
                $season = $seasons_by_id[$season_id];
                if(empty($schools[$school_id]['seasons'])){
                    $schools[$school_id]['seasons'] = [];
                }$sport = $sports_ordered[$season['sport_id']];
                $schools[$school_id]['seasons'][$season_id] = ['sport_id'=>$sport['id'],'title'=>$sport['title'],'semester'=>$sport['type'],'group'=>$sport['zgroup'],  'status'=>$sch_season['status'],'participating'=>0,'participating_jv'=>0,];
                $schools[$school_id]['total_participation'] = 0;
                $schools[$school_id]['total_jv_participation'] = 0;
                $schools[$school_id]['participating_students'] = [];
                $schools[$school_id]['participating_jv_students'] = [];
            }
            
            $rosters = $this->database->getArrayListByKey('rosters',['school_id'=>['IN'=>$school_ids],'season_id'=>['IN'=>$season_ids]],'id',['key_name'=>'id']);
            $roster_ids = array_keys($rosters);
            if(!empty($roster_ids)){
                $roster_students = $this->database->getArrayListByKey('rosters_students',['roster_id'=>['IN'=>$roster_ids]]);

                foreach($roster_students AS $student){
                    $roster_id = $student['roster_id'];
                    $is_jv = $student['is_jv']?true:false;
                    $roster = $rosters[$roster_id];
                    $school_id = $roster['school_id'];
                    $season_id = $roster['season_id'];
                    $student_id = $student['student_id'];
                    if(empty($schools[$school_id]['total_participation'])){
                        $schools[$school_id]['total_participation'] = 0;
                        $schools[$school_id]['total_jv_participation'] = 0;
                    }
                    $schools[$school_id]['total_participation'] ++;
                    if(empty($schools[$school_id]['seasons'][$season_id]['participating'])){
                        $schools[$school_id]['seasons'][$season_id]['participating'] = 0;
                        $schools[$school_id]['seasons'][$season_id]['participating_jv'] = 0;
                    }
                    $schools[$school_id]['seasons'][$season_id]['participating']++;
                    if(!in_array($student_id,$schools[$school_id]['participating_students'])){
                        $schools[$school_id]['participating_students'][] = $student_id;
                    }
                    if($is_jv){
                        $schools[$school_id]['seasons'][$season_id]['participating_jv']++;
                        $schools[$school_id]['total_jv_participation']++;
                        if(!in_array($student_id,$schools[$school_id]['participating_jv_students'])){
                            $schools[$school_id]['participating_jv_students'][] = $student_id;
                        }
                    }
                }
            }
        }

        // Sort 'seasons' array for each school by sport_id order
        foreach ($schools as $school_id => &$school) {
            if (!empty($school['seasons'])) {
                uasort($school['seasons'], function ($a, $b) use ($sport_order_index) {
                    return array_search($a['sport_id'], $sport_order_index) - array_search($b['sport_id'], $sport_order_index);
                });
            }
        }
    


        $this->DATA = $schools;
        $this->title = $this->season_year.' School Participation';
    }
    
    
    function getReportHTML(){
        
        $html = '';
        
        $html .= '<table class="data-table report school_participation"><caption>'.$this->season_year.' School Participation</caption><thead>';
        $html .= '<tr><th>School</th><th colspan="3">Participation</th></tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach($this->DATA AS $school){
            $total_jv = 0;
            $html .= '<tr><th>'.$school['title'].'</th><td></td><th>Total</th><th>JV</th></tr>';
            if(!empty($school['seasons'])){
                foreach($school['seasons'] AS $season){
                    $participating = $season['participating']??0;
                    $participating_jv = $season['participating_jv']??0;
                    $html .= '<tr><td></td><td>'.$season['title'].' - <em>'.$season['group'].'</em></td><td>'.$participating.'</td><td>'.$participating_jv.'</td></tr>';
                }
                $html .= '<tr><td></td><td><strong>Participation Total:</strong></td><td><strong>'.$school['total_participation'].'</strong></td><td><strong>'.$school['total_jv_participation'].'</strong></td></tr>';
                $html .= '<tr><td></td><td><strong>Participating Students:</strong></td><td><strong>'.count($school['participating_students']).'</strong></td><td><strong>'.count($school['participating_jv_students']).'</strong></td></tr>';

                $html .= '<tr><td colspan="4"><hr/></td></tr>';
            }else{
                $html .= '<tr><td></td><td><strong>Participation Total:</strong></td><td><strong>0</strong></td><td><strong>0</strong></td></tr>';
                $html .= '<tr><td></td><td><strong>Participating Students:</strong></td><td><strong>0</strong></td><td><strong>0</strong></td></tr>';
            }
        }
        $html .= '</tbody></table>';
        return $html;
        
    }
    
    function num2alpha($n)
    {
        for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n%26 + 0x41) . $r;
        return $r;
    }
    
    function getReportXML(){
        
        $spreadsheet = new Spreadsheet();
        $title = 'GAPPS Participation Report - '.$this->season_year;
        // Set document properties
        $spreadsheet->getProperties()->setCreator(SITE_NAME)
        ->setLastModifiedBy(SITE_NAME)
        ->setTitle($title);

        $flag = true;
        $j=2;
        $col = 'A';
        $spreadsheet->setActiveSheetIndex(0); 
        
        $spreadsheet->getActiveSheet()
                   ->getCell('A1')->setValue('SCHOOL');
        $spreadsheet->getActiveSheet()
                   ->getCell('B1')->setValue('PARTICIPATION');
        $spreadsheet->getActiveSheet()
                   ->getCell('D1')->setValue('TOTAL');
        $spreadsheet->getActiveSheet()
                   ->getCell('E1')->setValue('JV');
        
        $total_style = array(
            'font'=>array('bold'=>'true'),
            'borders'=>array(
                'outline'=>array(
                    'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,)
            ),
        );
        foreach($this->DATA AS $school){
            
            $spreadsheet->getActiveSheet()
                    ->getCell('A'.$j)->setValue($school['title']);
            $j++;
            $total_jv = 0;
            foreach($school['seasons'] AS $season){
                $spreadsheet->getActiveSheet()
                        ->getCell('B'.$j)->setValue($season['title']);
                $spreadsheet->getActiveSheet()
                        ->getCell('C'.$j)->setValue($season['status']);
                $spreadsheet->getActiveSheet()
                        ->getCell('D'.$j)->setValue($season['participating']);
                $spreadsheet->getActiveSheet()
                        ->getCell('E'.$j)->setValue($season['participating_jv']);
                $j++;
                
                
            }
            $spreadsheet->getActiveSheet()
                        ->getCell('B'.$j)->setValue('Participation Total:');
            $spreadsheet->getActiveSheet()
                        ->getCell('D'.$j)->setValue($school['total_participation']);
            $spreadsheet->getActiveSheet()
                        ->getStyle('D'.$j)->applyFromArray($total_style);
            $spreadsheet->getActiveSheet()
                        ->getCell('E'.$j)->setValue($school['total_jv_participation']);
            $spreadsheet->getActiveSheet()
                        ->getStyle('E'.$j)->applyFromArray($total_style);
            $j++;

            $spreadsheet->getActiveSheet()
                        ->getCell('B'.$j)->setValue('Participating Students::');
            $spreadsheet->getActiveSheet()
                        ->getCell('D'.$j)->setValue(count($school['participating_students']));
            $spreadsheet->getActiveSheet()
                        ->getStyle('D'.$j)->applyFromArray($total_style);
            $spreadsheet->getActiveSheet()
                        ->getCell('E'.$j)->setValue(count($school['participating_jv_students']));
            $spreadsheet->getActiveSheet()
                        ->getStyle('E'.$j)->applyFromArray($total_style);

            
        }
    $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
    $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
    $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
    $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
    $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
    $spreadsheet->getActiveSheet()->setTitle($this->season_year);
    $spreadsheet->getActiveSheet()->getStyle('A1:'.'E'.$j)
                ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
    $spreadsheet->setActiveSheetIndex(0);
    $this->printReport($spreadsheet,$title);
    }
    
    
}
