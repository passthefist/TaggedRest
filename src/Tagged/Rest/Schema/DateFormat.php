<?php

class DateFormat extends Schema{
    function __construct(array $config){
        $this->_schema = array(
            "type"=> "string",
            "description"=> "A format string for dates",
            "additionalProperties"=>false,
            "properties"=> array(
                "dateTime"=> array(
                    "type"=>"string",
                    "description"=> "A foramatted date string.",
                    "pattern"=> "/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/",
                ),
                "required"=>array("dateTime"),
            )
        );
    }
}
