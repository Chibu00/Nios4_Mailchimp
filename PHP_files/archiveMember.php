<?php

$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERRORE");
}

$email= $data["email"];
$hashEmail= hash("md5", $email);
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

$urlMailchimp= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members/".$hashEmail;

function deleteAllTag(string $emailHash, string $apikey, string $list_id, string $dc) {
    $urlAllTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$list_id."/segments?count=1000";

    $chAllTag= curl_init();

    curl_setopt($chAllTag, CURLOPT_URL, $urlAllTag);
    curl_setopt($chAllTag, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chAllTag, CURLOPT_USERPWD, "user:".$apikey);

    $responseAllTag= curl_exec($chAllTag);
    $responseAllTag= json_decode($responseAllTag);
    curl_close($chAllTag);
    
    if($responseAllTag->status == 401) {
        exit("NoKey");
    }

    $tagList= $responseAllTag->segments;

    $listaAllTag= array();

    foreach ($tagList as $key => $value) {
        array_push($listaAllTag, $value->name);
    }

    $urlDeleteAllTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$list_id."/members/".$emailHash."/tags";

    $tagName= array();
    $datiArray= array();
    foreach ($listaAllTag as $key => $value) {
        $datiArray["name"] = $value;
        $datiArray["status"] = "inactive";
        array_push($tagName, $datiArray);
    }

    $data= array(
        "tags" => $tagName,
    );

    $data= json_encode($data);

    $ch= curl_init();

    curl_setopt($ch, CURLOPT_URL, $urlDeleteAllTag);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_USERPWD, "user:".$apikey);

    $response= curl_exec($ch);
    curl_close($ch);

}

deleteAllTag($hashEmail, $apiKey, $mailchimp_list_id, $dc);

$ch= curl_init();

curl_setopt($ch, CURLOPT_URL, $urlMailchimp);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_USERPWD, "user:".$apiKey);

$response= curl_exec($ch);
curl_close($ch);

if(empty($response)) {
    exit("ok");
} else {
    exit("ko");
}
