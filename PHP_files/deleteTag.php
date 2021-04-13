<?php
//dati di input

$data= json_decode(file_get_contents('php://input'), true);
if(!isset($data)) {
    exit("ERRORE");
}

$tagCancellato = $data["deletedTag"];
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

//devo ricavarmi l'id del tag
$urlIdTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments/";
$ch= curl_init();

curl_setopt($ch, CURLOPT_URL, $urlIdTag);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "user:".$apiKey);

$responseIdTag= curl_exec($ch);
curl_close($ch);
$responseIdTag= json_decode($responseIdTag);
//print_r($responseIdTag);

if($responseIdTag->status == 401) {
    exit("NoKey");
}

$idTag= null;

if(array_key_exists("segments", $responseIdTag)) {
    $listaTag= $responseIdTag->segments;
    foreach ($listaTag as $key => $value) {
        if($value->name == $tagCancellato) {
            $idTag= $value->id;
            break;
        }
    }
    
} else {
    exit("ko");
}

if(!isset($idTag)) {
    exit("ko");
}

//cancellazione del tag trame l'API Mailchimp
$urlDeleteTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments/".$idTag;

$ch1= curl_init();

curl_setopt($ch1, CURLOPT_URL, $urlDeleteTag);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch1, CURLOPT_USERPWD, "user:".$apiKey);

$response= curl_exec($ch1);
curl_close($ch1);

if(empty($response)) {
    exit("ok");
} else {
    exit("ko");
}


