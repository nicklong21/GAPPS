<?php
namespace ElevenFingersCore\GAPPS\Reports;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\Export\Report;
use ElevenFingersCore\GAPPS\Schools\SchoolFactory;
use ElevenFingersCore\GAPPS\Sports\Sport;
use ElevenFingersCore\GAPPS\Schools\Coach;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\Utilities\SortableObject;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ElevenFingersCore\Utilities\MessageTrait;
use ElevenFingersCore\Utilities\UtilityFunctions;

class ReportContactListCoaches extends Report{

    protected $Coaches;
    protected $contact_list;
    protected $Sport;


    function setSport(Sport $Sport){
        $this->Sport = $Sport;
    }

    function generateReport(){

        $sport_id = $this->Sport->getID();
        $Coaches = Coach::getSportCoaches($this->database, $sport_id, array(), array('position'=>'HC'));
        $Sortable = new SortableObject('getFullName');
        $Sortable->Sort($Coaches);
        $SchoolFactory = new SchoolFactory($this->database);
        $Schools = $SchoolFactory->getSchools('active');
        $contact_list = array();
        $coaches_by_school = array();
        foreach($Coaches AS $Coach){
            $school_id = $Coach->getSchoolID();
            if(empty($coaches_by_school[$school_id])){
                $coaches_by_school[$school_id] = array();
            }
            $coaches_by_school[$school_id][] = $Coach;
        }
        foreach($Schools AS $School){
            if(!empty($coaches_by_school[$School->getID()])){
                $contact_list[$School->getTitle()] = array();
                /** @var Coach $Coach */
                foreach($coaches_by_school[$School->getID()] AS $Coach){
                    $CoachProfile = $Coach->getProfileObj();
                    $contact_list[$School->getTitle()][] = $CoachProfile->getData();
                }
            }
        }
        $this->contact_list = $contact_list;
        return true;
    }

    function getReportHTML():string{
        $html = '';
        foreach($this->contact_list AS $school=>$coaches){
                $html .= '<li>'.$school.'
                <div class="accounts">';
                foreach($coaches AS $account){
                    $html .= '<h5>'.$account['firstname'].' '.$account['lastname'].'</h5>';
                    $html .= '<a href="mailto:'.$account['email'].'">'.$account['email'].'</a></br>';
                    $html .= $account['phone'].'<br/>';
                }
                $html .= '</div></li>';
            }
        return $html;
    }
    
    function getReportXML(){
        
        $spreadsheet = new Spreadsheet();
        $title = 'Coaches Contacts - '.$this->Sport->getTitle();
        $spreadsheet->getProperties()->setCreator(SITE_NAME)
        ->setLastModifiedBy(SITE_NAME)
        ->setTitle($title);

        $flag = true;
        $j=2;
        $col = 'A';
        $spreadsheet->setActiveSheetIndex(0); 
        
        $spreadsheet->getActiveSheet()
                   ->getCell('A1')->setValue('SCHOOL');
        $col = UtilityFunctions::getNextAlphaSequence($col);
        $spreadsheet->getActiveSheet()
                   ->getCell($col.'1')->setValue('LAST NAME');
        $col = UtilityFunctions::getNextAlphaSequence($col);
        $spreadsheet->getActiveSheet()
                   ->getCell($col.'1')->setValue('FIRST NAME');
        $col = UtilityFunctions::getNextAlphaSequence($col);
        $spreadsheet->getActiveSheet()
                    ->getCell($col.'1')->setValue('PHONE');
        $col = UtilityFunctions::getNextAlphaSequence($col);
        $spreadsheet->getActiveSheet()
                               ->getCell($col.'1')->setValue('EMAIL');
        
        $total_style = array(
            'font'=>array('bold'=>'true'),
            'borders'=>array(
                'outline'=>array(
                    'borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,)
            ),
        );
        
        foreach($this->contact_list AS $school=>$coaches){
            $col = 'A';
            $spreadsheet->getActiveSheet()
                    ->getCell('A'.$j)->setValue($school);
            
            $j++;
            foreach($coaches AS $account){
                $col = 'B';
                $spreadsheet->getActiveSheet()
                        ->getCell($col.$j)->setValue($account['lastname']);
                $col = UtilityFunctions::getNextAlphaSequence($col);
                $spreadsheet->getActiveSheet()
                        ->getCell($col.$j)->setValue($account['firstname']);
                $col = UtilityFunctions::getNextAlphaSequence($col);
                $spreadsheet->getActiveSheet()
                        ->getCell($col.$j)->setValue($account['phone']);
                $col = UtilityFunctions::getNextAlphaSequence($col);
                $spreadsheet->getActiveSheet()
                        ->getCell($col.$j)->setValue($account['email']);
                $j++;
                
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