<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableDebate extends RosterTableGroups{

    protected $groups = array(
        'Unassigned'=>array(),
        'Persuasive Speech'=>array(),
        'Informative Speech'=>array(),
        'Lincoln Douglas Debate'=>array(),
    );

    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Sex',
        'jersey_number'=>'Category',
        'is_aes'=>'Is AES',
    );

}