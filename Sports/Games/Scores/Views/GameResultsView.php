<?php
namespace ElevenFingersCore\GAPPS\Sports\Games\Results\Views;
use ElevenFingersCore\Utilities\MessageTrait;

class GameResultsView{
    use MessageTrait;
    protected $Teams;
    protected $Scores;
    protected $display_style = ['default'];
    protected $game_status = '';

    protected $VenueView;


    public function __construct(){
    }

    public function setTeams(array $Teams){
        $this->Teams = $Teams;
    }

    public function setScores(array $Scores){
        $this->Scores = $Scores;
    } 

    public function getResultsString():string{
        $html = '';
        $style = !empty($this->display_style['results'])?$this->display_style['results']:'default';
        if(method_exists($this,'getResultsString_'.$style)){
            $method = 'getResultsString_'.$style;
        }else{
            $method = 'getResultsString_default';
        }
        $html = $this->$method();
        return $html;
    }

    protected function getResultsString_default():string{
        $hometeam_title = '';
        $awayteam_title = '';
        $hometeam_score = '';
        $awayteam_score = '';
        $html = '';
        $html .= '<table class="table game-scores">
        <thead><tr><th>'.$hometeam_title.'</th><th>'.$awayteam_title.'</th></tr></thead>';
        $html .= '<tbody><tr><td>'.$hometeam_score.'</td><td>'.$awayteam_score.'</td></tr></tbody>';
        $html .= '</table>';
       
        return $html;
    }
}