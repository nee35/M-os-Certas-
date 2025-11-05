<?php
session_start();
require_once 'conexao.php';

// segurança mínima
if(empty($_GET['id']) || !ctype_digit($_GET['id'])){
    http_response_code(400);
    echo json_encode(['error'=>'ID inválido']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM candidaturas WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$c){
    http_response_code(404);
    echo json_encode(['error'=>'Candidato não encontrado']);
    exit;
}

// Ajustar caminhos para frontend: se no DB já tens 'uploads/xxx', usa tal como está.
// Garantir html-escaping no front-end. Aqui devolvemos caminhos relativos seguros.
if(!empty($c['foto_perfil'])) {
    // se estiver apenas nome, prefixa uploads/
    if(strpos($c['foto_perfil'],'uploads/') === false){
        $c['foto_perfil'] = 'uploads/' . basename($c['foto_perfil']);
    }
}
if(!empty($c['formacao'])) {
    if(strpos($c['formacao'],'uploads/') === false){
        $c['formacao'] = 'uploads/' . basename($c['formacao']);
    }
}

// Para preview seguro do PDF, apontaremos para visualizar.php?file=<basename>
if(!empty($c['formacao'])){
    $c['formacao'] = 'uploads/' . basename($c['formacao']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($c, JSON_UNESCAPED_UNICODE);
