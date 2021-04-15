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

$email= $data["email"];
$hashEmail= hash("md5", $email);
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

//dati necessari per le API Mailchimp
$urlMailchimp= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/members/".$hashEmail;

//prima di cancellarlo definitivamente cancello tutti i tag in riferimento a quell'utente perchè se poi dovessi ricreare lo stesso utente mi terrebbe i tags di prima
//////////////////////////////////////////////////////////////////////////
//////////////////FUNZIONE RIMOZIONE TUTTI I TAG///////////////////////////
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