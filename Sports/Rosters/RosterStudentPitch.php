<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters;

use DateTimeImmutable;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\PitchCount;

class RosterStudentPitch extends RosterStudent{

    private $PitchCount;

    private $Today;

    public function getDATA():array{
        $DATA = parent::getDATA();
        $pitch_count = $this->getPitchCountDATA();
        $pitch_count['last_pitched'] = !empty($pitch_count['last_pitched'])?$pitch_count['last_pitched']->format('m/d/Y'):'';
        $pitch_count['next_eligible_date'] = !empty($pitch_count['next_eligible_date'])?$pitch_count['next_eligible_date']->format('m/d/Y'):'';
        if(!empty($pitch_count['season_total'])){
            $DATA['pitch_count'] = '
            Season Total: '.$pitch_count['season_total'].'<br/>
            Recent Total: '.$pitch_count['recent_total'].'<br/>
            Last Pitched: '.$pitch_count['last_pitched']. ' ('.$pitch_count['last_pitched_count'].')<br/>
            Next Eligible: '.$pitch_count['next_eligible_date'].'<br/>';
        }else{
            $DATA['pitch_count'] = 'NA';
        }
        return $DATA;
    }

    public function getPitchCountDATA():array{
        $PitchCount = $this->getPitchCountDependency();
        $Today = $this->getDate();
        $LastPitchedDate = $PitchCount->getLastPitchedDate($Today);
        $last_pitched_count = $PitchCount->getLastPitchedCount($Today);
        if(!empty($LastPitchedDate)){
            $recent_pitches = $PitchCount->getRecentTotals($LastPitchedDate);
            $recent_total = $recent_pitches['total_recent_pitches'];
            $nextDate = $recent_pitches['next_eligible_date'];
        }else{
            $recent_total = 0;
            $nextDate = '';
        }
        

        $pitch_count_data = [
            'last_pitched'=>$LastPitchedDate,
            'last_pitched_count'=>$last_pitched_count,
            'next_eligible_date'=>$nextDate,
            'recent_total'=>$recent_total,
            'season_total'=>$PitchCount->getSeasonTotal($Today),
        ];
        return $pitch_count_data;
    }

    public function getPitchCountDependency():PitchCount{
        return $this->PitchCount;
    }

    public function setDate(DateTimeImmutable $Date){
        $this->Today = $Date;
    }

    protected function getDate():DateTimeImmutable{
        if(empty($this->Today)){
            $this->Today = new DateTimeImmutable('now');
        }
        return $this->Today;
    }


    public function setDependency(string $type, RosterStudentDependencies\RosterStudentDependency $Dependency) {
        switch($type){
            case 'PitchCount':
                $this->PitchCount = $Dependency;
                break;
            default:
            break;
        }
    }
}