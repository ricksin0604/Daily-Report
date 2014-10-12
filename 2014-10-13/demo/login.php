<?php

// Login credentials. This example script needs a user with read access to backup
$login    = "demo";
$password = "demo";


    // Path to API
$apiHostPath = "https://pubsoap.citynetwork.se/api";

// Perform login
$AuthClient = new SoapClient($apiHostPath."/auth.wsdl");

$ret = $AuthClient->performLogin($login,
                                 $password);
$secret = null;
// Validate result
switch ($ret["result"]) {
case "login_ok":
    // Successful login, store secret key as we need to use this for future requests
    $secret = $ret["secret"]; // You might want to store this in a session or cache as it can be used many times.
    break;
case "login_failure":
    echo "Invalid credentials. Login failed\n";
    exit();
    break;
case "twofactor_required":
    // This user has two factor authentication set.
    // Make a call to request the code by SMS and then perform a two factor login
    echo "Two factor login. Not handled in this example. Exiting\n";
    exit();
    break;
default:
    echo "Unexcepted return value: {$ret["result"]}\n";
    exit();
    break;
}

if ($secret != null) {
    // Valid secret key meaning we have a valid login that can be used to make API calls
    // We test here by setting up a soap client against OnlinebackupServer
    $obClient = new SoapClient($apiHostPath."/onlinebackup.wsdl");

    // To authenticate we create an authentication soap header as defined in authentication.xsd using the secret key
    $authenticationHeader = createAuthenticationHeader($apiHostPath,
                                                     $login,
                                                     $secret);
    
    // Set the authentication header in our soap client as the first element
    $obClient->__setSoapHeaders(array($authenticationHeader));

    // Now we are all set to perform api calls. 

    echo "Successfully logged in to API. Preparing to fetch client data and usage\n";

    try {
        $serviceIdents = $obClient->getServiceIdents();
        $clients = array();
        foreach ($serviceIdents as $ident) {
            if ($ident["status"] == "active") {
                $offset = 0;
                $limit  = 100;
                while ($offset < 1000) { // Limiting to 1000 clients. This is actually not really needed as the check with number of returned rows should take care of breaking the loop when no more clients are found. Regardless we add it here to prevent eternal loop if you should accidently make a code error.
                    $tmp = $obClient->getClients($ident["serviceIdent"], $offset, $limit);
                    foreach ($tmp["clients"] as $client) {
                        // Fetch client data
                        $client["client_data"] = $obClient->getClientData($ident["serviceIdent"], $client["client_name"]);
                        $clients[] = $client;

                        echo "\rClients fetched so far: ".count($clients);
                    }
                    
                    if (count($tmp["clients"]) < $limit) {
                        // No more clients to fetch, no use trying to fetch with higher offset
                        break;
                    }
                    // Increase offset
                    $offset += $limit;
                }
            }
        }

        echo "\nFetched a total of ".count($clients)." clients\n";
        echo "Printing some statistics\n";
        echo "ClientName,OperatingSystem,UsedSpaceBytes,UsedSpaceReadable,ProtectedFilesTotal\n";
        
        foreach ($clients as $client) {
            echo "{$client["client_name"]},{$client["operating_system"]},{$client["client_data"]["usedSpace"]},{$client["client_data"]["usedSpaceReadable"]},{$client["client_data"]["protectedFiles"]}\n";
        }
    }
    catch (SoapFault $e) {
        echo "Caught soap fault error: ".$e->getMessage()."\n";
        exit();
    }
}


// Utility function to create a SoapHeader used for authenticate login
function createAuthenticationHeader($apiHostPath,
                                    $login,
                                    $secret) {
    $authHeader =
        "<ns1:Authentication>".
        " <ns1:Login>{$login}</ns1:Login>".
        " <ns1:Secret>{$secret}</ns1:Secret>".
        "</ns1:Authentication>";
    
    $authenticationHeader = new SoapHeader($apiHostPath."/authentication.xsd", "Authentication", new SoapVar($authHeader, XSD_ANYXML), true);

    return $authenticationHeader;
}

?>