<?php
namespace ElevenFingersCore\GAPPS\Reports;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Report{
    var $title = '';
    var $database = null;
    var $DATA = array();
    
    function __construct(DatabaseConnectorPDO $database){
        
        $this->database = $database;
        
    }
    
    public static function Create($database,$ReportType){
        
        if(!class_exists($ReportType)){
            throw new \Exception('Error: '.$ReportType.' is not a valid Class');
        }
        if(!is_a($ReportType,'Reports',true)){
            throw new \Exception('Error: '.$ReportType.' is not a subclass of Reports');
        }
        $Report = new $ReportType(($database));
        
        return $Report;
        
    }
    
    function configureReport($values){
        
    }
    
    function generateReport(){
        return true;
    }
    
    
    function getReportHTML(){
        return '';
    }
    
    function getReportXML(){
        
    }

    public function getTitle():string{
        return $this->title;
    }
    
    
    public static function UIXViewReportDialogue($options){
        $html = '';
        $html .= '<div id="ViewReportDialogue" class="PopupInter">
        <div class="PopupPanel">
        <span class="hide"></span>
        <div class="title"><h3>'.$options['title'].'</h3></div>
        ';
        
        $html .= '<div class="text-wrap"><div class="report_view"></div></div>';
        $html .= '<p class="eight center message">&nbsp;</p>
        
        <div class="submit clear"><div class="container">
        <span class="button jd-ui-button cancel" data-jd-click="hide"><span>CANCEL</span></span>
                    <span class="spacer"></span>
                    <a class="button update" href=""><span>Print Report</span></a>
                </div>
        
        </div>
        </div>';
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
        $dompdf->stream($this->getTitle());   
        
    }
    
    
    function printReport($spreadsheet,$title){
        
        // Redirect output to a client’s web browser (Xls)
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$title.'.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        exit;    
    }
}