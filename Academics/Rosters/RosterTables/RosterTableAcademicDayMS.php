<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableAcademicDayMS extends RosterTableGroups{

    protected $groups = array(
        'Unassigned'=>array(),
        'Geography Bee'=>array(),
        'History Bowl'=>array(),
        'Math Bowl'=>array(),
        'Personal Essay'=>array(),
        'Spelling Bee'=>array(),
    );


}