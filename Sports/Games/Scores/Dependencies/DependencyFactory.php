<?php

namespace ElevenFingersCore\GAPPS\Sports\Games\Scores\Dependencies;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;

abstract class DependencyFactory{

    private $database;

    function __construct(DatabaseConnectorPDO $DB){
        $this->database = $DB;
    }

    abstract public function initGameScoreDependency(GameScore $GameScore, string $type);


    abstract public function initGameScoresDependency(array $GameScores, string $type);

    abstract public function saveGameScoreDependencies(GameScore $GameScore, array $DATA):bool;
}