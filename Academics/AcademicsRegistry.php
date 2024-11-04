<?php
namespace ElevenFingersCore\GAPPS\Academics;
use ElevenFingersCore\GAPPS\Sports\SportRegistry;
use ElevenFingersCore\GAPPS\Schools\StudentFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Season;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonSchool;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonSchoolFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Divisions\DivisionFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Regions\RegionFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Game;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;
use ElevenFingersCore\GAPPS\Sports\Games\Teams\Team;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameView;
use ElevenFingersCore\GAPPS\Sports\Rosters\Roster;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableAcademicDayEL;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableAcademicDayMS;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableChess;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableChorus;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableDebate;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableFineArts;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableHistoryBowl;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableInstrumentalMusic;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableLiterary;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableMathBowl;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableRobotics;

class AcademicsRegistry extends SportRegistry{
    private static array $registry = [
        'Event' =>[
            'event'=>AcademicEvent::class,
            'season'=>Season::class,
            'roster'=>Roster::class,
            'roster_table'=>RosterTable::class,
            'roster_student'=>RosterStudent::class,
            'game'=>Game::class,
            'game_view'=>GameView::class,
            'team'=>Team::class,
            'score'=>GameScore::class,
            'school'=>SeasonSchool::class,
            'student_factory'=>StudentFactory::class,
            'roster_student_factory'=>RosterStudentFactory::class,
            'division_factory'=>DivisionFactory::class,
            'region_factory'=>RegionFactory::class,
            'school_factory'=>SeasonSchoolFactory::class,
        ],
        'chess'=>[
            'roster_table'=>RosterTableChess::class,
        ],
        'chess-elementary'=>[
            'roster_table'=>RosterTableChess::class,
        ],
        'chess-ms'=>[
            'roster_table'=>RosterTableChess::class,
        ],
        'chorus'=>[
            'roster_table'=>RosterTableChorus::class,
        ],
        'chorus-ms'=>[
            'roster_table'=>RosterTableChorus::class,
        ],
        'debate'=>[
            'roster_table'=>RosterTableDebate::class,
        ],
        'elementary-academics-day'=>[
            'roster_table'=>RosterTableAcademicDayEL::class,
        ],
        'history-bowl-hs'=>[
            'roster_table'=>RosterTableHistoryBowl::class,
        ],
        'honors-chorus'=>[
            'roster_table'=>RosterTableChorus::class,
        ],
        'instrumental-music'=>[
            'roster_table'=>RosterTableInstrumentalMusic::class,
        ],
        'instrumental-music-ms'=>[
            'roster_table'=>RosterTableInstrumentalMusic::class,
        ],
        'math-bowl-hs'=>[
            'roster_table'=>RosterTableMathBowl::class,
        ],
        'ms-academics-day'=>[
            'roster_table'=>RosterTableAcademicDayMS::class,
        ],
        'one-act-festival'=>[
            'roster_table'=>RosterTableFineArts::class,
        ],
        'one-act-play'=>[
            'roster_table'=>RosterTableFineArts::class,
        ],
        'quiz-bowl-hs'=>[
            'roster_table'=>RosterTableHistoryBowl::class,
        ],
        'quiz-bowl-ms'=>[
            'roster_table'=>RosterTableHistoryBowl::class,
        ],
        'robotics'=>[
            'roster_table'=>RosterTableRobotics::class,
        ],
        'robotics-ms'=>[
            'roster_table'=>RosterTableRobotics::class,
        ],
        'science-far-ms'=>[
            'roster_table'=>RosterTableFineArts::class,
        ],
        'spring-literary'=>[
            'roster_table'=>RosterTableLiterary::class,
        ],
        'visual-arts'=>[
            'roster_table'=>RosterTableFineArts::class,
        ],
        'visual-arts-elementary'=>[
            'roster_table'=>RosterTableFineArts::class,
        ],
        'visual-arts-ms'=>[
            'roster_table'=>RosterTableFineArts::class,
        ],
    ];
    
    public static function getDependencies(string $sport):array{
        $a_dependencies = self::$registry[$sport] ?? self::$registry['Event'];
        $dependencies = array_merge(self::$registry['Event'],$a_dependencies);
        return $dependencies;
    }
}

