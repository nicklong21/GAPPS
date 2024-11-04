<?php
namespace ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;

use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
class RosterTableLiterary extends RosterTableGroups{

    protected $instructions = '
        <p>Select the participating students from your school enrollment and indicate a which category they will be participating in.</p>
        <p>If a student is participating in more than one category, add them twice.</p>';

    protected $groups = array(
        'Unassigned'=>array(),
        'US Extemporaneous Speaking'=>array(),
        'International Extemporaneous Speaking'=>array(),
        'Dramatic Interpretation'=>array(),
        'Humorous Interpretation'=>array(),
        'Impromptu'=>array(),
        'Informative Speech'=>array(),
        'Duo Interpretation (2 names)'=>array(),
        'Argumentative Essay'=>array(),
        'Personal Essay'=>array(),
        'Persuasuve Speech'=>array(),
        'Rhetorical Analysis Essay'=>array(),
        'Spelling'=>array(),
        'Piano'=>array(),
        'Girls Solo'=>array(),
        'Boys Solo'=>array(),
        'Girls Trio (3 names)'=>array(),
        'Quartet (4 names)'=>array(),
    );

    protected $html_table_labels = array(
        'name'=>'Student Name',
        'age'=>'Age',
        'grade'=>'Grade',
        'gender'=>'Sex',
        'jersey_number'=>'Category',
        'attr1'=>'Alternate',
        'is_aes'=>'Is AES',
    );

}