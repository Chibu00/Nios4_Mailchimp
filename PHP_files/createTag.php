<?php

$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERRORE");
}
 
$nomeTag= $data["tag"];
$id_mailchimp= $data["id_mailchimp"];
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}


if($id_mailchimp == "" || !isset($id_mailchimp)) {
    $urlMailchimp= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments";
    
    $ch= curl_init();

    $dataMailchimp= array(
        "name" => $nomeTag,
        "static_segment" => array(),
    );

    $dataMailchimp= json_encode($dataMailchimp);

    curl_setopt($ch, CURLOPT_URL, $urlMailchimp);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataMailchimp);
    curl_setopt($ch, CURLOPT_USERPWD, "user:".$apiKey);

    $responseMailchimp= curl_exec($ch);
    curl_close($ch);

    $responseMailchimp= json_decode($responseMailchimp);
    
    if($responseMailchimp->status == 401) {
        exit("NoKey");
    }

    if($responseMailchimp->type == "saved" || $responseMailchimp->type == "static" || $responseMailchimp->type == "fuzzy") {
        exit(strval($responseMailchimp->id));
    } else {
        exit("ko");
    }
    
} else {
    $urlAllMemberTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments/".$id_mailchimp."/members?count=1000";
    
    $chAllMemberTag= curl_init();
    
    curl_setopt($chAllMemberTag, CURLOPT_URL, $urlAllMemberTag);
    curl_setopt($chAllMemberTag, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chAllMemberTag, CURLOPT_USERPWD, "user:".$apiKey);
    
    $responseAllMemberTag= curl_exec($chAllMemberTag);
    $responseAllMemberTag= json_decode($responseAllMemberTag);
    curl_close($chAllMemberTag);
    
    if($responseAllMemberTag->status == 401) {
        exit("NoKey");
    }
    
    $listaMembri= array();
    
    foreach ($responseAllMemberTag->members as $key => $value) {
        array_push($listaMembri, $value->email_address);
    }
    
    $urlUpdateTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments/".$id_mailchimp;
    
    $dataUpdateTag= array(
        "name" => $nomeTag,
        "static_segment" => $listaMembri,
    );
    
    $dataUpdateTag= json_encode($dataUpdateTag);
    
    $chUpdateTag= curl_init();
    
    curl_setopt($chUpdateTag, CURLOPT_URL, $urlUpdateTag);
    curl_setopt($chUpdateTag, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chUpdateTag, CURLOPT_POST, true);
    curl_setopt($chUpdateTag, CURLOPT_POSTFIELDS, $dataUpdateTag);
    curl_setopt($chUpdateTag, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($chUpdateTag, CURLOPT_USERPWD, "user:".$apiKey);
    
    $responseUpdateTag= curl_exec($chUpdateTag);
    $responseUpdateTag= json_decode($responseUpdateTag);
    curl_close($chUpdateTag);
    
    if(array_key_exists("id", $responseUpdateTag)) {
        exit(strval($responseUpdateTag->id));
    } else {
        exit("ko");
    }
    
}
