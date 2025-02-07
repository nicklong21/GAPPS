<?php
namespace ElevenFingersCore\GAPPS\Reports;
use Dompdf\Dompdf;
use ElevenFingersCore\GAPPS\Schools\School;
use ElevenFingersCore\GAPPS\Schools\SchoolFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterFactory;
use ElevenFingersCore\GAPPS\Sports\SportFactory;
use ElevenFingersCore\GAPPS\Sports\SportRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class ReportAcademicEvent extends Report{


    var $season_year = 0;
    var $activity_id = 0;
    var $season_id = '';
    private $Rosters = [];
    var $title = '';

    private SportRegistry $Registry;

    
    function configureReport($values){
        $this->season_year = !empty($values['season'])?$values['season']:0;
        $this->activity_id = !empty($values['sport_id'])?$values['sport_id']:0;
        $this->Registry = $values['Registry'];
    }
    
    function generateReport(){
        $SportFactory = new SportFactory($this->database,$this->Registry);
        $Sport = $SportFactory->getSport($this->activity_id);
        $SeasonFactory = $Sport->getSeasonFactory();
        $Season = $SeasonFactory->getSportSeasonForSchoolYear($this->activity_id,$this->season_year);
        $RosterFactory = $Sport->getRosterFactory();
        $Rosters = $RosterFactory->getSeasonRosters($Season->getID());
        /** @var \ElevenFingersCore\GAPPS\Sports\Rosters\Roster $Roster */
        $rosters_by_school_id = [];
        foreach($Rosters AS $Roster){
            $school_id = $Roster->getSchoolID();
            $rosters_by_school_id[$school_id] = $Roster;
        }
        $school_ids = array_keys($rosters_by_school_id);
        $SchoolFactory = new SchoolFactory($this->database);
        $Schools = $SchoolFactory->getSchoolsByIds($school_ids);
        $RostersBySchool = [];
        foreach($Schools AS $School){
            $Roster = $rosters_by_school_id[$School->getID()];
            $RostersBySchool[] = ['School'=>$School,'Roster'=>$Roster];
        }
        $this->Rosters = $RostersBySchool;
        $this->title = 'Participation Report: '.$Season->getTitle();
        return true;
    }
    
    function getReportHTML(){
        
        $html = '';
        foreach($this->Rosters AS $R){
            /**
             * @var School $School;
             */
            $School = $R['School'];
            /**
             * @var \ElevenFingersCore\GAPPS\Sports\Rosters\Roster $Roster;
             */
            $Roster = $R['Roster'];
            $html .= '<h2>'.$School->getTitle().'</h2>';
            $RosterTable = $Roster->getRosterTable();
            $RosterTable->setDisplayStyle('default');
            $html .= $RosterTable->getHTML();
            $html .= '<hr/>';
        }

        return $html;
        
    }
    
    function getReportPDF(){
        
        $html = '';
        $html ='<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
.content-wrap{max-width:1280px; margin-left:auto; margin-right:auto;}
.data-table {border:1px solid #343434; border-collapse: collapse; width:100%;}
.data-table td,
.data-table th{border:1px solid #343434; padding:8px 12px; font-size: 14px;}
.responsive-label{display:none;}
</style>
</head>
<body><div class="content-wrap">';
        $html .= $this->getReportHTML();
        $html .= '</div></body></html>';
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream($this->title);   
        
    }
}