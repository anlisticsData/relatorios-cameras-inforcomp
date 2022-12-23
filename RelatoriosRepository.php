<?php

class RelatoriosRepository{

    private $connection = null;
    private $desconcecida ='99999999';
    

    public  function __construct($connection){
        $this->connection =  $connection;
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
                select p.description as \'portaria\',m.placa,c.description as \'tipo\',m.created_at 
                FROM movimentoscameras m 
                    inner join cameras c on c.id =m.portatirasensor
                    inner join portarias p on p.id =m.codigosensor
                    where m.placa=? and (m.created_at like ?)  
            ';
            $stmt = $this->connection->prepare($sql);
            if($stmt->execute([$placa,sprintf("%%%s%%",$dataLike)])){
                $movimentos=$stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
        }catch(Exception $e){}

        return $movimentos;
    }

    



}
