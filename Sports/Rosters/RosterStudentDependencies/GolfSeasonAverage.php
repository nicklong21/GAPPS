<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies;

class GolfSeasonAverage extends RosterStudentDependency{

    private $season_avg;




    public function __construct(){}

    public function setSeasonAvg(float $avg){
        $this->season_avg = $avg;
    }

    public function getSeasonAvg():?float{
        return $this->season_avg;
    }



}