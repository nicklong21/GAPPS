<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableAcademicDayHS extends RosterTableGroups{

    protected $groups = array(
        'Unassigned'=>array(),
        'History Bowl'=>array(),
        'Math Bowl'=>array(),
        'Spelling'=>array(),
        'Personal Essay'=>array(),
        'Argumentative Essay'=>array(),
        'Rhetorical Essay'=>array(),
    );


}