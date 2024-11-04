<?php
namespace ElevenFingersCore\GAPPS\Academics\Seasons;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonFactory AS SportSeasonFactory;
class SeasonFactory extends SportSeasonFactory{


    public function getEventSeasons(int $event_id):array{
        return parent::getSportSeasons($event_id);
    }

}