<?php
namespace ElevenFingersCore\GAPPS\Sports;

use ElevenFingersCore\GAPPS\Schools\StudentFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\Dependencies\PitchCountFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreMultiTeam;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\PitchCountMS;
use ElevenFingersCore\GAPPS\Sports\Seasons\Season;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonSchool;
use ElevenFingersCore\GAPPS\Sports\Seasons\SeasonSchoolFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Divisions\DivisionFactory;
use ElevenFingersCore\GAPPS\Sports\Seasons\Regions\RegionFactory;
use ElevenFingersCore\GAPPS\Sports\Games\Game;
use ElevenFingersCore\GAPPS\Sports\Games\GameTennis;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScore;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreGolf;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreTennis;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\GameScoreVolleyball;
use ElevenFingersCore\GAPPS\Sports\Games\Teams\Team;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameBaseBallView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameBassFishingView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameCrossCountryView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameGolfView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameMultiTeamView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameTennisView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameVolleyballView;
use ElevenFingersCore\GAPPS\Sports\Games\Views\GameWrestlingView;
use ElevenFingersCore\GAPPS\Sports\Rosters\Roster;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudent;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentFactoryGolf;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentGolf;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTable;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableAcademicDayEL;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableAcademicDayHS;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableAcademicDayMS;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTableBassFishing;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableChess;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableChorus;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableDebate;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableFineArts;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTableGolf;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableHistoryBowl;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTableHunter;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableInstrumentalMusic;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableLiterary;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableMathBowl;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTableNoJersey;
use ElevenFingersCore\GAPPS\Academics\Rosters\RosterTables\RosterTableRobotics;
use ElevenFingersCore\GAPPS\Sports\Games\Scores\Dependencies\PitchCount;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\GolfSeasonAverage;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\GolfSeasonAverageFactory;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentPitch;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTablePitch;
use ElevenFingersCore\GAPPS\Sports\Rosters\RosterTables\RosterTableWrestling;

class SportRegistry{
    private static array $registry = [
        'Sport' =>[
            'sport'=>Sport::class,
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
            'roster_student_dependencies'=>[],
            'game_score_dependencies'=>[],
        ],
        'archery'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameMultiTeamView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'archery-ms'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameMultiTeamView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'baseball'=>[
            'game_view'=>GameBaseBallView::class,
        ],
        'baseball-ms'=>[
            'sport'=>SportPitchCount::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'roster_student'=>RosterStudentPitch::class,
            'roster_table'=>RosterTablePitch::class,
            'game_view'=>GameBaseBallView::class,
            'game_score_dependencies'=>[
                'PitchCount'=>[
                    'class'=>PitchCount::class,
                    'factory'=>PitchCountFactory::class,
                ],
            ],
            'roster_student_dependencies'=>[
                'PitchCount'=>[
                    'class'=>PitchCountMS::class,
                    'factory'=>\ElevenFingersCore\GAPPS\Sports\Rosters\RosterStudentDependencies\PitchCountFactory::class,
                ]
            ],
        ],
        'basketball-boys'=>[
        ],
        'basketball-girls'=>[
        ],
        'basketball-ms-boys'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
        ],
        'basketball-ms-girls'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
        ],
        'bass-fishing'=>[
            'roster_table'=>RosterTableBassFishing::class,
            'game_view'=>GameBassFishingView::class,
        ],
        'bowling'=>[
        ],
        'bowling-ms'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
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
        'clay-target'=>[
            'roster_table'=>RosterTableHunter::class,
            'game_view'=>GameMultiTeamView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'clay-target-ms'=>[
            'roster_table'=>RosterTableHunter::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameMultiTeamView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'competition-cheer'=>[
            'game_view'=>GameMultiTeamView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'competition-cheer-ms'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameMultiTeamView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'cross-country'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'cross-country-girls'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'cross-country-elementary'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'cross-country-elementary-girls'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'cross-country-ms'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'cross-country-ms-girls'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'debate'=>[
            'roster_table'=>RosterTableDebate::class,
        ],
        'elementary-academics-day'=>[
            'roster_table'=>RosterTableAcademicDayEL::class,
        ],
        'flag-football-girls'=>[
        ],
        'football-11-man'=>[
        ],
        'football-9-man'=>[
        ],
        'football-ms-9-man'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
        ],
        'golf'=>[
            'game_view'=>GameGolfView::class,
            'roster_student'=>RosterStudentGolf::class,
            'roster_table'=>RosterTableGolf::class,
            'score'=>GameScoreGolf::class,
            'roster_student_dependencies'=>[
                'SeasonAverage'=>[
                    'class'=>GolfSeasonAverage::class,
                    'factory'=>GolfSeasonAverageFactory::class],
                ],
        ],
        'golf-ms'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'roster_student'=>RosterStudentGolf::class,
            'roster_table'=>RosterTableGolf::class,
            'game_view'=>GameGolfView::class,
            'score'=>GameScoreGolf::class,
            'roster_student_dependencies'=>[
                'SeasonAverage'=>[
                    'class'=>GolfSeasonAverage::class,
                    'factory'=>GolfSeasonAverageFactory::class],
                ],
        ],
        'history-bowl-hs'=>[
            'roster_table'=>RosterTableHistoryBowl::class,
        ],
        'honors-chorus'=>[
            'roster_table'=>RosterTableChorus::class,
        ],
        'hs-academic-day'=>[
            'roster_table'=>RosterTableAcademicDayHS::class,
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
        'soccer-boys'=>[
        ],
        'soccer-girls'=>[
        ],
        'soccer-ms-boys'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
        ],
        'soccer-ms-girls'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
        ],
        'softball'=>[
            'game_view'=>GameBaseBallView::class,
        ],
        'spring-literary'=>[
            'roster_table'=>RosterTableLiterary::class,
        ],
        'swimming'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'swimming-girls'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'swimming-ms'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'swimming-ms-girls'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'tennis-boys'=>[
            'game'=>GameTennis::class,
            'game_view'=>GameTennisView::class,
            'score'=>GameScoreTennis::class,
        ],
        'tennis-girls'=>[
            'game'=>GameTennis::class,
            'game_view'=>GameTennisView::class,
            'score'=>GameScoreTennis::class,
        ],
        'tennis-ms-boys'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game'=>GameTennis::class,
            'game_view'=>GameTennisView::class,
            'score'=>GameScoreTennis::class,
        ],
        'tennis-ms-girls'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game'=>GameTennis::class,
            'game_view'=>GameTennisView::class,
            'score'=>GameScoreTennis::class,
        ],
        'track'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
        ],
        'track-ms'=>[
            'roster_table'=>RosterTableNoJersey::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameCrossCountryView::class,
            'score'=>GameScoreMultiTeam::class,
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
        'volleyball'=>[
            'game_view'=>GameVolleyballView::class,
            'score'=>GameScoreVolleyball::class,
        ],
        'volleyball-ms'=>[
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameVolleyballView::class,
            'score'=>GameScoreVolleyball::class,
        ],
        'wrestling'=>[
            'roster_table'=>RosterTableWrestling::class,
            'game_view'=>GameWrestlingView::class,
        ],
        'wrestling-ms'=>[
            'roster_table'=>RosterTableWrestling::class,
            'roster_varsity_values'=>array('A-Team','B-Team'),
            'roster_varsity_label'=>'A/B',
            'game_view'=>GameWrestlingView::class,
        ],

    ];
    
    public static function getDependencies(string $sport):array{
        $a_dependencies = self::$registry[$sport] ?? self::$registry['Sport'];
        $dependencies = array_merge(self::$registry['Sport'],$a_dependencies);
        return $dependencies;
    }
}

