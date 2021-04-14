<?php

$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERRORE");
}

//Nios4
$email= $data["email"];
$righeDaAggiungere= $data["numRowsTagList"];
$id_mailchimp= $data["id_mailchimp"];
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

$listaTag= array();

if($righeDaAggiungere != 0) {
    for($i = 1; $i <= $righeDaAggiungere; $i++) {
        array_push($listaTag, $data["tag$i"]);
    }
}

if($id_mailchimp == "" || !isset($id_mailchimp)) {
    
    $urlMailchimp= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members";

    $dataInMailchimp= array(
        "email_address" => $email,
        "status" => "subscribed",
        "tags" => $listaTag,
    );

    $dataInMailchimp= json_encode($dataInMailchimp);

    $ch1= curl_init();

    curl_setopt($ch1, CURLOPT_URL, $urlMailchimp);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_POST, true);
    curl_setopt($ch1, CURLOPT_POSTFIELDS, $dataInMailchimp);
    curl_setopt($ch1, CURLOPT_USERPWD, "user:".$apiKey);

    $responseMailchimp= curl_exec($ch1);
    $responseMailchimp= json_decode($responseMailchimp);
    curl_close($ch1);
    
    if(isset($responseMailchimp->status) && $responseMailchimp->status == 401) {
        exit("NoKey");
    }

    if($responseMailchimp->status == 400) {
        exit("ko");
    } else {
        exit($responseMailchimp->id);
    }
} else {
   
    $urlMailchimpUtente= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members?count=1000";
    
    $chUtente= curl_init();
    
    curl_setopt($chUtente, CURLOPT_URL, $urlMailchimpUtente);
    curl_setopt($chUtente, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chUtente, CURLOPT_USERPWD, "user:".$apiKey);
    
    $responseUtente= curl_exec($chUtente);
    $responseUtente= json_decode($responseUtente);
    curl_close($chUtente);
    
    if(isset($responseUtente->status) && $responseUtente->status == 401) {
        exit("NoKey");
    }
    
    $listaUtenti= $responseUtente->members;  
    
    foreach ($listaUtenti as $key => $value) {
        if($value->id == $id_mailchimp) {
            $emailDaModificare= $value->email_address;
            break;
        }
    }
    
    $hashEmailDaModificare= hash("md5", $emailDaModificare);
    
    
    function deleteAllTag(string $emailHash, string $list_id, string $apikey, string $dc) {
        $urlAllTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$list_id."/segments?count=1000";

        $chAllTag= curl_init();

        curl_setopt($chAllTag, CURLOPT_URL, $urlAllTag);
        curl_setopt($chAllTag, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chAllTag, CURLOPT_USERPWD, "user:".$apikey);

        $responseAllTag= curl_exec($chAllTag);
        $responseAllTag= json_decode($responseAllTag);
        curl_close($chAllTag);

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

    function addTags(array $tagDaAggiungere, string $emailHash, string $list_id, string $apikey, string $dc) {
        $urlAggiungiTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$list_id."/members/".$emailHash."/tags";
        
        $tagName= array();
        $datiInputApi= array();
        foreach ($tagDaAggiungere as $key => $value) {
            $datiInputApi["name"]= $value;
            $datiInputApi["status"]= "active";
            array_push($tagName, $datiInputApi);
        }
        
        
        $dataAggiungiTag= array(
            "tags" => $tagName
        );
        
        $dataAggiungiTag= json_encode($dataAggiungiTag);
        
        $chAggiungiTag= curl_init();
        
        curl_setopt($chAggiungiTag, CURLOPT_URL, $urlAggiungiTag);
        curl_setopt($chAggiungiTag, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chAggiungiTag, CURLOPT_POST, true);
        curl_setopt($chAggiungiTag, CURLOPT_POSTFIELDS, $dataAggiungiTag);
        curl_setopt($chAggiungiTag, CURLOPT_USERPWD, "user:".$apikey);
        
        $response= curl_exec($chAggiungiTag);
        curl_close($chAggiungiTag);
        
    }

    if(empty($listaTag)) {
        deleteAllTag($hashEmailDaModificare, $mailchimp_list_id, $apiKey, $dc);
        
    } else {
        deleteAllTag($hashEmailDaModificare, $mailchimp_list_id, $apiKey, $dc);
        addTags($listaTag, $hashEmailDaModificare, $mailchimp_list_id, $apiKey, $dc);
    }
    
    $urlMailchimpUpdate= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members/".$hashEmailDaModificare;
    
    $dataMailchimpUpdate= array(
        "email_address" => $email
    );
    
    $dataMailchimpUpdate= json_encode($dataMailchimpUpdate);
    
    $chUpdate= curl_init();
    
    curl_setopt($chUpdate, CURLOPT_URL, $urlMailchimpUpdate);
    curl_setopt($chUpdate, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chUpdate, CURLOPT_POST, true);
    curl_setopt($chUpdate, CURLOPT_POSTFIELDS, $dataMailchimpUpdate);
    curl_setopt($chUpdate, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($chUpdate, CURLOPT_USERPWD, "user:".$apiKey);
    
    $responseUpdate= curl_exec($chUpdate);
    $responseUpdate= json_decode($responseUpdate);
    curl_close($chUpdate);
    
    if(isset($responseUpdate->id)) {
        exit($responseUpdate->id);
    } else {
        exit("ko");
    }
}



