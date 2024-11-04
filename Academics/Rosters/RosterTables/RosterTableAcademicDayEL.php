<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableAcademicDayEL extends RosterTableAcademicDayMS{

    protected $groups = array(
        'Unassigned'=>array(),
        'Math Bee'=>array(),
        'Quiz Bowl'=>array(),
        'Science Fair'=>array(),
        'Spelling Bee'=>array(),
       );    
}