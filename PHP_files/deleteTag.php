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

$deletedTag = $data["deletedTag"];
$apiKey= $data["apikey"];
$mailchimp_list_id= $data["mailchimp_list_id"];

if($apiKey != "") {
    $dc= substr($apiKey, strlen($apiKey)-3);
}

$urlIdTag= "https://".$dc.".api.mailchimp.com/3.0/lists/".$mailchimp_list_id."/segments?count=1000";
$ch= curl_init();

curl_setopt($ch, CURLOPT_URL, $urlIdTag);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "user:".$apiKey);

$responseIdTag= curl_exec($ch);
curl_close($ch);
$responseIdTag= json_decode($responseIdTag);

if($responseIdTag->status == 401) {
    exit("NoKey");
}

$idTag= null;

if(array_key_exists("segments", $responseIdTag)) {
    $tagList =$responseIdTag->segments;
    foreach ($tagList as $key => $value) {
        if($value->name == $deletedTag) {
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
