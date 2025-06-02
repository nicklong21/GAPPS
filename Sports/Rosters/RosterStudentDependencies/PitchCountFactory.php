<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\Dependencies\PitchCountFactory AS GameScorePitchCountFactory;

class PitchCountFactory extends DependencyFactory{

    private $PitchCountFactory;

    protected function getGameScorePitchCountFactory():GameScorePitchCountFactory{
        if(empty($this->PitchCountFactory)){
            $this->PitchCountFactory = new GameScorePitchCountFactory($this->database);
        }
        return $this->PitchCountFactory;
    }


    public function initRosterStudentDependency(RosterStudent $RosterStudent, string $type){
        $RosterStudents = [$RosterStudent];
        return $this->initRosterStudentGroupDependency($RosterStudents, $type);
    }

    public function initRosterStudentGroupDependency(array $RosterStudents, string $type){
        if(!empty($RosterStudents)){
            
            $PitchCountFactory = $this->getGameScorePitchCountFactory();
            $student_ids = [];
            foreach($RosterStudents AS $Student){
                $student_ids[] = $Student->getID();
            }
            $season_pitches = $PitchCountFactory->getSeasonPitches(null,$student_ids);
            /**
             * @var RosterStudent $Student
             */
            foreach($RosterStudents AS $Student){
                $pitch_count = isset($season_pitches[$Student->getID()])?$season_pitches[$Student->getID()]:[];
                $PitchCountDependency = new $this->DependencyClass($pitch_count);
                $Student->setDependency($type, $PitchCountDependency);
            }
        }
        
    }

    public function saveRosterStudentDependencies(RosterStudent $RosterStudent, array $DATA):bool{
        return true;
    }

}