<?php
namespace ElevenFingersCore\GAPPS\Reports;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class ReportAESEnrollment extends Report{

    var $start_date = '';
    var $end_date = '';
    var $school_id = '';


    function configureReport($values){
        
        $start = !empty($values['start_date'])?strtotime($values['start_date']):time();
        $end =  !empty($values['end_date'])?strtotime($values['end_date']):time();   
        $this->start_date = date('Y-m-d',$start);
        $this->end_date = date('Y-m-d',$end);
        $this->school_id = !empty($values['school_id'])?$values['school_id']:0;   
      }



      function generateReport(){
        global $logger;


        $school_param = [];
        if(!empty($this->schools_id)){
            $school_param = ['id'=>$this->school_id];
        }
        $schools = $this->database->getArrayListByKey('schools',$school_param,'title',['key_name'=>'id']);
        $school_ids = array_keys($schools);
        //asort($schools);

        $logger->debug('schools',['schools'=>$schools]);
        $sql = 'SELECT * FROM students_aes_2024 WHERE status IN ("APPROVED","ASSIGNED") AND date_updated<=:end_date AND date_updated >= :start_date ';
        $args = array(':start_date'=>$this->start_date, ':end_date'=>$this->end_date);
        if($this->school_id){
            $sql .= ' AND school_id = :school_id ORDER BY school_id, lastname, firstname';
            $args[':school_id'] = $this->school_id;
            
        }else{
            $sql .= ' ORDER BY lastname, firstname';
        }
        
        $students = $this->database->getResultArrayList($sql,$args,'id');
        
        $not_listed = [];
        foreach($students AS $student){
            $school_id = $student['school_id'];
            if(empty($schools[$school_id])){
                $not_listed[] = $student;
            }else{
                if(empty($schools[$school_id]['students'])){
                    $schools[$school_id]['students'] = [];
                }
                $schools[$school_id]['students'][] = $student;
            }
        }

        if(!empty($not_listed)){
            $schools['not_listed'] = ['title'=>'Not Listed','students'=>$not_listed];
        }
        $this->DATA = $schools;  
        $this->title = 'AES Enrollment - '.$this->start_date.' - '.$this->end_date;
    }

    function getReportHTML(){
        $html = '';
        $html .= '<table class="data-table report aes_enrollment"><caption>AES Enrollment - '.$this->start_date.' - '.$this->end_date.'</caption><thead>';
        $html .= '<tr><th>School</th><th>Last Name</th><th>First Name</th><th>Status</th></tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach($this->DATA AS $school){
            $total = !empty($school['students'])?count($school['students']):0;
            $html .= '<tr><th>'.$school['title'].'</th><td colspan="3"><strong>Total Enrollment: '.$total.'</strong></td></tr>';
            foreach($school['students'] AS $student){
                $html .= '<tr><td></td><td>'.$student['lastname'].'</td><td>'.$student['firstname'].'</td><td>'.($student['status']).'</td></tr>';
            }
            $html .= '<tr><td colspan="4"><hr/></td></tr>';
            
        }
        $html .= '</tbody></table>';
        
        return $html;
    }


    function getReportXML(){
        
        $spreadsheet = new Spreadsheet();
        $title = 'AES Enrollment - '.$this->start_date.' - '.$this->end_date;
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
                    ->getCell('B1')->setValue('LAST NAME');
         $spreadsheet->getActiveSheet()
                    ->getCell('C1')->setValue('FIRST NAME');
         $spreadsheet->getActiveSheet()
                    ->getCell('D1')->setValue('STATUS');
         
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
             $spreadsheet->getActiveSheet()
                     ->getCell('B'.$j)->setValue('TOTAL ENROLLMENT: '.count($school['students']));
             $spreadsheet->getActiveSheet()
                         ->getStyle('B'.$j)->applyFromArray($total_style);
             $j++;
             foreach($school['students'] AS $student){
                 $spreadsheet->getActiveSheet()
                         ->getCell('B'.$j)->setValue($student['lastname']);
                 $spreadsheet->getActiveSheet()
                         ->getCell('C'.$j)->setValue($student['firstname']);
                 $spreadsheet->getActiveSheet()
                         ->getCell('D'.$j)->setValue(($student['status']));
                 $j++;
             }
             
             
         }
         $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getStyle('A1:'.'D'.$j)
                 ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
         $spreadsheet->setActiveSheetIndex(0);
         $this->printReport($spreadsheet,$title);
         
     }

}