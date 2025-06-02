<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentGolf;
use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreGolf;
class GolfSeasonAverageFactory extends DependencyFactory{

    private $GameScoreFactory;



    public function initRosterStudentDependency(RosterStudent $RosterStudent, string $type){
        $RosterStudents = [$RosterStudent];
        return $this->initRosterStudentGroupDependency($RosterStudents, $type);
    }

    public function initRosterStudentGroupDependency(array $RosterStudents, string $type){
        $this->setStudentsSeasonAvg($RosterStudents, $type);
    }

    public function saveRosterStudentDependencies(RosterStudent $RosterStudent, array $DATA):bool{
        return true;
    }

    /**
     * Summary of getGameScoresForRoster
     * @param int $roster_id
     * @return GameScoreGolf[]
     */
    protected function getGameScoresForRoster(int $roster_id):array{
        $Scores = $this->getGameScoreFactory()->getRosterScoresForSeason($roster_id);
        return $Scores;
    }

    /**
     * Summary of setStudentsSeasonAvg
     * @param array RosterStudentGolf[] $RosterStudents
     * @return void
     */
    protected function setStudentsSeasonAvg(array $RosterStudents, string $type){
        $roster_ids = [];
        foreach($RosterStudents AS $Student){
            $roster_id = $Student->getRosterID();
            if(!in_array($roster_id,$roster_ids)){
                $roster_ids[] = $roster_id;
            }
        }
        $RosterGameScores = [];
        foreach($roster_ids AS $roster_id){
            $RosterGameScores[$roster_id] = $this->getGameScoresForRoster($roster_id);
        }
        foreach($RosterStudents AS $RosterStudent){
            $roster_id = $RosterStudent->getRosterID();
            $roster_student_id = $RosterStudent->getID();
            $GameScores = $RosterGameScores[$roster_id];
            $indv_scores = [];
            foreach($GameScores AS $Score){
                $score = $Score->getIndividualScore($roster_student_id);
                if(!is_null($score)){
                    $indv_scores[] = $score;
                }
            }
            if(!empty($indv_scores)){
                $season_avg = array_sum($indv_scores) / count($indv_scores);
            }else{
                $season_avg = 0;
            }
            $SeasonAvg = $this->getDependency();
            $SeasonAvg->setSeasonAvg($season_avg);
            $RosterStudent->setDependency($type, $SeasonAvg);
        }
    }

    protected function getDependency():GolfSeasonAverage{
        return new $this->DependencyClass();
    }


    

    protected function getGameScoreFactory():GameScoreFactory{
        if(empty($this->GameScoreFactory)){
            $dependencies = $this->getDependencies();
            $this->GameScoreFactory = new GameScoreFactory($this->database, $dependencies);
        }
        return $this->GameScoreFactory;
    }

    public function getDependencies():array{
        return $this->dependencies;
    }

    public function getDependencyValue(string $key){
        $value = isset($this->dependencies[$key])?$this->dependencies[$key]:null;
        return $value;
    }

}

