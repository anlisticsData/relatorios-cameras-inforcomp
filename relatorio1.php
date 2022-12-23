<?php
require(__DIR__."./fpdf/fpdf.php");


require_once(__DIR__."/connection.php");
require_once(__DIR__."/RelatoriosRepository.php");


$RelatoriosRepository =   new RelatoriosRepository(getContectionContext("inforpark_0005_0005","dev","@Dev1234","localhost"));
 

$dataDePesquisa = (isset($_GET['dt']))? $_GET['dt'] : date("Y-m-d");

$data = $RelatoriosRepository->placasQueEntraraoMaisNaoSairam($dataDePesquisa);
$report = array(
    "config"  =>array(
      "type"  =>"P",
    ),
    "header"  => array(
        "logo"      => "logo.png",
        "title"     => 'Relatório de Movimentação de Veiculos',
        "subtitle"  => 'Carros Estacionados. (Que Deram Entrada e não Deram Saída na Data do Relatório).',
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
        $this->Cell(1);
        // Title
        $this->Cell(200,10,utf8_decode($this->data['header']['title']),$this->header_border,0,'C');
        $this->Ln(10);
        $this->SetFont('Arial','B',8);
        $this->Cell(25);
        $this->Cell(200,10,utf8_decode($this->data['header']['subtitle']) ,$this->header_border,0,'C');
        $this->SetFont('Arial','',10);
        $this->Ln(10);
        $this->Cell(90);
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
          $this->SetFont('Times','B',9);
          $this->SetX($this->GetX()-6);
          $this->Cell(10,10,utf8_decode('#'),$this->header_table_border,0,'C');
          $this->Cell(30,10,utf8_decode('PLACAS'),$this->header_table_border,0,'C');
          $this->Cell(60,10,utf8_decode('NOMES'),$this->header_table_border,0,'C');
          $this->Cell(65,10,utf8_decode('LOCAL'),$this->header_table_border,0,'C');
          $this->Cell(38,10,utf8_decode('DATA DA ENTRADA'),$this->header_table_border,1,'C');
    
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
      $this->header_table_border=1;
      foreach($this->data['data']['placasEntradas']['historicos'] as $key =>$row){
        $this->SetFont('Arial','',6);
        foreach($row as $index2 =>$historico){
            if($lines==22){
              $this->createdHeader();
              $lines=1;
            }
            $registrosTotais++;
          
            $this->SetFont('Arial','',9.5);
            $this->SetX($this->GetX()-6);
            $this->SetFont('Arial','B',10.5);
            $this->Cell(10,10,$registrosTotais,$this->header_table_border,0,'C');
            $this->SetFont('Arial','',9.5);
            $this->Cell(30,10,$historico["placa"],$this->header_table_border,0,'C');
            $this->Cell(60,10,$historico["tipo"],$this->header_table_border,0,'C');
            $this->Cell(65,10,$this->formatName(utf8_decode($historico["portaria"]),59),$this->header_table_border,0,'C');
            $this->Cell(38,10,utf8_decode($historico["created_at"]),$this->header_table_border,1,'C');
            $lines++;
            
        }
      }
      $totalGeral =0;
      $totalGeral+=$this->data['data']['observacao']['placasDesconhecida'];
      $totalGeral+=$this->data['data']['observacao']['placasQueEntraramENaoSairamTotal'];
      $totalGeral+=$this->data['data']['observacao']['placasQueNaoEntraramESairamTotal'];

      $detalhes=array(
         "total" => $totalGeral ,
         "observacoes" =>
            array( 
              array( "observacaotitle"=>"Placas Não Indentificadas","participacao"=>$this->data['data']['observacao']['placasDesconhecida'],"color"=>[245,93,39]),
              array( "observacaotitle"=>"Placas Que Entraram E Não Sairam","participacao"=>$this->data['data']['observacao']['placasQueEntraramENaoSairamTotal'],"color"=>[255,0,0]),
              array( "observacaotitle"=>"Placas Que Não Entraram E Sairam","participacao"=>$this->data['data']['observacao']['placasQueNaoEntraramESairamTotal'],"color"=>[0,255,61])
            )
      );

      $this->AliasNbPages();
      $this->AddPage();
      $this->SetFont('Times','B',25);

      $this->Cell(200,10,utf8_decode("Resumo Geral das Movimentações do Dia."),$this->header_border,0,'C');
      $this->Ln(10);
      $this->SetFont('Arial','B',8);
      $this->Cell(25);
      $this->Cell(200,10,utf8_decode($this->data['header']['subtitle']) ,$this->header_border,0,'C');
      $this->SetFont('Arial','',10);
      $this->Ln(10);
      $this->Cell(90);
      $this->Cell(100,10,utf8_decode(sprintf("Data da Consulta: %s ",$this->data['header']['periodo']['dtInicial'])),$this->header_border,0,'R');
      // Line break
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
      foreach($detalhes['observacoes'] as $index=>$detalhe){
          $R=$detalhe['color'][0];
          $G=$detalhe['color'][1];
          $B=$detalhe['color'][2];
          
          $participacao = (isset($detalhe['participacao']) && $detalhe['participacao'] !=0) ? number_format(($detalhe['participacao']/$detalhes['total'])*100,2):0;
          $width = $participacao / 60 * 100;
          $this->Cell(68,10,utf8_decode($detalhe['observacaotitle']),$this->header_table_border,0,'L');
          $this->Cell(100,10,"",$this->header_table_border,0,'C');
          $this->Cell(25,10,sprintf("%s%%",$participacao),$this->header_table_border,1,'C');

          $this->SetY($this->GetY()-10);
          $this->SetX($this->GetX()+60);
          $this->SetFillColor($R,$G,$B);
          if($width==0){
            $this->SetFillColor(255,255,255);
            $this->Cell(88,10,"",$this->header_table_border,1,'C',true);
          }else{
             if($width > 70){
              $this->Cell($width-45,10,"",$this->header_table_border,1,'R',true);
             }else{
              $this->Cell($width,10,"",$this->header_table_border,1,'R',true);
             }
          }

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

