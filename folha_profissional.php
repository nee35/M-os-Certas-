<?php
session_start();
require_once 'conexao.php';
require_once 'verificar_sessao.php'; // garante login

$user_id = $_SESSION['user_id'] ?? null;

// Buscar dados do profissional
$stmt = $pdo->prepare("
    SELECT nome, email, telefone, foto_perfil 
    FROM users 
    WHERE id = ? AND tipo = 'profissional'
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login_profissional.php');
    exit;
}

// Buscar categorias associadas
$stmt = $pdo->prepare("
    SELECT c.nome 
    FROM profissionais p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.user_id = ?
");
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Painel do Profissional - Mãos Pro</title>
    <link rel="stylesheet" href="css/painel_profissional.css">
</head>
<body>
    <header>
        <h1>Mãos Pro</h1>
        <nav>
            <a href="painel_profissional.php">Início</a>
            <a href="editar_perfil.php">Editar Perfil</a>
            <a href="logout.php" class="sair">Sair</a>
        </nav>
    </header>

    <main class="container">
        <section class="perfil">
            <img src="<?= htmlspecialchars($user['foto_perfil'] ?: 'img/default.png') ?>" alt="Foto de Perfil" class="foto">
            <div>
                <h2><?= htmlspecialchars($user['nome']) ?></h2>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?></p>
                <p><strong>Categorias:</strong> <?= implode(', ', $categorias) ?></p>
            </div>
        </section>

        <section class="acoes">
            <h3>Opções Rápidas</h3>
            <div class="botoes">
                <a href="editar_perfil.php" class="btn">Editar Perfil</a>
                <a href="ver_trabalhos.php" class="btn">Trabalhos Recebidos</a>
                <a href="dados_bancarios.php" class="btn">Dados Bancários</a>
            </div>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Mãos Pro — Plataforma Profissional</p>
    </footer>
</body>
</html>
