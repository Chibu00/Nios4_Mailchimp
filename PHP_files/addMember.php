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


$data= json_decode(file_get_contents('php://input'), true);

if(!isset($data)) {
    exit("ERROR!");
}

//dati da Nios4
$email= $data["email"];
$rowToAdd= $data["numRowsTagList"];
$id_mailchimp= $data["id_mailchimp"];
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

$tagList= array();

if($rowToAdd != 0) {
    for($i = 1; $i <= $rowToAdd; $i++) {
        array_push($tagList, $data["tag$i"]);
    }
}

//controllo se è impostato un id_mailchimp all'utente. Se è impostato vuol dire che l'utente già esiste su mailchimp altrimenti no.
if($id_mailchimp == "" || !isset($id_mailchimp)) {
    //su mailchimp non c'è. Aggiungo il nuovo utente.
    $urlMailchimp= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members";

    $dataInMailchimp= array(
        "email_address" => $email,
        "status" => "subscribed",
        "tags" => $tagList,
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
       //print("L'utente è gia presente in Mailchimp");
    } else {
       //print("NUOVO UTENTE AGGIUNTO CORRETTAMENTE"); 
        exit($responseMailchimp->id);
    }
} else {
    //è già presente su mailchimp..Faccio la modifica
    //per fare la modifica prima mi tiro giu tutti gli utenti della lista con lo stesso id_mailchimp e salvo l'email corrispondente all'id_mailchimp. 
    //Poi faccio l'update
    $urlMailchimpUser= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members?count=1000";
    
    $chUser= curl_init();
    
    curl_setopt($chUser, CURLOPT_URL, $urlMailchimpUser);
    curl_setopt($chUser, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chUser, CURLOPT_USERPWD, "user:".$apiKey);
    
    $responseUser= curl_exec($chUser);
    $responseUser= json_decode($responseUser);
    curl_close($chUser);
    
    if(isset($responseUser->status) && $responseUser->status == 401) {
        exit("NoKey");
    }
    
    $userList= $responseUser->members;  
    
    foreach ($userList as $key => $value) {
        if($value->id == $id_mailchimp) {
            $emailToUpdate= $value->email_address;
            break;
        }
    }
    
    $hashEmailToUpdate= hash("md5", $emailToUpdate);
    
    //nel caso in cui io modifichi anche i tag corrispondeti all'utente devo prima eliminarli e poi aggiungere i nuovi tag 
    //////////////////////////////////////////////////////////////////////////
    //////////////////FUNZIONE RIMOZIONE TUTTI I TAG///////////////////////////
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

        $allTagList= array();

        foreach ($tagList as $key => $value) {
            array_push($allTagList, $value->name);
        }

        $urlDeleteAllTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$list_id."/members/".$emailHash."/tags";

        $tagName= array();
        $dataArray= array();
        foreach ($allTagList as $key => $value) {
            $dataArray["name"] = $value;
            $dataArray["status"] = "inactive";
            array_push($tagName, $dataArray);
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
    //////////////////////FINE FUNZIONE RIMOZIONE TUTTI I TAG///////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    
    ////////////////////////////////////////////////////////////////////////////////
    //////////////////////FUNZIONE DI AGGIUNTA TAG//////////////////////////////////
    function addTags(array $tagToAdd, string $emailHash, string $list_id, string $apikey, string $dc) {
        $urlAggiungiTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$list_id."/members/".$emailHash."/tags";
        
        $tagName= array();
        $datiInputApi= array();
        foreach ($tagToAdd as $key => $value) {
            $datiInputApi["name"]= $value;
            $datiInputApi["status"]= "active";
            array_push($tagName, $datiInputApi);
        }
        
        
        $dataAddTag= array(
            "tags" => $tagName
        );
        
        $dataAddTag= json_encode($dataAddTag);
        
        $chAddTag= curl_init();
        
        curl_setopt($chAddTag, CURLOPT_URL, $urlAggiungiTag);
        curl_setopt($chAddTag, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chAddTag, CURLOPT_POST, true);
        curl_setopt($chAddTag, CURLOPT_POSTFIELDS, $dataAddTag);
        curl_setopt($chAddTag, CURLOPT_USERPWD, "user:".$apikey);
        
        $response= curl_exec($chAddTag);
        //$response= json_decode($response);
        curl_close($chAddTag);
        
    }
    //////////////////////FINE FUNZIONE DI AGGIUNTA TAG ////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////
    
    //se non ho nessun tag allora io li cancello tutti, se invece ho dei tag mi devi fare l'aggiornamento eliminandoli tutti e aggiungendo quelli nuovi
    if(empty($tagList)) {
        deleteAllTag($hashEmailToUpdate, $mailchimp_list_id, $apiKey, $dc);
        
    } else {
        deleteAllTag($hashEmailToUpdate, $mailchimp_list_id, $apiKey, $dc);
        addTags($tagList, $hashEmailToUpdate, $mailchimp_list_id, $apiKey, $dc);
    }
    
    //dopo aver aggiornato i tag posso procedere con l'update dei campi dell'utente
    $urlMailchimpUpdate= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members/".$hashEmailToUpdate;
    
    $dataMailchimpUpdate= array(
        "email_address" => $email,
        //"status" => "subscribed",
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
    
    //$dataMailchimpUpdate= json_decode($dataMailchimpUpdate);
    if(isset($responseUpdate->id)) {
        exit($responseUpdate->id);
    } else {
        exit("ko");
    }
}



