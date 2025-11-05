<?php
session_start();
require_once 'conexao.php';
require_once 'fpdf186/fpdf.php'; // garante que tens fpdf na pasta /fpdf

// id
if(empty($_GET['id']) || !ctype_digit($_GET['id'])){
    exit('ID inválido');
}
$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM candidaturas WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$c) exit('Candidato não encontrado');

// criar PDF (layout institucional)
class PDF extends FPDF {
    function Header(){
        // procura logo em uploads/logo.png
        $logo = __DIR__ . '/uploads/logo.png';
        if(file_exists($logo)){
            $this->Image($logo, 12, 8, 30);
        }
        $this->SetFont('Arial','B',14);
        $this->SetXY(50,10);
        $this->Cell(0,8,utf8_decode('Mãos Certas — Ficha de Candidato'),0,1);
        $this->Ln(6);
        $this->SetDrawColor(13,110,253);
        $this->SetLineWidth(0.6);
        $this->Line(10,30,200,30);
        $this->Ln(4);
    }
    function Footer(){
        $this->SetY(-30);
        $this->SetFont('Arial','I',9);
        $this->SetTextColor(100,100,100);
        $this->Cell(0,6,utf8_decode('Documento gerado por Mãos Certas — ' . date('d/m/Y H:i')),0,1,'C');
        $this->SetFont('Arial','',8);
        $this->Cell(0,6,utf8_decode('Assinatura electrónica: ______________________'),0,0,'C');
    }
}

$pdf = new PDF('P','mm','A4');
$pdf->SetAutoPageBreak(true,30);
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

// bloco com dados principais
$pdf->SetFillColor(245,247,250);
$pdf->SetDrawColor(220,225,230);

$fields = [
  'Nome' => $c['nome'],
  'Email' => $c['email'],
  'Telefone' => $c['telefone'],
  'NIF' => $c['nif'],
  'Tipo ID' => $c['tipo_id'],
  'Número de ID' => $c['num_id'],
  'Morada' => $c['rua'] . ' ' . $c['numero_porta'],
  'Código Postal' => $c['codigo_postal'],
  'Cidade' => $c['cidade'],
  'Distrito' => $c['distrito'],
  'Banco' => $c['banco'],
  'IBAN' => $c['iban'],
  'Categorias' => $c['categorias'],
  'Data de Candidatura' => $c['data_candidatura']
];

foreach($fields as $label => $valor){
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(50,8,utf8_decode($label.':'),0,0);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,8,utf8_decode($valor ?? '—'));
}

$pdf->Ln(6);

// foto, se existir
if(!empty($c['foto_perfil'])){
    $fotoPath = __DIR__ . DIRECTORY_SEPARATOR . $c['foto_perfil'];
    if(file_exists($fotoPath)){
        // posicionar foto no canto direito
        $y = $pdf->GetY();
        $x = 150;
        $pdf->Image($fotoPath, $x, $y, 40);
        $pdf->Ln(45);
    }
}

// anexos: listar ficheiro de formacao e link
$pdf->Ln(6);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,utf8_decode('Documentos anexados:'),0,1);
$pdf->SetFont('Arial','',10);
if(!empty($c['formacao'])){
    $pdf->MultiCell(0,6,utf8_decode('- ' . basename($c['formacao'])));
} else {
    $pdf->MultiCell(0,6,utf8_decode('- Nenhum ficheiro de formação.'));
}

// caso queiras anexar o PDF real no ficheiro final: FPDF não faz anexos facilmente.
// Em alternativa, oferecemos o PDF para download separado.

$pdfName = 'Ficha_Candidato_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $c['nome']) . '.pdf';

// enviar inline / download
$pdf->Output('D', $pdfName);
exit;
