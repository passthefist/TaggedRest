<?php
class Pagination extends Schema {
    function __construct($config){
        parent::__construct($config);
        $max = isset($config['size'])? $config['size'] : 100;

        $this->_schema = array(
            "type"=> "object",
            "description"=> "Request a slice of the final data set.",
            "properties"=> array(
                "limit"=> array(
                    "type"=> "number",
                    "description"=> "The number of items to fetch.",
                    "default"=> 20,
                    "minimum"=> 1,
                    "maximum"=> $max
                ),
                "offset"=> array(
                    "type"=> "number",
                    "description"=> "Start with this item. 0-based.",
                    "default"=> 0,
                    "minimum"=> 0
                ),
                "pageNumber"=> array(
                    "type"=>"number",
                    "description"=> "The page",
                    "default"=> 1,
                    "minimum"=> 1,
                ),
                "pageSize"=> array(
                    "description"=> "The number of items to fetch per page.",
                    "default"=> $max,
                    "minimum"=> 1,
                ),
            )
        );
    }
}
