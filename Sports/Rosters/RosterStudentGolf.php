<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\GolfSeasonAverage;

class RosterStudentGolf extends RosterStudent{

    protected $season_avg = null;

    protected $dependency_list = [
        'SeasonAverage'=>GolfSeasonAverage::class,
    ];

    protected $SeasonAverage;

    public function setSeasonAvg(float $avg){
        $this->season_avg = $avg;
    }

    public function getSeasonAvg():?float{
        $SeasonAvg = $this->getSeasonAverageDependency();
        return $SeasonAvg->getSeasonAvg();
    }

    public function getDATA():array{
        $DATA = parent::getDATA();
        $DATA['season_avg'] = $this->getSeasonAvg();
        return $DATA;
    }

    public function getSeasonAverageDependency():GolfSeasonAverage{
        return $this->SeasonAverage;
    }

    public function setDependency(string $type, RosterStudentDependencies\RosterStudentDependency $Dependency) {
        switch($type){
            case 'SeasonAverage':
                $this->SeasonAverage = $Dependency;
                break;
            default:
            break;
        }
    }
}