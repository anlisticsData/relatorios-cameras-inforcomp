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
      $this->Ln(1);

     
      

        
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

