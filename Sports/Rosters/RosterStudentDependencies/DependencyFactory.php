<?php

namespace ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies;

use ElevenFingersCore\Database\DatabaseConnectorPDO;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;

abstract class DependencyFactory{

    protected $database;
    protected $dependencies;
    protected $DependencyClass;

    function __construct(DatabaseConnectorPDO $DB, array $dependencies, string $dependency_class){
        $this->database = $DB;
        $this->dependencies = $dependencies;
        $this->DependencyClass = $dependency_class;
    }


    abstract public function initRosterStudentDependency(RosterStudent $RosterStudent, string $type);


    abstract public function initRosterStudentGroupDependency(array $RosterStudents, string $type);

    abstract public function saveRosterStudentDependencies(RosterStudent $RosterStudent, array $DATA):bool;
}