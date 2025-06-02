<?php
namespace ElevenFingersCore\GAPPS\ItemList;

use ElevenFingersCore\ItemList\Item;

class Sponsor extends Item{
        protected $type = 'sponsors';
        protected $related_tags = [
            'logo'=>'image',
        ];
}