<?php

require_once(__DIR__."/connection.php");
require_once(__DIR__."/RelatoriosRepository.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



 echo "<pre>";



$RelatoriosRepository =   new RelatoriosRepository(getContectionContext("inforpark_0005_0005","dev","@Dev1234","localhost"));
 
//$placas = $RelatoriosRepository->placasQueEntraraoMaisNaoSairam($_GET['dt']);

$dataInit=$_GET['dtinit'];
$dataIfim=$_GET['dtfim'];



$y=$RelatoriosRepository->TempoDePermanenciaPorDiaPorPlacaEPeriodo($dataInit,$dataIfim);

print_r($y);

/*

$placas =  $RelatoriosRepository->getPlacas($dataD);
$t=[];
foreach($placas as $k=>$y){
    $r =  $RelatoriosRepository->TempoDePermanenciaPorDiaCarroQueEntrouESaiuMaisTempo($y['placa'],$dataD);

    if(count($r)>0){
        $t[]=$r ;

    }
   
}


print_r($t);



*/








 

?>