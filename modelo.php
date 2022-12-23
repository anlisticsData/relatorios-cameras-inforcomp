<?php
require(__DIR__."./fpdf/fpdf.php");


require_once(__DIR__."/RelatoriosRepository.php");


//$relatorioRemository   = new RelatoriosRepository("");


$report = array(
    "config"  =>array(
      "type"  =>"L",
    ),
    "header"  => array(
        "logo"      => "logo.png",
        "title"     => 'Relatório de Movimento Por Trabalhador',
        "subtitle"  => 'Gerado diretamente da catraca pela plataforma Inforcomp de controle de acesso a obras.',
        "periodo"   => array("dtInicial" =>"","dtFinal"=>""),
    ),
    "data"     =>[]
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
        $this->Cell(60);
        // Title
        $this->Cell(200,10,utf8_decode($this->data['header']['title']),$this->header_border,0,'C');
        $this->Ln(10);
        $this->SetFont('Arial','B',8);
        $this->Cell(60);
        $this->Cell(200,10,utf8_decode($this->data['header']['subtitle']) ,$this->header_border,0,'C');
        $this->SetFont('Arial','B',15);
        $this->Ln(10);
        $this->Cell(60);
        $this->Cell(200,10,utf8_decode(sprintf("Período entre %s e %s",$this->data['header']['periodo']['dtInicial'],$this->data['header']['periodo']['dtFinal'] )),$this->header_border,0,'R');
        // Line break
        $this->Ln(20);
        
        $lineWidth  = 295;
        $lineY      = 40;
        $lineX      = 1 ;

        $this->Line($lineWidth,$lineY,$lineX,$lineY);
    }



    function addSpaceInText($text,$spaces=0){
        $sizeText =  strlen(trim($text));
        $spc="";
       
        if( ceil($size / 18) > 0 ){
            for($next=0;$next < $spaces;$next++){
                $spc .=" ";
            }
           
        }
        return $spc.$text;
    }



    



    function createdHeader(){

          $this->AliasNbPages();
          $this->AddPage();
          $this->SetFont('Times','B',10);
          $this->SetX($this->GetX()-8);
          $this->Cell(20,10,utf8_decode('Matrícula'),$this->header_table_border,0,'C');
          $this->Cell(60,10,utf8_decode('Nome'),$this->header_table_border,0,'C');
          $this->Cell(33,10,utf8_decode('Cargo'),$this->header_table_border,0,'C');
          $this->Cell(50,10,utf8_decode('Empreiteira'),$this->header_table_border,0,'C');
          $this->Cell(23,10,'Entrada',$this->header_table_border,0,'C');
          $this->Cell(23,10,utf8_decode('Saída'),$this->header_table_border,0,'C');
          $this->Cell(20,10,utf8_decode('Crachá'),$this->header_table_border,0,'C');
          $this->Cell(40,10,utf8_decode('Obra'),$this->header_table_border,0,'C');
          $this->Cell(23,10,utf8_decode('Permanência'),$this->header_table_border,1,'C');
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

    function diffData($dataEntrada,$dataSaida){


        $data1 =$dataEntrada;
        $data2 =$dataSaida;
        $unix_data1 = strtotime($data1);
        $unix_data2 = strtotime($data2);
        $nHoras   = ($unix_data2 - $unix_data1) / 3600;
        $nMinutos = (($unix_data2 - $unix_data1) % 3600) / 60;

        return sprintf('%02d:%02d', $nHoras, $nMinutos);

    }
    function Rows($rectX,$rectY,$largura,$altura,$alturaLine=5,$object){
        $this->Rect($rectX,$rectY,$largura,$altura,false);
        $this->MultiCell($largura,$alturaLine,"",0,'L',false );
        $this->MultiCell($largura,$alturaLine,$object,0,'L',false );
        $this->MultiCell($largura,$alturaLine,"",0,'L',false );

    }

    function createdPdf(){
      $this->createdHeader();
      $lines=1;
      $lineMulltX = 230;
      $lineMulltY = 57;
      $marginLeft = 10;
      $marginBottom = 65;
      foreach($this->data['data'] as $key =>$row){
        $this->SetFont('Arial','',6);
        

        $empreiteiraDescription=utf8_decode($row["Empreiteira"]);
        $saida = (strlen(trim(utf8_decode($row["Saida"])))==0 || is_null(utf8_decode($row["Saida"]))) ? 'Em Aberto' : utf8_decode($row["Saida"]);
        $saidaAlert = (strlen(trim(utf8_decode($row["Saida"])))==0 || is_null(utf8_decode($row["Saida"]))) ? true : false;
        $permanencia =$this->diffData($row["Entrada"],$row["Saida"]);
       
        if($lines==14){
          $this->createdHeader();
          $lines=1;
        }
        $lines++;
          $this->SetFont('Arial','',6);
          $this->SetX($this->GetX()-8);
          $this->Cell(20,10,$row["Matricula"],$this->header_table_border,0,'C');
          $this->Cell(60,10,$this->formatName(utf8_decode($row["Nome"]),59),$this->header_table_border,0,'C');
          $this->Cell(33,10,utf8_decode($row["Cargo"]),$this->header_table_border,0,'C');

          $this->Cell(50,10, $this->formatName($empreiteiraDescription,33),$this->header_table_border,0,'C');
          $this->Cell(23,10,$row["Entrada"],$this->header_table_border,0,'C');
          if($saidaAlert){
            $this->SetFont('Arial','B',10);
            $this->SetTextColor(255,0,0);
            $this->Cell(23,10,"Em Aberto",$this->header_table_border,0,'C');
            $this->SetFont('Arial','',6);
            $this->SetTextColor(0,0,0);
            $permanencia = '----';
          }else{
            $this->Cell(23,10,$row["Saida"],$this->header_table_border,0,'C');
          }
          
          $this->Cell(20,10,$row["Cracha"],$this->header_table_border,0,'C');
          $this->Cell(40,10,utf8_decode($row["Obra"]),$this->header_table_border,0,'C');
          $this->Cell(23,10,$permanencia,$this->header_table_border,1,'C');
       
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

$pdf->Output();

