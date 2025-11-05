<?php
require_once 'conexao.php';
require_once 'header.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$meu_id = $_SESSION['user_id'];
$outro_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

// ==========================
// BUSCAR CONVERSAS EXISTENTES (ADAPTATIVO)
// ==========================
$tipoAtual = $_SESSION['user_tipo'] ?? 'cliente';
$tipoDestino = ($tipoAtual === 'profissional') ? 'cliente' : 'profissional';

$sql = "
    SELECT 
        u.id,
        u.nome,
        u.foto_perfil,
        MAX(m.data_envio) AS ultima_mensagem
    FROM mensagens m
    JOIN users u 
        ON (
            (m.remetente_id = :me_remetente AND m.destinatario_id = u.id)
            OR
            (m.destinatario_id = :me_destinatario AND m.remetente_id = u.id)
        )
    WHERE (m.remetente_id = :me_where1 OR m.destinatario_id = :me_where2)
      AND u.tipo = :tipoDestino
    GROUP BY u.id, u.nome, u.foto_perfil
    ORDER BY ultima_mensagem DESC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':me_remetente', $meu_id, PDO::PARAM_INT);
$stmt->bindValue(':me_destinatario', $meu_id, PDO::PARAM_INT);
$stmt->bindValue(':me_where1', $meu_id, PDO::PARAM_INT);
$stmt->bindValue(':me_where2', $meu_id, PDO::PARAM_INT);
$stmt->bindValue(':tipoDestino', $tipoDestino, PDO::PARAM_STR);
$stmt->execute();
$conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================
// VERIFICAR SE EXISTE CHAT ATIVO
// ==========================
$chatAtivo = false;
$mensagens = [];
$destinatario = null;

if ($outro_id > 0) {
    $stmt = $pdo->prepare("SELECT id, nome, foto_perfil, tipo FROM users WHERE id = ?");
    $stmt->execute([$outro_id]);
    $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($outro_id > 0) {
    $stmt = $pdo->prepare("
        SELECT id, nome, email, telefone, localizacao, foto_perfil, tipo
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$outro_id]);
    $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($destinatario) {
        $chatAtivo = true;

        // Marcar mensagens recebidas como lidas
        $pdo->prepare("UPDATE mensagens SET lida = 1 WHERE remetente_id = ? AND destinatario_id = ?")
            ->execute([$outro_id, $meu_id]);

        // Buscar histórico do chat
        $stmt = $pdo->prepare("
            SELECT * FROM mensagens
            WHERE (remetente_id = :me AND destinatario_id = :outro)
               OR (remetente_id = :outro AND destinatario_id = :me)
            ORDER BY data_envio ASC
        ");
        $stmt->execute([':me' => $meu_id, ':outro' => $outro_id]);
        $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
}
// ==========================
// ENVIO DE MENSAGEM / ANEXO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $chatAtivo) {
    $texto = trim($_POST['mensagem'] ?? '');
    $anexos = [];

    if (!empty($_FILES['anexo']['name'][0])) {
        $ext_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        $pasta_destino = "uploads/chat/";
        if (!is_dir($pasta_destino)) mkdir($pasta_destino, 0777, true);

        // 🔥 Apenas 3 primeiros ficheiros
        $total = min(count($_FILES['anexo']['name']), 3);

        for ($i = 0; $i < $total; $i++) {
            $nome = $_FILES['anexo']['name'][$i];
            $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));

            if (in_array($ext, $ext_permitidas)) {
                $nome_unico = time() . '_' . basename($nome);
                move_uploaded_file($_FILES['anexo']['tmp_name'][$i], $pasta_destino . $nome_unico);
                $anexos[] = $pasta_destino . $nome_unico;
            }
        }
    }

    if ($texto !== '' || !empty($anexos)) {
        if ($texto !== '') {
            $pdo->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, mensagem) VALUES (?, ?, ?)")
                ->execute([$meu_id, $outro_id, $texto]);
        }

        foreach ($anexos as $anexo) {
            $pdo->prepare("INSERT INTO mensagens (remetente_id, destinatario_id, anexo) VALUES (?, ?, ?)")
                ->execute([$meu_id, $outro_id, $anexo]);
        }
    }

    header("Location: chat.php?user_id=" . $outro_id);
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Chat - Mãos Certas</title>
    <link rel="stylesheet" href="feed.css">
    <link rel="stylesheet" href="chat.css">
</head>
<body>

<div class="chat-layout">

    <!-- 🟦 Sidebar esquerda -->
    <aside class="sidebar-esquerda sidebar">
        <div class="perfil-card">
            <img src="<?= !empty($_SESSION['user_foto'])
                ? htmlspecialchars($_SESSION['user_foto'])
                : 'assets/default-user.png'; ?>" 
                alt="Foto de perfil" class="perfil-foto">
            <h3><?= htmlspecialchars($_SESSION['user_nome']); ?></h3>
            <p><?= ucfirst($_SESSION['user_tipo']); ?></p>
        </div>

        <div class="barra-pesquisa-chat">
            <input type="text" id="pesquisa" placeholder="Pesquisar <?= $tipoDestino; ?>...">
            <div id="resultados-pesquisa"></div>
        </div>

        <div class="conversas-recentes">
            <h4>Conversas</h4>
            <ul class="lista-contactos" id="lista-contactos">
                <?php if ($conversas): ?>
                    <?php foreach ($conversas as $c): ?>
                        <li class="<?= $c['id'] == $outro_id ? 'ativo' : ''; ?>">
                            <a href="chat.php?user_id=<?= $c['id']; ?>">
                                <img src="<?= !empty($c['foto_perfil']) 
                                    ? 'uploads/clientes/' . htmlspecialchars($c['foto_perfil']) 
                                    : 'assets/default-user.png'; ?>" alt="Foto do utilizador">
                                <span><?= htmlspecialchars($c['nome']); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="sem-conversas">Nenhuma conversa ainda.</p>
                <?php endif; ?>
            </ul>
        </div>
    </aside>

    <!-- 🟩 Chat central -->
    <main class="chat-central">
        <?php if ($chatAtivo): ?>
            <div class="chat-container">
                <div class="chat-header">
                    <img src="<?= !empty($destinatario['foto_perfil']) 
                        ? 'uploads/clientes/' . htmlspecialchars($destinatario['foto_perfil']) 
                        : 'assets/default-user.png'; ?>" alt="Foto do destinatário">
                    <h3><?= htmlspecialchars($destinatario['nome']); ?></h3>
                </div>

                <div class="chat-mensagens" id="chat-mensagens">
                    <?php foreach ($mensagens as $m): ?>
                        <div class="msg <?= $m['remetente_id'] == $meu_id ? 'enviada' : 'recebida'; ?>">
                            <?php if (!empty($m['mensagem'])): ?>
                                <p><?= nl2br(htmlspecialchars($m['mensagem'])); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($m['anexo'])): ?>
                                <div class="anexo">
                                    <?php
                                    $ext = strtolower(pathinfo($m['anexo'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                        <a href="<?= htmlspecialchars($m['anexo']); ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($m['anexo']); ?>" alt="Imagem enviada">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($m['anexo']); ?>" target="_blank" class="ficheiro-link">
                                            📎 <?= basename($m['anexo']); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <span><?= date('H:i', strtotime($m['data_envio'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" class="chat-form" enctype="multipart/form-data" id="form-chat">
                    <div id="preview-anexo" class="preview-anexo" style="display:none;"></div>
                    <div class="chat-input-area">
                        <label for="file-upload" class="upload-btn" title="Enviar ficheiro">📎</label>
                        <input type="file" id="file-upload" name="anexo[]" accept="image/*,.pdf,.doc,.docx" multiple hidden>
                        <input type="text" name="mensagem" placeholder="Escreve uma mensagem...">
                        <button type="submit">Enviar</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="chat-vazio">
                <h2>💬 Seleciona um <?= $tipoDestino; ?></h2>
                <p>Usa a barra de pesquisa à esquerda para encontrar e iniciar uma conversa.</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- 🟧 Painel direito -->
<?php if ($chatAtivo && $destinatario): ?>
<aside class="chat-info">
    <div class="info-foto">
        <img src="<?= !empty($destinatario['foto_perfil']) 
            ? 'uploads/clientes/' . htmlspecialchars($destinatario['foto_perfil']) 
            : 'assets/default-user.png'; ?>" alt="Foto de perfil">
    </div>

    <h3><?= htmlspecialchars($destinatario['nome']); ?></h3>
    <p class="tipo"><?= ucfirst($destinatario['tipo']); ?></p>

    <div class="info-detalhes">
        <p><strong>Email:</strong> <span><?= htmlspecialchars($destinatario['email'] ?? '—'); ?></span></p>
        <p><strong>Telefone:</strong> <span><?= htmlspecialchars($destinatario['telefone'] ?? '—'); ?></span></p>
        <p><strong>Localização:</strong> <span><?= htmlspecialchars($destinatario['localizacao'] ?? '—'); ?></span></p>
    </div>
</aside>
<?php endif; ?>


<script>
// 🔍 Pesquisa dinâmica (profissional/cliente)
document.getElementById('pesquisa').addEventListener('input', async function() {
    const termo = this.value.trim();
    const resultadosDiv = document.getElementById('resultados-pesquisa');

    if (termo.length < 2) {
        resultadosDiv.innerHTML = '';
        return;
    }

    const tipoUtilizador = "<?= $_SESSION['user_tipo']; ?>";
    const endpoint = tipoUtilizador === "profissional" 
        ? "pesquisa_clientes.php" 
        : "pesquisa_profissionais.php";

    const resposta = await fetch(endpoint + '?q=' + encodeURIComponent(termo));
    const html = await resposta.text();
    resultadosDiv.innerHTML = html;
});

// 🟢 Scroll automático no final
window.addEventListener('load', () => {
    const chatMensagens = document.getElementById('chat-mensagens');
    if (chatMensagens) chatMensagens.scrollTop = chatMensagens.scrollHeight;
});

// 🔄 Auto-refresh do chat (a cada 3s)
if (<?= $chatAtivo ? 'true' : 'false'; ?>) {
    setInterval(async () => {
        const resposta = await fetch('chat_refresh.php?user_id=<?= $outro_id; ?>');
        if (!resposta.ok) return;
        const html = await resposta.text();

        const chatMensagens = document.getElementById('chat-mensagens');
        const scrollNoFundo = chatMensagens.scrollHeight - chatMensagens.scrollTop - chatMensagens.clientHeight < 100;

        chatMensagens.innerHTML = html;
        if (scrollNoFundo) chatMensagens.scrollTop = chatMensagens.scrollHeight;
    }, 3000);
}
// ==========================
// 📎 PRÉ-VISUALIZAÇÃO FLUTUANTE (máx. 3 anexos)
// ==========================
const fileInput = document.getElementById('file-upload');
const previewContainer = document.getElementById('preview-anexo');
let selectedFiles = [];

fileInput.addEventListener('change', function () {
    const newFiles = Array.from(this.files);

    // Combina e corta até 3
    selectedFiles = [...selectedFiles, ...newFiles].slice(0, 3);

    // Aviso se ultrapassar limite
    if (newFiles.length + previewContainer.children.length > 3) {
        alert('⚠️ Podes enviar no máximo 3 anexos por mensagem.');
    }

    atualizarPreview();
});

function atualizarPreview() {
    previewContainer.innerHTML = '';

    if (selectedFiles.length > 0) {
        previewContainer.style.display = 'flex';
    } else {
        previewContainer.style.display = 'none';
        return;
    }

    selectedFiles.forEach((file, index) => {
        const ext = file.name.split('.').pop().toLowerCase();
        const item = document.createElement('div');
        item.classList.add('preview-item');

        const removeBtn = document.createElement('button');
        removeBtn.classList.add('remove-btn');
        removeBtn.textContent = '✖';
        removeBtn.title = 'Remover';
        removeBtn.addEventListener('click', () => {
            selectedFiles.splice(index, 1);
            atualizarPreview();
        });

        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
            const img = document.createElement('img');
            const reader = new FileReader();
            reader.onload = e => img.src = e.target.result;
            reader.readAsDataURL(file);
            item.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.classList.add('file-icon');
            icon.textContent = '📄';
            item.appendChild(icon);
        }

        item.appendChild(removeBtn);
        previewContainer.appendChild(item);
    });

    // Atualiza o input real com os ficheiros ativos
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    fileInput.files = dt.files;
}



</script>

</body>
</html>
