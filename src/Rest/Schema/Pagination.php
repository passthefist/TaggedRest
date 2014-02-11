<?php
class Pagination extends Schema {
    function __construct($config){
        parent::__construct($config);
        $this->_schema = array(
            "type"=> "object",
            "description"=> "Request a slice of the final data set.",
            "properties"=> array(
                "limit"=> array(
                    "type"=> "number",
                    "description"=> "The number of items to fetch.",
                    "default"=> 20,
                    "minimum"=> 1,
                    "maximum"=> 100
                ),
                "offset"=> array(
                    "type"=> "number",
                    "description"=> "Start with this item. 0-based.",
                    "default"=> 0,
                    "minimum"=> 0
                ),
                "page"=> array(
                    "type"=> "object",
                    "description"=> "Get items based on a page and pagesize, more natural for browsing.",
                    "properties"=> array(
                        "number"=> array(
                            "description"=> "The page",
                            "default"=> 1,
                            "minimum"=> 1,
                        ),
                        "size"=> array(
                            "description"=> "The number of items to fetch per page.",
                            "default"=> 25,
                            "minimum"=> 1,
                        ),
                    ),
                    "required"=>array("number","size"),
                ),
                "required"=>array("limit","offset"),
            )
        );
    }
}
