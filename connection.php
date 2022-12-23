<?php

function getContectionContext($base,$user,$pass,$host){
    $connexction =null;
    try {
        $connexction= $dbh = new PDO( sprintf("mysql:host=%s;dbname=%s",$host,$base),$user, $pass);
    } catch (PDOException $e) {
    }
    return $connexction;
}

?>