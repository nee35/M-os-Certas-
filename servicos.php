<?php
require_once 'conexao.php';
require_once 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cat_id = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';

// Buscar categorias
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar profissionais com média de avaliações
$params = [];
$sql = "
SELECT u.id, u.nome, u.email, u.telefone, u.foto_perfil, c.nome AS categoria_nome,
       AVG(av.classificacao) AS media_avaliacao
FROM profissionais_categorias pc
JOIN users u ON u.id = pc.profissional_id
JOIN categorias c ON c.id = pc.categoria_id
LEFT JOIN avaliacoes av ON av.id_profissional = u.id
WHERE u.tipo='profissional'
";

if ($cat_id > 0) {
    $sql .= " AND pc.categoria_id = ?";
    $params[] = $cat_id;
}

if (!empty($pesquisa)) {
    $sql .= " AND u.nome LIKE ?";
    $params[] = "%$pesquisa%";
}

$sql .= " GROUP BY u.id ORDER BY u.nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mãos Certas - Profissionais</title>
<link rel="stylesheet" href="servicos_styles.css">
<style>
/* Estilos extras para média de avaliação e favoritos */
.cat-card .avaliacao {
    margin: 0.5rem 0;
    color: #ffdd57;
    font-weight: 600;
}

.fav-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ccc;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    font-size: 16px;
    transition: 0.3s;
}

.fav-btn.favorito {
    background: #ffdd57;
}
</style>
</head>
<body>

<section class="servicos-section">
<div class="container">
    <h2>Procurar Profissionais</h2>

    <form method="GET" action="servicos.php" class="filtro-form">
        <select name="categoria" onchange="this.form.submit()">
            <option value="0">-- Todas as Categorias --</option>
            <?php foreach($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="pesquisa" placeholder="Pesquisar por nome..." value="<?= htmlspecialchars($pesquisa) ?>">
        <button type="submit">🔍</button>
    </form>

    <div class="grid-categorias">
        <?php if($profissionais): ?>
            <?php foreach($profissionais as $prof): ?>
            <div class="cat-card" style="position:relative;">
                <button class="fav-btn" onclick="toggleFavorito(this)">❤️</button>
                <div class="card-header">
                    <?php if($prof['foto_perfil']): ?>
                        <img src="<?= htmlspecialchars($prof['foto_perfil']) ?>" alt="<?= htmlspecialchars($prof['nome']) ?>" class="perfil-thumb">
                    <?php else: ?>
                        <div class="perfil-thumb placeholder">👤</div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($prof['nome']) ?></h3>
                    <p class="avaliacao">⭐ <?= round($prof['media_avaliacao'],1) ?></p>
                    <p><?= htmlspecialchars($prof['categoria_nome']) ?></p>
                </div>
                <p><strong>Email:</strong> <?= htmlspecialchars($prof['email']) ?></p>
                <p><strong>Telefone:</strong> <?= htmlspecialchars($prof['telefone']) ?></p>
                <button class="btn-perfil" onclick="abrirChat('<?= $prof['id'] ?>', '<?= htmlspecialchars($prof['nome']) ?>')">Ver Perfil / Contactar</button>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="sem-profissionais">Nenhum profissional encontrado para estes critérios.</p>
        <?php endif; ?>
    </div>
</div>
</section>

<!-- MODAL CHAT -->
<div id="modalChat" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="fecharChat()">&times;</span>
        <h3>Chat com <span id="chatNomeProfissional"></span></h3>
        <div id="chatMensagens" class="chat-box" style="max-height:300px; overflow-y:auto; margin-bottom:10px;"></div>
        <form id="formChat">
            <input type="hidden" id="chat_profissional_id">
            <textarea id="chat_mensagem" rows="3" placeholder="Escreve a tua mensagem..." required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
</div>

<script>
// Toggle favorito
function toggleFavorito(btn){
    btn.classList.toggle('favorito');
    // Aqui podes adicionar AJAX para salvar no DB
}

// Chat modal
function abrirChat(id, nome){
    document.getElementById('chatNomeProfissional').innerText = nome;
    document.getElementById('chat_profissional_id').value = id;
    document.getElementById('modalChat').style.display = 'block';
}

function fecharChat(){
    document.getElementById('modalChat').style.display = 'none';
}

document.getElementById('formChat').addEventListener('submit', function(e){
    e.preventDefault();
    let mensagem = document.getElementById('chat_mensagem').value;
    if(mensagem.trim() === '') return;
    
    let div = document.createElement('div');
    div.style.background = '#cce5ff';
    div.style.margin = '5px';
    div.style.padding = '5px 10px';
    div.style.borderRadius = '10px';
    div.innerText = mensagem;
    
    let chatBox = document.getElementById('chatMensagens');
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
    
    document.getElementById('chat_mensagem').value = '';
    // Aqui podes adicionar AJAX para enviar mensagem ao DB
});
</script>

</body>
</html>
