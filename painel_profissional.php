<?php
session_start();
require_once 'conexao.php';

// Bloquear acesso sem login ou sem permissão
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'profissional') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar dados do profissional
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profissional = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar categorias do profissional
$stmt = $pdo->prepare("
    SELECT c.nome 
    FROM categorias c
    INNER JOIN profissionais p ON c.id = p.categoria_id
    WHERE p.user_id = ?
");
$stmt->execute([$user_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Atualizar dados do perfil
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $rua = trim($_POST['rua']);
    $apartamento = trim($_POST['apartamento']);
    $numero_porta = trim($_POST['numero_porta']);
    $codigo_postal = trim($_POST['codigo_postal']);
    $iban = trim($_POST['iban']);
    $banco = trim($_POST['banco']);

    // Upload foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
        $foto_path = 'uploads/' . time() . '_' . basename($_FILES['foto_perfil']['name']);
        move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $foto_path);
        $stmt = $pdo->prepare("UPDATE users SET foto_perfil = ? WHERE id = ?");
        $stmt->execute([$foto_path, $user_id]);
        $profissional['foto_perfil'] = $foto_path;
    }

    // Upload formação
    if (isset($_FILES['formacao']) && $_FILES['formacao']['error'] === 0) {
        $formacao_path = 'uploads/' . time() . '_' . basename($_FILES['formacao']['name']);
        move_uploaded_file($_FILES['formacao']['tmp_name'], $formacao_path);
        $stmt = $pdo->prepare("UPDATE users SET formacao = ? WHERE id = ?");
        $stmt->execute([$formacao_path, $user_id]);
    }

    // Atualizar restantes dados
    $stmt = $pdo->prepare("UPDATE users SET nome=?, telefone=?, rua=?, apartamento=?, numero_porta=?, codigo_postal=?, iban=?, banco=? WHERE id=?");
    $stmt->execute([$nome, $telefone, $rua, $apartamento, $numero_porta, $codigo_postal, $iban, $banco, $user_id]);

    $profissional = array_merge($profissional, [
        'nome' => $nome,
        'telefone' => $telefone,
        'rua' => $rua,
        'apartamento' => $apartamento,
        'numero_porta' => $numero_porta,
        'codigo_postal' => $codigo_postal,
        'iban' => $iban,
        'banco' => $banco
    ]);

    $msg = "Perfil atualizado com sucesso!";
}

// Buscar serviços atribuídos ao profissional
$stmt = $pdo->prepare("
    SELECT s.id, s.titulo, s.descricao, s.estado, u.nome AS cliente_nome
    FROM servicos s
    INNER JOIN users u ON s.id_cliente = u.id
    WHERE s.id IN (
        SELECT id_servico FROM propostas WHERE id_profissional = ?
    ) OR s.estado = 'pendente'
    ORDER BY s.data_publicacao DESC
");
$stmt->execute([$user_id]);
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Profissional - Mão Certa</title>
    <link rel="stylesheet" href="painel_profissional.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="logo">Mão Certa</div>
        <nav>
            <a href="#" class="active" data-section="perfil">👤 Perfil</a>
            <a href="#" data-section="servicos">🧰 Serviços</a>
            <a href="logout.php">🚪 Sair</a>
        </nav>
    </aside>

    <main class="content">
        <header>
            <h2>Bem-vindo, <?= htmlspecialchars($profissional['nome']) ?> 👋</h2>
        </header>

        <section id="perfil" class="section active">
            <?php if ($msg): ?>
                <p class="success"><?= htmlspecialchars($msg) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="perfil-form">
                <input type="hidden" name="atualizar_perfil" value="1">

                <div class="foto-perfil">
                    <?php if (!empty($profissional['foto_perfil'])): ?>
                        <img src="<?= htmlspecialchars($profissional['foto_perfil']) ?>" alt="Foto de Perfil">
                    <?php else: ?>
                        <div class="placeholder">Sem foto</div>
                    <?php endif; ?>
                </div>

                <div class="categorias">
                    <label>Categorias:</label>
                    <?php if ($categorias): ?>
                        <ul>
                            <?php foreach ($categorias as $cat): ?>
                                <li><?= htmlspecialchars($cat) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Sem categorias atribuídas.</p>
                    <?php endif; ?>
                </div>

                <label>Nome</label>
                <input type="text" name="nome" value="<?= htmlspecialchars($profissional['nome']) ?>" required>

                <label>Telefone</label>
                <input type="text" name="telefone" value="<?= htmlspecialchars($profissional['telefone']) ?>" required>

                <label>Rua</label>
                <input type="text" name="rua" value="<?= htmlspecialchars($profissional['rua']) ?>" required>

                <label>Apartamento</label>
                <input type="text" name="apartamento" value="<?= htmlspecialchars($profissional['apartamento']) ?>">

                <label>Número da Porta</label>
                <input type="text" name="numero_porta" value="<?= htmlspecialchars($profissional['numero_porta']) ?>" required>

                <label>Código Postal</label>
                <input type="text" name="codigo_postal" value="<?= htmlspecialchars($profissional['codigo_postal']) ?>" required>

                <label>IBAN</label>
                <input type="text" name="iban" value="<?= htmlspecialchars($profissional['iban']) ?>" required>

                <label>Banco</label>
                <input type="text" name="banco" value="<?= htmlspecialchars($profissional['banco']) ?>" required>

                <label>Foto de Perfil</label>
                <input type="file" name="foto_perfil">

                <label>Formação</label>
                <input type="file" name="formacao">

                <button type="submit">Atualizar Perfil</button>
            </form>
        </section>

        <section id="servicos" class="section">
            <h3>Serviços Atribuídos / Pendentes</h3>
            <table>
                <tr>
                    <th>Título</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
                <?php foreach ($servicos as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['titulo']) ?></td>
                    <td><?= htmlspecialchars($s['cliente_nome']) ?></td>
                    <td><?= htmlspecialchars($s['estado']) ?></td>
                    <td><a href="servico_detalhe.php?id=<?= $s['id'] ?>">Abrir</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>
    </main>
</div>

<script>
const links = document.querySelectorAll('.sidebar nav a');
const sections = document.querySelectorAll('.section');

links.forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    links.forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    const target = link.dataset.section;
    sections.forEach(sec => sec.classList.remove('active'));
    document.getElementById(target).classList.add('active');
  });
});
</script>

</body>
</html>
