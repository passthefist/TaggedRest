<?php

class Sorted extends Schema {
    function __construct(array $config){
        if(isset($config['default'])){
            $default = $config['default'];
        }
        else{
            $default = $config['types'][0];
        }
        $this->_schema = array(
            "type"=> "object",
            "description"=> "Specify the sort type and direction.",
            "additionalProperties"=>false,
            "properties"=> array(
                "fields"=> array(
                    "enum"=>$config['types'],
                    "description"=> "The field(s) to sort on.",
                    "default"=> $default,
                    "minimum"=> 1,
                    "maximum"=> 100
                ),
                "order"=> array(
                    "enum"=>array(
                        "asc",
                        "desc"
                    ),
                    "description"=>"Choose a direction to sort",
                    "default"=>"asc"
                )
            )
        );
    }
}
