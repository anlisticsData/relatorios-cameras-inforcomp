<?php

require_once(__DIR__."/connection.php");
require_once(__DIR__."/RelatoriosRepository.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



 echo "<pre>";



$RelatoriosRepository =   new RelatoriosRepository(getContectionContext("inforpark_0005_0005","dev","@Dev1234","localhost"));
 
$placas = $RelatoriosRepository->placasQueEntraraoMaisNaoSairam($_GET['dt']);

print_r($placas);









 

?>