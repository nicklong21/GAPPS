<?php
namespace ElevenFingersCore\GAPPS\Sports\Venues;
use ElevenFingersCore\Utilities\MessageTrait;

class VenueView{

    function __construct(){}

    public function getVenueAddressString():string{
        $title = '';
        $address = '';
        $html = '<h5>'.$title.'</h5>
        '.$address;
        return $html;
    }

    public function getInstructionsString():string{
        return '';
    }

    public function getGoogleMapString():string{
        return '';
    }
}