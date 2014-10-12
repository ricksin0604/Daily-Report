<?php
require 'lib/nusoap.php';
$client=new nusoap_client("http://localhost/demo/service.php?wsdl");
$user_name="ricky";
$response=$client->call('price',array("name"=>"$user_name"));
if (empty($response))
    echo "Book data not available";
else
    echo $response;
?>
