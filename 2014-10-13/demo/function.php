<?php
//require 'db.php';

function login($name)
{
    $details=array(
        'ricky'=>sucess,
        'henry'=>sucess,
        'made'=>sucess
    );
    
    foreach ($details as $n=>$p)
    {
        if($name==$n)
            $login=$p;
    }
    return $login;
}