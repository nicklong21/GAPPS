<?php
namespace ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies;

use DateTimeImmutable;

class PitchCount extends RosterStudentDependency{


    protected $season_pitch_count;
    protected $recent_total = null;

    protected $rest_period = [
        40=>1,
        60=>2,
        85=>3,
        110=>4,
    ];

    function __construct(array $season_pitch_count){
        $this->season_pitch_count = $season_pitch_count;
    }

    public function getRecentTotals(DateTimeImmutable $Today):array{
        global $debug;
        $debug[] = 'getRecentTotals: '.$Today->format('Y-m-d');
        $pitchCounts = $this->getPitchCountRecords($Today);
        $totalPitches = 0;
        $latestRestDays = 0;

        foreach ($pitchCounts as $gameDate => $pitchCount) {
            $gameDateObj = new DateTimeImmutable($gameDate);
            $interval = $gameDateObj->diff($Today)->days;

            if ($interval > 3) {
                // Pitches older than 3 days do not contribute to required rest
                break;
            }
            $debug[] = $pitchCount;
            // Add to the total pitch count for the evaluation period
            $totalPitches += $pitchCount;

            // Determine the highest required rest days
            foreach ($this->rest_period as $limit => $restDays) {
                if ($pitchCount > $limit) {
                    $latestRestDays = max($latestRestDays, $restDays);
                }
            }
        }
        $latestRestDays = $latestRestDays > 0?$latestRestDays + 1:0;
        // Determine the next eligible date
        $nextEligibleDate = $Today->modify("+{$latestRestDays} days");
        
        $debug[] = 'Next Date: '.$nextEligibleDate->format('Y-m-d');

        return [
            'total_recent_pitches' => $totalPitches,
            'rest_days_required' => $latestRestDays,
            'next_eligible_date' => $nextEligibleDate
        ];
        

    }
    
    public function getSeasonTotal(DateTimeImmutable $Today):int{
        $total = 0;
        $pitch_count = $this->getPitchCountRecords($Today);
        if(!empty($pitch_count)){
            $total = array_sum($pitch_count);
        }
        return $total;
    }

    public function getLastPitchedDate(DateTimeImmutable $Today):?DateTimeImmutable{
        $Date = null;
        $pitch_count = $this->getPitchCountRecords($Today);
        if(!empty($pitch_count)){
            $last_date = array_key_first($pitch_count);
            $Date = new DateTimeImmutable($last_date);
        }
        return $Date;
    }

    public function getLastPitchedCount($Today):int{
        $count = 0;
        $pitch_count = $this->getPitchCountRecords($Today);
        if(!empty($pitch_count)){
            $count = reset($pitch_count);
        }
        return $count;

    }

    public function getPitchCountRecords(DateTimeImmutable $Today):array{
        return array_filter($this->season_pitch_count,fn($_,$date)=>$date <= $Today->format('Y-m-d H:i:s'), ARRAY_FILTER_USE_BOTH);
    }

}