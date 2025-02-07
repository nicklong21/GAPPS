<?php
namespace ElevenFingersCore\GAPPS\Schools;


class SchoolStaff extends Coach{


    public function getStatus():string{
        return $this->DATA['status'];
    }
}