<?php
use \Tagged\Rest;

class tag_tool_api_v1_profile extends RestApi {
    public function __construct() {
        $this->registerInputSchema( "find",
            array(
                "title" => "Query for user's uploaded photos",
                "type" => "object",
                "properties" => array(
                    "filter"=> array(
                        "type"=>"object",
                        "properties"=>array(
                            "dateRange" => Schema\DateRange::build(),
                            "sent"=> array(
                                "type"=>"boolean",
                                "default"=>false
                            ),
                            "received"=> array(
                                "type"=>"boolean",
                                "default"=>false
                            )
                        ),
                        "required"=>array('startDate','endDate','sent','received')
                    ),
                    "pagination" => Schema\Pagination::build(),
                ),
            )
        );
    }

    public function find($params) {
        $uid = $params['userid'];

        $response->json(array(
            'profile' => $this->getOutput($uid, $params['output'])
        ));
    }

    public function getOutput($uid, stdClass $outputSchema) {
        $user = $_TAG->user[$uid];

        $addrObj = $user->getAddressObj();
        $auth = $_TAG->auth[$user->getId()];
        $emails = array_values($auth->emails);
        $props = new stdClass();
        $dormancy = $_TAG->accountDormancy[$user->getId()]->code;

        if($outputSchema->dateCanceled){
            // if client is requesting date canceled, then we have to get it from DAO. If any other cases like this come
            // up, handle them in the condition above
            $authInfo = $_TAG->dao->login[$uid]->getUserAuth();
        }

        foreach($outputSchema as $key => $config){
            if(is_bool($config) && !$config){
                continue;
            }

            switch($key){
                case 'age':
                    $value = $user->getAge();
                    break;
                case 'brand':
                    $value = $userBrand = strtoupper(tag_brand::get_brand($uid));
                    break;

                case 'dateCanceled':
                    $value = null;
                    if($authInfo['date_cancelled']){
                        $value = date('m/d/y h:i a', strtotime($authInfo['date_cancelled']));
                    }

                    break;

                case "dateRegistered":
                    $value = date('m/d/y h:i a', $user->getDateRegistered());
                    break;

                case "displayName":
                    $value = $user->getDisplayName(true);
                    break;

                case "firstName":
                    $value = $user->getFirstName();
                    break;

                case "fullName":
                    $value = $user->getName();
                    break;

                case 'isVip':
                    $value = $_TAG->vipinfo[$uid]->isVIP();
                    break;

                case "lastName":
                    $value = $user->getLastName();
                    break;

                case "userId":
                    $value = $uid;
                    break;

                case "gender":
                    $value = $user->getGender();
                    break;

                case "birthdate":
                    $date = new DateTime();
                    $date->setTimestamp($user->getBirthdate());
                    $value = $date->format($config);
                    break;

                case "email":
                    $value = $auth->primaryEmail;
                    break;

                case "location":
                    $locOneLine = $addrObj->getOneLineLocationSummary(false);
                    $city = $addrObj->getCity();
                    $state = $addrObj->getState();
                    $region = $addrObj->getRegion();
                    $country = $addrObj->getCountry();

                    $value = (object)array(
                        "summary" => $locOneLine,
                        "city" => $city,
                        "state" => $state,
                        "region" => $region,
                        "country" => $country,
                    );
                    break;

                case "dormancy":
                    if(isset($dormancy) && isset($dormancy['value'])){
                        $date = new DateTime();
                        $date->setTimestamp(strtotime($dormancy['expiration_date']));
                        $value = (object)array(
                            "isDormant"=>true,
                            "code"=>$dormancy['value'],
                            "date"=>$date->format($config)
                        );
                    }
                    else{
                        $value = array(
                            "isDormant"=>false
                        );
                    }
                    break;

                case "cancellationReason":
                    $value = $user->getCanceledReason();
                    break;
                case "sla":
                    $value = tag_user_gold::getSlaLabel($_TAG->gold[$user->getId()]->getSla());
                    break;
                case "status":
                    $statusStr = "";
                    if($user->getUserCancelReason()){
                        $statusStr = "Cancelled";
                    }
                    else if(count($emails)==1 && !$emails[0]['last_verified']){
                        $statusStr = "Pending";
                    }
                    else if(isset($dormancy) && isset($dormancy['value'])){
                        $statusStr = "Dormant ( ".$dormancy['value']." )";
                    }
                    else{
                        $statusStr = "Active";
                    }
                    $value = $statusStr;
                    break;
            }

            $props->$key = $value;
        }
        return $props;
    }
}

