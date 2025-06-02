<?php
namespace ElevenFingersCore\GAPPS\Sports\Seasons\Views;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Schools\SchoolFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Divisions\Division;
use ElevenFingersCore\GAPPS\Sports\Seasons\Regions\Region;
use ElevenFingersCore\GAPPS\Sports\Seasons\Results\SeasonResults;
use ElevenFingersCore\GAPPS\Sports\Seasons\Season;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonFactory;

class SeasonView{
    protected $database;
    protected $Season;
    protected $SeasonSchools;
    protected $Regions;
    protected $Divisions;

    protected $SeasonResults;

    function __construct(DatabaseConnectorPDO $DB, Season $Season){
        $this->database = $DB;
        $this->Season = $Season;
    }

    public function getSeasonWinLossHTML(?Division $Division = null, ?Region $Region = null):string{
        $html = '';
        $participating_school_data = $this->getSortedSeasonSchoolsData();
        $SeasonResults = $this->getSeasonResults();
        
        $region_id = !empty($Region)?$Region->getID():0;
        $division_id = !empty($Division)?$Division->getID():0;
        $results_by_school_id = $SeasonResults->getSeasonResults($division_id,$region_id);
        
        if(!empty($Division)){
            $division_title = $Division->getTitle();
            $school_data = [
                $division_title =>$participating_school_data[$division_title]
            ];
            if(!empty($Region)){
                $region_title = $Region->getTitle();
                $school_data = [
                    $division_title=>[
                        $region_title=>$participating_school_data[$division_title][$region_title]
                        ]
                    ];
            }
        }else{
            $school_data = $participating_school_data;
        }

        $all_types = ['overall','regional'];
        $display_types = $all_types;
        foreach($results_by_school_id AS $school_id => $school_stats){
            foreach(array_keys($school_stats) AS $type){
                if(!in_array($type,$all_types)){
                    $all_types[] = $type;
                }
            }
        }

        foreach($school_data AS $division_title=>$regions){
            $html .= '<h2>'.$division_title.'</h2>';
            $html .= '<table class="table table-striped table-bordered season-stats">';
            foreach($regions AS $region_title=>$schools){
               
                // Sort by regional Win DESC if available
            usort($schools, function ($a, $b) use ($results_by_school_id) {
                $a_stats = $results_by_school_id[$a['school_id']]['regional']['Win'] ?? null;
                $b_stats = $results_by_school_id[$b['school_id']]['regional']['Win'] ?? null;

                // If both have regional win data, sort descending
                if ($a_stats !== null && $b_stats !== null) {
                    return $b_stats <=> $a_stats;
                }

                // If only one has data, that one comes first
                if ($a_stats !== null) return -1;
                if ($b_stats !== null) return 1;

                // Fallback to alpha by title
                return strcasecmp($a['title'], $b['title']);
            });
                
                $html .= '<thead><tr><th>'.$region_title.'</th>';
                $html .= '<th>Overall</th><th>Regional</th>';
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                foreach($schools AS $school){
                    $html .= '<tr><th class="jd-ui-button clickable" data-jd-click="getSchedule" data-id="'.$school['school_id'].'">'.$school['title'].'</th>';
                    $stats = $results_by_school_id[$school['school_id']]??[];

                    foreach ($display_types as $type) {
                        if (isset($stats[$type])) {
                            $w = $stats[$type]['Win'] ?? 0;
                            $l = $stats[$type]['Loss'] ?? 0;
                            $t = $stats[$type]['Tie'] ?? 0;
                            $html .= '<td>
                                <span class="win">'.$w.'</span> -
                                <span class="loss">'.$l.'</span> -
                                <span class="tie">'.$t.'</span>
                                </td>';
                        } else {
                            $html .= '<td>-</td>'; // Not available
                        }
                        
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
            }
            $html .= '</table>';
        }


        return $html;
    }

    public function getSeasonResultsHTML(?Division $Division = null, ?Region $Region = null):string{
        $html = '';
        $participating_school_data = $this->getSortedSeasonSchoolsData();
        $SeasonResults = $this->getSeasonResults();
        
        $region_id = !empty($Region)?$Region->getID():0;
        $division_id = !empty($Division)?$Division->getID():0;
        $results_by_school_id = $SeasonResults->getSeasonResults($division_id,$region_id);
        
        if(!empty($Division)){
            $division_title = $Division->getTitle();
            $school_data = [
                $division_title =>$participating_school_data[$division_title]
            ];
            if(!empty($Region)){
                $region_title = $Region->getTitle();
                $school_data = [
                    $division_title=>[
                        $region_title=>$participating_school_data[$division_title][$region_title]
                        ]
                    ];
            }
        }else{
            $school_data = $participating_school_data;
        }

        $all_types = ['overall'];
        foreach($results_by_school_id AS $school_id => $school_stats){
            foreach(array_keys($school_stats) AS $type){
                if(!in_array($type,$all_types)){
                    $all_types[] = $type;
                }
            }
        }

        foreach($school_data AS $division_title=>$regions){
            $html .= '<h2>'.$division_title.'</h2>';
            $html .= '<table class="table table-striped table-bordered season-stats">';
            foreach($regions AS $region_title=>$schools){
               
                
                $html .= '<thead><tr><th>'.$region_title.'</th>';
                foreach($all_types AS $type){
                    $html .= '<th>'.ucfirst($type).'</th>';
                }
                $html .= '</tr></thead>';
                $html .= '<tbody>';
                foreach($schools AS $school){
                    $html .= '<tr><th class="jd-ui-button clickable" data-jd-click="getSchedule" data-id="'.$school['school_id'].'">'.$school['title'].'</th>';
                    $stats = $results_by_school_id[$school['school_id']]??[];
                    foreach ($all_types as $type) {
                        if (isset($stats[$type])) {
                            $w = $stats[$type]['Win'] ?? 0;
                            $l = $stats[$type]['Loss'] ?? 0;
                            $t = $stats[$type]['Tie'] ?? 0;
                            $html .= '<td>
                                <span class="win">'.$w.'</span> -
                                <span class="loss">'.$l.'</span> -
                                <span class="tie">'.$t.'</span>
                                </td>';
                        } else {
                            $html .= '<td>-</td>'; // Not available
                        }
                        
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody>';
            }
            $html .= '</table>';
        }


        return $html;
    }

    public function getSortedSeasonSchoolsData():array{
  
        $ParticipatingSchools = $this->getSeasonSchools();
        $Regions = $this->getRegions();
        $Divisions = $this->getDivisions();
        $ByDivision = [];
        foreach($ParticipatingSchools AS $SeasonSchool){
            $region_id = $SeasonSchool->getRegionID();
            $region = isset($Regions[$region_id])?$Regions[$region_id]->getTitle():'None';
            $division_id = $SeasonSchool->getDivisionID();
            $division = isset($Divisions[$division_id])?$Divisions[$division_id]->getTitle():'None';
            $division_flag = (string) $SeasonSchool->getDivisionFlag();
            $division_flag2 = (string) $SeasonSchool->getDivisionFlag2();
            $school_id = $SeasonSchool->getSchoolID();
            if(empty($ByDivision[$division])){
                $ByDivision[$division] = [];
            }
            if(empty($ByDivision[$division][$region])){
                $ByDivision[$division][$region] = [];
            }
        
            $flag = '';
            if(!empty(trim($division_flag)) || !empty(trim($division_flag2))){
                $flag .= ' <em class="smaller">( ';
                $flag .= !empty($division_flag)?$division_flag.' ':'';
                $flag .= !empty($division_flag2)?$division_flag2.' ':'';
                $flag .= ')</em>';
            }
            $School = $SeasonSchool->getSchool();
            $title = $School?$School->getTitle():'';
            $ByDivision[$division][$region][] = [
                'school_id'=>$school_id,
                'title'=>$title,
                'flag'=>trim($division_flag),
                'flag2'=>trim($division_flag2),
            ];
        }
        $SortedByDivision = $this->sortNestedArray($ByDivision);
        return $SortedByDivision;
    }

    protected function sortNestedArray(array $input):array{

        if ($this->isListOfAssociativeArrays($input)) {
            usort($input, function($a, $b) {
                return strcmp($a['title'] ?? '', $b['title'] ?? '');
            });
            return $input;
        }

        $sorted = [];
        // First, check if 'None' exists and put it first
        if (isset($input['None'])) {
            $sorted['None'] = is_array($input['None']) ? $this->sortNestedArray($input['None']) : $input['None'];
        }
        // Sort remaining keys alphabetically, excluding 'None'
        $other_keys = array_diff_key($input, ['None' => true]);
        ksort($other_keys, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($other_keys as $key => $value) {
            $sorted[$key] = is_array($value) ? $this->sortNestedArray($value) : $value;
        }

        return $sorted;
    }

    protected function isListOfAssociativeArrays(array $array): bool {
        if (empty($array)) return false;
        return array_keys($array) === range(0, count($array) - 1)
            && is_array($array[0])
            && array_keys($array[0]) !== range(0, count($array[0]) - 1);
    }

    /**
     * Summary of getSeasonSchools
     * @return \ElevenFingersCore\GAPPS\Sports\Seasons\SeasonSchool[]
     */
    protected function getSeasonSchools():array{
        if(empty($this->SeasonSchools)){
            $SeasonSchools = $this->Season->getSeasonSchools();
            $school_ids = [];
            foreach($SeasonSchools AS $SeasonSchool){
                $school_ids[] = $SeasonSchool->getSchoolID();
            }
            
            $SchoolFactory = new SchoolFactory($this->database);
            $Schools = $SchoolFactory->getSchoolsByIds($school_ids);
            $SchoolsByID = [];
            foreach($Schools AS $School){
                $SchoolsByID[$School->getID()] = $School;
            }
            foreach($SeasonSchools AS $SeasonSchool){
                $School = $SchoolsByID[$SeasonSchool->getSchoolID()]??null;
                if(!empty($School)){
                    $SeasonSchool->setSchool($School);
                }
            }
            $this->SeasonSchools = $SeasonSchools;
        }
        return $this->SeasonSchools;
    }


    /**
     * Summary of getDivisions
     * @return Division[]
     */
    protected function getDivisions():array{
        if(empty($this->Divisions)){
            $this->Divisions = $this->Season->getDivisions();
        }
        return $this->Divisions;
    }

    /**
     * Summary of getRegions
     * @return \ElevenFingersCore\GAPPS\Sports\Seasons\Regions\Region[]
     */
    protected function getRegions():array{
        if(empty($this->Regions)){
            $this->Regions = $this->Season->getRegions();
        }
        return $this->Regions;
    }

    public function setSeasonResults(SeasonResults $SeasonResults){
        $this->SeasonResults = $SeasonResults;
    }

    public function getSeasonResults():SeasonResults{
        if(empty($this->SeasonResults)){
            $this->SeasonResults = new SeasonResults($this->database, $this->Season);
        }
        return $this->SeasonResults;
    }
}