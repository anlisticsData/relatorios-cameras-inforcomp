<?php

class RelatoriosRepository{

    private $connection = null;
    private $desconcecida ='99999999';
    

    public  function __construct($connection){
        $this->connection =  $connection;
    }

   private function diffData($dataEntrada,$dataSaida){
        $entrada =   new DateTime($dataEntrada);
        $saida =   new DateTime($dataSaida);
        $intervalo =  $entrada->diff($saida);
        return sprintf('%02d:%02d', abs($intervalo->h), abs($intervalo->i));

    }


    public function placasQueEntraraoMaisNaoSairam($dataLike){
        $placas = array(
            "observacao"=>[
                            "placasDesconhecida"=>0,
                            "placasQueEntraramENaoSairamTotal"=>0,
                            "placasQueNaoEntraramESairamTotal"=>0,
                            "placasQueNaoEntraramESairam"=>[],
                        ],
                        "placasEntradas"=>[
                            "placas"=>[],
                            "historicos"=>[]
                        ],
                        "placasNaoReconhecida"=>[],

             );
        
        try{
            $placasQueEntraram =  $this->movimentosDosCarros($dataLike,1);
            $placasQueSairam =  $this->movimentosDosCarros($dataLike,2);
            $placasSaidas  = array_column($placasQueSairam,'placa');
            foreach($placasQueEntraram as $index => $object ){
                $placaLida = $object['placa'];
                if($placaLida==$this->desconcecida){
                    $placas['placasNaoReconhecida'][]=$object;
                    $placas['observacao']['placasDesconhecida']=$object['rows_'];
                    
                }else{      
                    if($placaLida!=$this->desconcecida  &&  !in_array($placaLida,$placasSaidas) ){
                        $placas['placasEntradas']['placas'][]=$object;
                        $placas['placasEntradas']['historicos'][]=$this->historicoDeMovimentosPorPlacas($dataLike,$placaLida);
                        $placas['observacao']['placasQueEntraramENaoSairamTotal']+=1;
                       
                    }else{
                        if($placaLida!=$this->desconcecida){
                            $placas['observacao']['placasQueNaoEntraramESairamTotal']+=1;
                            $placas['observacao']['placasQueNaoEntraramESairam'][]=$object;
                        }
                        
                    }

                }    

            }
        }catch(Exception $e){}
        return $placas;
    }

    public function TempoDePermanenciaPorDiaPorPlacaEPeriodo($dataInit,$dataFim){
        $resumo=[];
        try{
            $placas =  $this->getPlacasPorPeriodo($dataInit,$dataFim);

            foreach($placas as $key=>$movimento){
                $historico = $this->TempoPorPeriodo($movimento['placa'],$dataInit,$dataFim);
                if(count($historico)>0){
                    $resumo[] = $historico[0] ;
                }
            }
           
        }catch(Exception $e){}
       return $resumo;     
    }


    public function TempoDePermanenciaPorDia($data){
        $resumo=[];
        try{
            $placas =  $this->getPlacas($data);
            foreach($placas as $key=>$movimento){
                $historico = $this->TempoDePermanenciaPorDiaCarroQueEntrouESaiuMaisTempo($movimento['placa'],$data);
                if(count($historico)>0){
                    $resumo[] = $historico[0] ;
                }
            }
        }catch(Exception $e){}
       return $resumo;     
    }



    public function TempoPorPeriodo($placa,$dataInicial,$dataFinal){
        $movimentosPares = [];
        $entradas = [];
        $saidas   =[];
        $calculoDeMovimentacao = [];
        $calculoDeMovimentacaoSemPAr = [];
        try{
            $movimentoDoDia=$this->historicoDeMovimentosPorPlacasPeriodo($dataInicial,$dataFinal,$placa);

          //  echo "<pre>";print_r($movimentoDoDia);die;
            $pares = 0 ;
            $sensorAnterior = 0;             
            foreach($movimentoDoDia as $mvdia => $dia){
                if($dia['portatirasensor']==1){
                    $entradas[]=$dia;
                    $sensorAnterior=$dia['portatirasensor'];
                    $pares++;
                }
                if($dia['portatirasensor'] == 2){
                    if($sensorAnterior==1){
                        $sensorAnterior=0;
                        $saidas[]=$dia;
                    }else{
                        $calculoDeMovimentacaoSemPAr[]=$dia;
                    }
                    $pares++;
                }
                if($pares%2==0){
                    $pares=0;
                }
            }   
            
            foreach($entradas as $index=>$entrada){
                $intervalo = 0 ;
                if(isset($entradas[$index])  && isset($saidas[$index])){
                    $created_atSplit = explode(" ",$entrada['created_at']);
                    $intervalo = $this->diffData($entradas[$index]['created_at'],$saidas[$index]['created_at']);
                    $movimentosPares[]= array(
                        "entradas"=>$entrada,
                        "saidas"=> $saidas[$index],
                        "permanecia"=>$intervalo,
                        "data"     =>$created_atSplit[0]
                    
                    );
                }
            }

            $inconsistencias = [] ;
            foreach($calculoDeMovimentacaoSemPAr as $semPar){
                $inconsistencias[]= $semPar;
            }
            $movimentosPares[]["inconsitencia"]=$inconsistencias;
         
        }catch(Exception $e){
            print_r($e);die;
        }
        return $movimentosPares;
}



    public function TempoDePermanenciaPorDiaCarroQueEntrouESaiuMaisTempo($placa,$dataLike){
            $movimentosPares = [];
            $calculoDeMovimentacao = [];
            
            try{
                $movimentoDoDia=$this->historicoDeMovimentosPorPlacas($dataLike,$placa);
                if(is_countable($movimentoDoDia) && count($movimentoDoDia) % 2 == 0){
                    $movimentosPares[]=$movimentoDoDia;

                    foreach($movimentosPares as $key=>$movimento){
                        $size = count($movimento);
                        $end = intval($size/2);
                        $entradas=[];
                        $saidas=[];
                        foreach($movimento as $m){
                           if(is_countable($m) && count($m)!=0){
                              if(isset($m['codigo'])){
                                    if($m['portatirasensor']==1){$entradas[]=$m; }else{ $saidas[]=$m; }
                              }
                              
                           }
                        }
                        for($next=0; $next < $end;$next++){
                            $intervalo = 0;
                            if(isset($entradas[$next])){
                               // print_r(["T"=>$next,isset($entradas[$next]),isset($saidas[$next])]);
                                if(isset($entradas[$next])  && isset($saidas[$next])){
                                    $intervalo = $this->diffData($entradas[$next]['created_at'],$saidas[$next]['created_at']);
                                    $calculoDeMovimentacao[]=array(
                                        "entradaID"=>$entradas[$next]['codigo'],
                                        "saidaID"=>$saidas[$next]['codigo'],
                                        "placa"=>$entradas[$next]['placa'],
                                        "portaria"=>$entradas[$next]['portaria'],
                                        "sentidoEntrada"=>$entradas[$next]['portatirasensor'],
                                        "sentidoEntradaTipo"=>$entradas[$next]['tipo'],
                                        "sentidoEntradaCreated_at"=>$entradas[$next]['created_at'],
                                        "sentidoSaida"=>$saidas[$next]['portatirasensor'],
                                        "sentidoSaidaTipo"=>$saidas[$next]['tipo'],
                                        "sentidoSaidaCreated_at"=>$saidas[$next]['created_at'],
                                        "permanecia" => $intervalo
                                    );
                                }
                            }
                        }
                    }

                }
             
            }catch(Exception $e){
                print_r($e);die;
            }

            return $calculoDeMovimentacao;
    }



    public function movimentosDosCarros($dataLike,$tipoDeSensor=1){
        $movimentos =null ;
        try{
            $sql='
                select  placa ,count(codigo) as rows_
                FROM movimentoscameras  where portatirasensor=? and (created_at like ?) 
                group  by placa 
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([$tipoDeSensor,sprintf("%%%s%%",$dataLike)])){
                $movimentos=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        }catch(Exception $e){}

        return $movimentos;
    }
    

    public function historicoDeMovimentosPorPlacas($dataLike,$placa){
        $movimentos =null ;
        try{
            $sql='
                select m.codigo, p.description as \'portaria\',m.placa,c.description as \'tipo\',m.created_at,m.portatirasensor,m.codigosensor 
                FROM movimentoscameras m 
                    inner join cameras c on c.id =m.portatirasensor
                    inner join portarias p on p.id =m.codigosensor
                    where m.placa=? and (m.created_at like ?)  order by m.codigo asc
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([$placa,sprintf("%%%s%%",$dataLike)])){
                $movimentos=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        }catch(Exception $e){}

        return $movimentos;
    }


    public function historicoDeMovimentosPorPlacasPeriodo($dataInicial,$dataFinal,$placa){
        $movimentos =null ;
        try{

            /*
            
               $sql='
                select m.codigo, p.description as \'portaria\',m.placa,c.description as \'tipo\',m.created_at,m.portatirasensor,m.codigosensor 
                FROM movimentoscameras m 
                    inner join cameras c on c.id =m.portatirasensor
                    inner join portarias p on p.id =m.codigosensor
                    where  ( year(m.created_at) >= ?   and year(m.created_at) <= ?  ) and 
                           ( month(m.created_at) >= ?  and month(m.created_at) <= ?  ) and 
                           ( day(m.created_at) >= ?   and day(m.created_at) <= ?  )   and 
                            m.placa=?   order by m.created_at asc
            ';*/ 


            $sql='
                select m.codigo, p.description as \'portaria\',m.placa,c.description as \'tipo\',m.created_at,m.portatirasensor,m.codigosensor 
                FROM movimentoscameras m 
                    inner join cameras c on c.id =m.portatirasensor
                    inner join portarias p on p.id =m.codigosensor
                    where m.created_at between ? and ? and m.placa=?   order by m.created_at asc
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([$dataInicial,$dataFinal,$placa])){
                $movimentos=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            /*
             echo "<pre>";
            $stmt->debugDumpParams();
 die;
           */
            
        }catch(Exception $e){}

        return $movimentos;
    }



    public function getPlacasPorPeriodo($dataInit,$dataFim){
        $placas =null ;
        try{
            $sql='
                select  distinct  placa  FROM movimentoscameras  where   ((created_at like ?) or (created_at like ?)) 
                order by placa desc 
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([sprintf("%%%s%%",$dataInit),sprintf("%%%s%%",$dataFim)])){
                $placas=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        }catch(Exception $e){}

        return $placas;
    }


    public function getPlacas($dataLike){
        $placas =null ;
        try{
            $sql='
                select  distinct  placa  FROM movimentoscameras  where  (created_at like ?) 
                order by placa desc 
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([sprintf("%%%s%%",$dataLike)])){
                $placas=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        }catch(Exception $e){}

        return $placas;
    }
    



}
