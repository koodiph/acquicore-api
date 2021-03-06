<?php

/**
 * Authentication to Aquicore Server with the authorization grant
 * This script has to be hosted by your web server in order to make it work
 */

include __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../config.php';

use Aquicore\API\PHP\Api\Client;
use Aquicore\API\PHP\Api\Helper;
use Aquicore\API\PHP\Api\Exception\ClientException;
use Aquicore\API\PHP\Common\Scopes;

$client = new Client(array(
    'username' => $client_username,
    'password' => $client_password,
));

//Test if code is provided in get parameters (that means user has already accepted the app and has been redirected here)
if (isset($_GET["code"]))
{
    try
    {
	    // Get the token for later usage.(you can store $tokens["refresh_token"] for retrieving a new access_token next time)
	    $tokens = $client->getAccessToken();
    }
    catch (ClientException $ex)
    {
        echo "An error happend while trying to retrieve your tokens\n";
        echo "Reason : ".$ex->getMessage()."\n";
        die();
    }

    try
    {
        $helper = new Helper($client);

        $user = $helper->api('/users/me', 'GET');
        $devicelist = $helper->simplifyDeviceList();
        $mesures = $helper->getLastMeasures($client,$devicelist);
?>
        <html><body><pre><code>
<?php
        echo json_format(json_encode($mesures));
?>
        </code></pre></body></html>
<?php

    }
    catch (ClientException $ex)
    {
        echo "An error happend while trying to retrieve your last measures\n";
        echo $ex->getMessage()."\n";
    }
}
else
{
    if (isset($_GET["error"]))
    {
        if ($_GET["error"] == "access_denied")
        {
            echo "You refused to let application access your netatmo data\n";
        }
        else
        {
            echo "An error happend\n";
        }
    }
    else if (isset($_GET["start"]))
    {
        //Ok redirect to Aquicore Authorize URL
        $redirect_url = $client->getAuthorizeUrl();
        header("HTTP/1.1 ". 302);
        header("Location: " . $redirect_url);
        die();
    }
    else
    {
?>
<html>
    <body>
       <form method="GET" action="<?php echo $client->getRequestUri();?>">
           <input type='submit' name='start' value='Start'/>
       </form>
    </body>
</html>
<?php
    }
}

/**
 * Pretty print JSON string
 * @param string $json
 * @return string formated json
 */
function json_format($json)
{
    $tab          = "    ";
    $new_json     = "";
    $indent_level = 0;
    $in_string    = FALSE;

    $len = strlen($json);

    for ($c = 0; $c < $len; $c++) {
        $char = $json[$c];
        switch ($char) {
            case '{':
            case '[':
                if (!$in_string) {
                    $new_json .= $char . "\n" .
                    str_repeat($tab, $indent_level+1);
                    $indent_level++;
                } else {
                    $new_json .= $char;
                }
                break;
            case '}':
            case ']':
                if (!$in_string) {
                    $indent_level--;
                    $new_json .= "\n".str_repeat($tab, $indent_level).$char;
                } else {
                    $new_json .= $char;
                }
                break;
            case ',':
                if (!$in_string) {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                } else {
                    $new_json .= $char;
                }
                break;
            case ':':
                if (!$in_string) {
                    $new_json .= ": ";
                } else {
                    $new_json .= $char;
                }
                break;
            case '"':
                if ($c == 0){
                    $in_string = TRUE;
                } elseif ($c > 0 && $json[$c-1] != '\\') {
                    $in_string = !$in_string;
                }
            default:
                $new_json .= $char;
                break;
        }
    }
    return $new_json;
}
