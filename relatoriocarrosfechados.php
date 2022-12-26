<?php
require(__DIR__."/fpdf/fpdf.php");
require_once(__DIR__."/connection.php");
require_once(__DIR__."/RelatoriosRepository.php");
$RelatoriosRepository =   new RelatoriosRepository(getContectionContext("inforpark_0005_0005","dev","@Dev1234","localhost"));
$dataDePesquisa = (isset($_GET['dt']))? $_GET['dt'] : date("Y-m-d");
$data = $RelatoriosRepository->TempoDePermanenciaPorDia($dataDePesquisa);


$report = array(
    "config"  =>array(
      "type"  =>"L",
    ),
    "header"  => array(
        "logo"      => "logo.png",
        "title"     => 'Relatório de Movimentação de Veiculos',
        "subtitle"  => 'Carros Estacionados. (Tempo de Permanência por dia. Carro que entrou e saiu + cálculo do tempo).',
        "periodo"   => array("dtInicial" =>date("Y-m-d h:m:s")),
    ),
    "data"   =>$data
);








class PDF extends FPDF
{
// Page header
    private  $header_border = 0;
    private  $header_table_border ="B";
  //  private  $multicellBorder='LRTB'; //default mostra linha
    private  $multicellBorder='B';
    private $data = [];
    function __construct($data)
    {
      $this->data =$data;
      parent::__construct($this->data['config']['type']);
    }

    function Header()
    {
        // Logo
        if(file_exists($this->data['header']['logo'])){
            $this->Image($this->data['header']['logo'],10,6,30);
        }
        // Arial bold 15
        $this->SetFont('Arial','B',15);
        // Move to the right
        $this->Ln(-6); 
        $this->Cell(40);
        // Title
        $this->Cell(200,10,utf8_decode($this->data['header']['title']),$this->header_border,0,'C');
        $this->Ln(10);
        $this->SetFont('Arial','B',8);
        $this->Cell(90);
        $this->Cell(200,10,utf8_decode($this->data['header']['subtitle']) ,$this->header_border,0,'C');
        $this->SetFont('Arial','',10);
        $this->Ln(10);
        $this->Cell(180);
        $this->Cell(100,10,utf8_decode(sprintf("Data da Consulta: %s ",$this->data['header']['periodo']['dtInicial'])),$this->header_border,0,'R');
        // Line break
        $this->Ln(20);
        
        $lineWidth  = 295;
        $lineY      = 40;
        $lineX      = 1 ;

        $this->Line($lineWidth,$lineY,$lineX,$lineY);
    }

    function createdHeader(){

      $this->header_table_border = 1;

          $this->AliasNbPages();
          $this->AddPage();
          $this->SetFont('Times','B',8);
          $this->SetX($this->GetX()-6);
          $this->Cell(10,10,utf8_decode('#'),$this->header_table_border,0,'C');
          $this->Cell(18,10,utf8_decode('PLACAS'),$this->header_table_border,0,'C');
          $this->Cell(25,10,utf8_decode('ID#ENTRADAS'),$this->header_table_border,0,'C');
          $this->Cell(25,10,utf8_decode('ID#SAIDAS'),$this->header_table_border,0,'C');
          $this->Cell(95,10,utf8_decode('PORTARIA'),$this->header_table_border,0,'L');
          $this->Cell(38,10,utf8_decode('HORA DA ENTRADA'),$this->header_table_border,0,'C');
          $this->Cell(38,10,utf8_decode('HORA DA SAIDA'),$this->header_table_border,0,'C');
          $this->Cell(38,10,utf8_decode('PERMANÊNCIA'),$this->header_table_border,1,'C');
    
          $this->SetFont('Times','',8);
    }
    

    function formatName($nome,$limit=10){
        $nomeNovo="";
        $nome =  str_replace("\n"," ",$nome);
        $nome =  str_replace("\r"," ",$nome);
        while(strlen(trim($nome))!=0){
        $nomeNovo.= sprintf("%s ", trim(substr($nome,0,$limit)));
        $nome=substr($nome,$limit,strlen(trim($nome)));
        }
        return ucfirst($nomeNovo);
    }

  

    function createdPdf(){
      $this->createdHeader();
      $lines=1;
      $registrosTotais=0;
      $registrosTotaisMovimentos=[];
      $totalDeRegistros=0;
      $permaneciaMax=0;
      $permaneciaMaxCar=null;
      $permaneciaMim=0;
      $permaneciaMimCar=null;


      $this->header_table_border=1;
      foreach($this->data['data'] as $key =>$historico){

  
        if($lines==14){
          $this->createdHeader();
          $lines=1;
        }
        $registrosTotais++;
        $this->SetFont('Arial','',9);
        $this->SetX($this->GetX()-6);
        $this->Cell(10,10,$registrosTotais,$this->header_table_border,0,'C');
        $this->Cell(18,10,$historico["placa"],$this->header_table_border,0,'C');
        $this->Cell(25,10,$historico["entradaID"],$this->header_table_border,0,'C');
        $this->Cell(25,10,$historico["saidaID"],$this->header_table_border,0,'C');
        $this->Cell(95,10,$this->formatName(utf8_decode($historico["portaria"]),59),$this->header_table_border,0,'L');
        $this->Cell(38,10,$historico["sentidoEntradaCreated_at"],$this->header_table_border,0,'C');
        $this->Cell(38,10,$historico["sentidoSaidaCreated_at"],$this->header_table_border,0,'C');
        $this->SetFont('Arial','B',12);
        if($historico["permanecia"]=='00:00'){
          $this->SetTextColor(247,26,26);
          $this->Cell(38,10,$historico["permanecia"],$this->header_table_border,1,'C');
        }else{
          $this->Cell(38,10,$historico["permanecia"],$this->header_table_border,1,'C');
        }
        $this->SetTextColor(0,0,0);
        $registrosTotaisMovimentos[]=array(
          "placa"=>$historico["placa"],"tempo"=>intval(preg_replace('/[^0-9]/', '', $historico["permanecia"]))
        );
        $totalDeRegistros+=intval(preg_replace('/[^0-9]/', '', $historico["permanecia"]));
        if(intval(preg_replace('/[^0-9]/', '',$permaneciaMax))  < intval(preg_replace('/[^0-9]/', '', $historico["permanecia"]))){
          $permaneciaMax =$historico["permanecia"];
          $permaneciaMaxCar = $historico;
        }

        if(intval(preg_replace('/[^0-9]/', '',$historico["permanecia"]))  > 1){
          $permaneciaMim =$historico["permanecia"];
          $permaneciaMimCar = $historico;
        }


        $lines++;
      }

     
 
   
      $totalGeral =0;
      $detalhes=array(
         "total" => $totalGeral ,
         "observacoes" =>
            array( 
              array( "observacaotitle"=>"Permanecia Minima","participacao"=>$permaneciaMim,"placa"=>$permaneciaMimCar["placa"],"entrada"=>$permaneciaMimCar['sentidoEntradaCreated_at'],"saida"=>$permaneciaMimCar['sentidoSaidaCreated_at']),
              array( "observacaotitle"=>"Permanecia Maxima","participacao"=>$permaneciaMax,"placa"=>$permaneciaMaxCar["placa"],"entrada"=>$permaneciaMaxCar['sentidoEntradaCreated_at'],"saida"=>$permaneciaMaxCar['sentidoSaidaCreated_at']),


           )
      );

      $this->AliasNbPages();
      $this->AddPage();
      $this->SetFont('Times','B',25);
      $this->Cell(40);
      $this->Cell(200,10,utf8_decode("Resumo Geral das Movimentações do Dia."),$this->header_border,0,'C');
      $this->Ln(10);
      $this->SetFont('Arial','B',8);
      $this->SetFont('Arial','',10);
      $this->Ln(20);
      $this->SetX($this->GetX()+12);
      $this->getRowsLine($detalhes);
    }


    private function getRowsLine($detalhes){
      $this->SetFont('Arial','B',14);
      $this->SetX($this->GetX()+8);
      $this->Cell(12,10,utf8_decode("Observações  Gerais "),$this->header_border,1,'C');
      $this->SetFont('Arial','',10);
      //var_dump($detalhes['observacoes'][0]['color']);die;
      $this->Cell(68,10,"",$this->header_table_border,0,'L');
      $this->Cell(25,10,"Periodo",$this->header_table_border,0,'C');
      $this->Cell(50,10,"Placa",$this->header_table_border,0,'C');
      $this->Cell(50,10,"Entrada",$this->header_table_border,0,'C');
      $this->Cell(50,10,"Saida",$this->header_table_border,1,'C');

      foreach($detalhes['observacoes'] as $index=>$detalhe){
        
          $participacao =  $detalhe['participacao'];
          $this->Cell(68,10,utf8_decode($detalhe['observacaotitle']),$this->header_table_border,0,'L');
          $this->Cell(25,10,sprintf("%s",$participacao),$this->header_table_border,0,'C');
          $this->Cell(50,10,sprintf("  %s ",$detalhe['placa'])   ,$this->header_table_border,0,'C');
          $this->Cell(50,10,sprintf("  %s ",$detalhe['entrada'])   ,$this->header_table_border,0,'C');
          $this->Cell(50,10,sprintf(" %s ",$detalhe['saida'])   ,$this->header_table_border,0,'C');

          $this->Ln(10);

      }
 

    }
    
    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$filename=sprintf("Mov-%s_%s.pdf",date("y_m_d_h_m_i"),uniqid());
//Output the document
 


$dir="";
 
// Instanciation of inherited class
$pdf = new PDF($report);
$pdf->createdPdf();
//$pdf->Output($dir.$filename,'F');

$pdf->Output('I',$filename);

