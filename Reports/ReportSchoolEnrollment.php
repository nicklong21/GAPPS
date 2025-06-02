<?php
namespace ElevenFingersCore\GAPPS\Reports;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class ReportSchoolEnrollment extends Report{

    private $school_year;
    var $start_grade = 0;
    var $end_grade = 12;
    var $school_id = '';

    function configureReport($values){
        
        $this->school_year = $values['school_year'];
        $this->start_grade = !empty($values['start_grade'])?intval($values['start_grade']):0;
        $this->end_grade =  !empty($values['end_grade'])?intval($values['end_grade']):12;   
        $this->school_id = !empty($values['school_id'])?$values['school_id']:0;   
      }


      function generateReport(){
        $enrollment_by_school = [];
        $school_param = [];
        if(empty($this->schools_id)){
            $school_param = ['id'=>$this->school_id];
        }
        $schools = $this->database->getResultListByKey('schools',$school_param,'title','id');
        $school_ids = array_keys($schools);
        //asort($schools);
        $grades = [];
        $grade = $this->end_grade;
        while($grade >= $this->start_grade){
            $grades[] = $grade;
            $grade--;
        }
        if(!empty($grades) && !empty($school_ids)){
            $enrollment_args = ['grade'=>['IN'=>$grades],'school_year'=>$this->school_year];
            if(count($school_ids) == 1){
                $school_id = current($school_ids);
                $enrollment_args['school_id'] = $school_id;
            }
            $enrollment_args['status'] = ['!='=>'REMOVED'];
            $enrollment = $this->database->getArrayListByKey('school_enrollment',$enrollment_args,'grade DESC',['key_name'=>'student_id']);
            $student_ids = array_keys($enrollment);
            $students = $this->database->getArrayListByKey('students',['id'=>['IN'=>$student_ids]],['lastname','firstname'],['key_name'=>'id']);

            foreach($schools AS $school_id=>$school_title){
                $enrollment_by_school[$school_id] = ['title'=>$school_title,'enrollment_by_grade'=>array_fill_keys($grades,[])];
            }
            foreach($students AS $student){
                $student_id = $student['id'];
                $data = $enrollment[$student_id];
                $school_id = $data['school_id'];
                $grade = $data['grade'];
                if(empty($enrollment_by_school[$school_id]['enrollment_by_grade'][$grade])){
                    $enrollment_by_school[$school_id]['enrollment_by_grade'][$grade] = [];
                }
                $gender = $student['gender']??null;
                if(is_string($gender) && strlen($gender) > 1){
                    $gender = substr($gender,0,1);
                }
                $enrollment_by_school[$school_id]['enrollment_by_grade'][$grade][] = [
                    'lastname'=>$student['lastname'],
                    'firstname'=>$student['firstname'],
                    'dob'=>$student['dob'],
                    'gender'=>$gender
                ];
            }
            
        }
        $this->DATA = $enrollment_by_school;
        $this->title = 'School Enrollment - '.$this->school_year;
      }

      function getReportHTML(){
        $html = '';
        $html .= '<table class="data-table report school_enrollment"><caption>School Enrollment - '.$this->school_year.'</caption><thead>';
        $html .= '<tr><th>School</th><th>Last Name</th><th>First Name</th><th>DOB</th><th>Gender</th></tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        foreach($this->DATA AS $school){
            
            $html .= '<tr><th>'.$school['title'].'</th><td colspan="4"></td></tr>';
            foreach($school['enrollment_by_grade'] AS $grade=>$students){
                $grade_label = ($grade === 0)?'KINDERGARTEN':'GRADE '.$grade;
            $html .= '<tr><th>&nbsp;</th><th>'.$grade_label.'</th><td colspan="3"></td></tr>';    
    
            foreach($students AS $student){
                $html .= '<tr><td></td><td>'.$student['lastname'].'</td><td>'.$student['firstname'].'</td><td>'.$student['dob'].'</td><td>'.$student['gender'].'</td></tr>';
            }
            $html .= '<tr><td colspan="5"><hr/></td></tr>';
        }
        }
        $html .= '</tbody></table>';
        
        return $html;
    }


    function getReportXML(){
        
        $spreadsheet = new Spreadsheet();
        $title = 'School Enrollment - '.date('Y-m-d');
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
                    ->getCell('D1')->setValue('DOB');
         $spreadsheet->getActiveSheet()
                    ->getCell('E1')->setValue('GENDER');
         
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
             foreach($school['enrollment_by_grade'] AS $grade=>$students){
                $grade_label = ($grade === 0)?'KINDERGARTEN':'GRADE '.$grade;
             $spreadsheet->getActiveSheet()
                     ->getCell('B'.$j)->setValue($grade_label);
             $j++;    
             
             foreach($students AS $student){
                
                 $spreadsheet->getActiveSheet()
                         ->getCell('B'.$j)->setValue($student['lastname']);
                 $spreadsheet->getActiveSheet()
                         ->getCell('C'.$j)->setValue($student['firstname']);
                 $spreadsheet->getActiveSheet()
                         ->getCell('D'.$j)->setValue($student['dob']);
                 $spreadsheet->getActiveSheet()
                         ->getCell('E'.$j)->setValue($student['gender']);
                 $j++;
             }
             }
             
         }
         $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
         $spreadsheet->getActiveSheet()->getStyle('A1:'.'E'.$j)
                 ->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
         $spreadsheet->setActiveSheetIndex(0);
         $this->printReport($spreadsheet,$title);
         
     }


}