<?php
namespace Tagged\Rest\Schema;
use Tagged\Rest\Schema;

class DateTime extends Schema{
    function __construct(array $config){
        $this->_schema = array(
            "type"=> "object",
            "description"=> "A date/time value",
            "additionalProperties"=>false,
            "properties"=> array(
                "dateTime"=> array(
                    "type"=>"string",
                    "description"=> "A foramatted date string.",
                    "pattern"=> "(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:?(\d{2})",
                ),
                "required"=>array("dateTime"),
            )
        );
    }

    public static function getTimestamp($dateString) {
        return strtotime($dateString);
    }
}
