<?php

/*
Copyright of Chibuzo Udoji 2021
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE
*/

//input da Nios4
$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERROR!");
}

$tagName= $data["tag"];
$id_mailchimp= $data["id_mailchimp"];
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

//controllo se id_mailchimp è impostato oppure no. Se è impostato vuol dire che il tag è già presente su Mailchimp
if($id_mailchimp == "" || !isset($id_mailchimp)) {
    //il tag non è presente su mailchimp. Crearlo!
    //chiamata API Mailchimp per la creazione del tag
    $urlMailchimp= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments";
    
    $ch= curl_init();

    $dataMailchimp= array(
        "name" => $tagName,
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
    //il tag è già presente su mailchimp. Sono in modifica.    
    //prendo tutti i membri che hanno quel tag per metterli in static_segment successivamente
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
    
    $memberList= array();
    
    foreach ($responseAllMemberTag->members as $key => $value) {
        array_push($memberList, $value->email_address);
    }
    
    //adesso che ho la lista dei membri che hanno quel determinato tag posso modificare quel tag e modificare i membri con il nuovo tag
    $urlUpdateTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments/".$id_mailchimp;
    
    $dataUpdateTag= array(
        "name" => $tagName,
        "static_segment" => $memberList,
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

