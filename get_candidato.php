<?php
session_start();
require_once 'conexao.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] != 'consultor'){
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

if(empty($_GET['id']) || !ctype_digit($_GET['id'])){
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM candidaturas WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$c){
    http_response_code(404);
    echo json_encode(['error' => 'Candidato não encontrado']);
    exit;
}

// Ajuste: prefixar paths para o frontend (assume que os paths guardados já são "uploads/ficheiro.ext")
if(!empty($c['foto_perfil'])){
    $c['foto_perfil'] = htmlspecialchars($c['foto_perfil'], ENT_QUOTES);
}
if(!empty($c['formacao'])){
    $c['formacao'] = htmlspecialchars($c['formacao'], ENT_QUOTES);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($c);
