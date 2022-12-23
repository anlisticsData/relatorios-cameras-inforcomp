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



    public function TempoPorPeriodo($placa,$dataInit,$dataFim){
        $movimentosPares = [];
        $calculoDeMovimentacao = [];
        $calculoDeMovimentacaoSemPAr = [];
        
        try{
            $movimentoDoDia=$this->historicoDeMovimentosPorPlacasPeriodo($dataInit,$dataFim,$placa);
            $movimentosPares[]=$movimentoDoDia;
           
            print_r($movimentoDoDia);
         
        }catch(Exception $e){
            print_r($e);die;
        }


      


        return $calculoDeMovimentacao;
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


    public function historicoDeMovimentosPorPlacasPeriodo($dataInit,$dataFim,$placa){
        $movimentos =null ;
        try{
            $sql='
                select m.codigo, p.description as \'portaria\',m.placa,c.description as \'tipo\',m.created_at,m.portatirasensor,m.codigosensor 
                FROM movimentoscameras m 
                    inner join cameras c on c.id =m.portatirasensor
                    inner join portarias p on p.id =m.codigosensor
                    where m.placa=? and ((m.created_at like ?) or (m.created_at like ?) ) order by m.codigo asc
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([$placa,sprintf("%%%s%%",$dataInit),sprintf("%%%s%%",$dataFim)])){
                $movimentos=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
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
