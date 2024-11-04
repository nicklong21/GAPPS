<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;

use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentGolf;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreGolf;

class RosterStudentFactoryGolf extends RosterStudentFactory{

    protected $scores_table = 'game_scores';

    protected $GameScoreFactory;
    protected $init_season_avg = true;

    public function getRosterStudent(?int $id = 0, ?array $DATA = array()):RosterStudentGolf{
        $RosterStudent = parent::getRosterStudent($id, $DATA);
        if($this->init_season_avg){
            $this->setStudentSeasonAvg($RosterStudent);
        }
        return $RosterStudent;
    }

    /**
     * Summary of getRosterStudents
     * @param int $roster_id
     * @return RosterStudentGolf[]
     */
    public function getRosterStudents(int $roster_id):array{
        $this->init_season_avg = false;
        $RosterStudents = parent::getRosterStudents($roster_id);
        $this->setStudentGroupSeasonAvg($RosterStudents);
        $this->init_season_avg = true;
        return $RosterStudents;
    }

    /**
     * Summary of getRosterStudentsByIDs
     * @param array $ids
     * @return RosterStudentGolf[]
     */
    public function getRosterStudentsByIDs(array $ids):array{
        $this->init_season_avg = false;
        $RosterStudents = parent::getRosterStudentsByIDs($ids);
        $this->setStudentGroupSeasonAvg($RosterStudents);
        $this->init_season_avg = true;
        return $RosterStudents;
    }

    /**
     * Summary of getRosterStudentsByStudentIDs
     * @param int $roster_id
     * @param array $student_ids
     * @return RosterStudentGolf[]
     */
    public function getRosterStudentsByStudentIDs(int $roster_id, array $student_ids):array{
        $this->init_season_avg = false;
        $RosterStudents = parent::getRosterStudentsByStudentIDs( $roster_id, $student_ids);
        $this->setStudentGroupSeasonAvg($RosterStudents);
        $this->init_season_avg = true;
        return $RosterStudents;
    }

    protected function setStudentSeasonAvg(RosterStudentGolf $RosterStudent){
        $roster_id = $RosterStudent->getRosterID();
        $Scores = $this->getGameScoreFactory()->getRosterScoresForSeason($roster_id);
        $student_id = $RosterStudent->getID();
        $scores = [];
        /** @var GameScoreGolf $Score */
        foreach($Scores AS $Score){
            $individual_score = $Score->getIndividualScore($student_id);
            if(!is_null($individual_score)){
                $scores[] = $individual_score;
            }
        }
        if(!empty($scores)){
            $season_avg = array_sum($scores) / count($scores);
        }else{
            $season_avg = 0;
        }
        $RosterStudent->setSeasonAvg($season_avg);
    }

    /**
     * Summary of setStudentGroupSeasonAvg
     * @param RosterStudentGolf[] $RosterStudents
     * @return void
     */
    protected function setStudentGroupSeasonAvg(array $RosterStudents){
        if(!empty($RosterStudents)){
            $roster_id = $RosterStudents[0]->getRosterID();
            $Scores = $this->getGameScoreFactory()->getRosterScoresForSeason($roster_id);
            foreach($RosterStudents AS $RosterStudent){
                $student_id = $RosterStudent->getID();
                $scores = [];
                /** @var GameScoreGolf $Score */
                foreach($Scores AS $Score){
                    $individual_score = $Score->getIndividualScore($student_id);   
                    if(!empty($individual_score)){
                        $scores[] = $individual_score;
                    }
                }
                if(!empty($scores)){
                    $season_avg = number_format(array_sum($scores) / count($scores),2);
                }else{
                    $season_avg = 0;
                }
                $RosterStudent->setSeasonAvg($season_avg);
            }
        }
    }

    protected function getGameScoreFactory():GameScoreFactory{
        if(empty($this->GameScoreFactory)){
            $dependencies = $this->getDependencies();
            $this->GameScoreFactory = new GameScoreFactory($this->database, $dependencies);
        }
        return $this->GameScoreFactory;
    }
}