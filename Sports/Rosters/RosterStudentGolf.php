<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;


class RosterStudentGolf extends RosterStudent{

    protected $season_avg = null;

    public function setSeasonAvg(float $avg){
        $this->season_avg = $avg;
    }

    public function getSeasonAvg():?float{
        return $this->season_avg;
    }

    public function getDATA():array{
        $DATA = parent::getDATA();
        $DATA['season_avg'] = $this->getSeasonAvg();
        return $DATA;
    }
}