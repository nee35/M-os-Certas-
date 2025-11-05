<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';
require_once 'header.php';

// Verifica login
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'cliente') {
    $logado = false;
} else {
    $logado = true;
    $cliente_id = $_SESSION['user_id'];
}

$sucesso = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modal_trabalho'])) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $categoria_id = $_POST['categoria'];
    $localizacao = trim($_POST['localizacao']);
    $orcamento = floatval($_POST['orcamento']);

    // Criar pasta do cliente
    $baseDir = "uploads/clientes/";
    $clienteDir = $baseDir . "cliente_" . $cliente_id . "/";
    $trabalhoDir = $clienteDir . "trabalhos/";

    if (!is_dir($trabalhoDir)) mkdir($trabalhoDir, 0777, true);

    // Upload das imagens
    $imagensGuardadas = [];
    if (!empty($_FILES['imagens']['name'][0])) {
        foreach ($_FILES['imagens']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['imagens']['name'][$key]);
            $fileTmp = $_FILES['imagens']['tmp_name'][$key];
            $fileSize = $_FILES['imagens']['size'][$key];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $permitidos = ['jpg','jpeg','png','gif'];
            if (in_array($fileType, $permitidos) && $fileSize < 5 * 1024 * 1024) {
                $novoNome = time() . "_" . uniqid() . "." . $fileType;
                $destino = $trabalhoDir . $novoNome;
                if (move_uploaded_file($fileTmp, $destino)) {
                    $imagensGuardadas[] = "cliente_" . $cliente_id . "/trabalhos/" . $novoNome;
                }
            }
        }
    }

    // Inserir trabalho na BD
    $stmt = $pdo->prepare("INSERT INTO trabalhos (cliente_id, categoria_id, titulo, descricao, localizacao, orcamento, imagens) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$cliente_id, $categoria_id, $titulo, $descricao, $localizacao, $orcamento, implode(',', $imagensGuardadas)]);
    $id_trabalho = $pdo->lastInsertId();

    // Notificar profissionais
    $query = $pdo->prepare("SELECT nome, email FROM profissionais WHERE categoria_id=?");
    $query->execute([$categoria_id]);
    $profissionais = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach($profissionais as $p){
    $to = $p['email'];
    $nomeProf = htmlspecialchars($p['nome']);
    $tituloEsc = htmlspecialchars($titulo);
    $descricaoEsc = nl2br(htmlspecialchars($descricao));
    $orcamentoEsc = number_format($orcamento,2);

    $subject = "🧰 Novo Trabalho na tua área - Mãos Certas";

    // Corpo do e-mail em HTML
    $message = "
    <html>
    <head>
      <title>Novo Trabalho - Mãos Certas</title>
      <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { padding: 20px; }
        .titulo { color: #0077ff; font-size: 18px; font-weight: bold; }
        .detalhes { margin-top: 10px; }
        .cta { margin-top: 20px; }
        a.btn { background: #0077ff; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 5px; }
      </style>
    </head>
    <body>
      <div class='container'>
        <p>Olá <strong>{$nomeProf}</strong>,</p>
        <p>Foi publicado um novo trabalho na categoria em que atuas:</p>
        <p class='detalhes'>
          <span class='titulo'>Título:</span> {$tituloEsc}<br>
          <span class='titulo'>Descrição:</span> {$descricaoEsc}<br>
          <span class='titulo'>Orçamento:</span> €{$orcamentoEsc}
        </p>
        <div class='cta'>
          <a class='btn' href='http://localhost/Mão%20Certa/painel_profissional.php?id_trabalho={$id_trabalho}'>Ver Detalhes</a>
        </div>
        <p>Cumprimentos,<br>Equipa Mãos Certas</p>
      </div>
    </body>
    </html>
    ";

    // Cabeçalhos
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Mãos Certas <notificacoes@maoscertas.pt>\r\n";
    $headers .= "Reply-To: notificacoes@maoscertas.pt\r\n";

    mail($to, $subject, $message, $headers);
}

    $sucesso = "✅ O teu trabalho foi publicado com sucesso. Os profissionais foram notificados.";
}
?>


<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mãos Certas</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-content">
    <h1>Encontra o profissional certo em poucos cliques</h1>
    <p>Da canalização à pintura, eletricidade ou jardinagem - verificados, prontos para ajudar.</p>
    <div class="cta-buttons">
      <?php if($logado): ?>
        <a href="#" class="btn-primary" id="abrirModal">Publicar um trabalho</a>
      <?php else: ?>
        <a href="login.php" class="btn-primary">Publicar um trabalho</a>
      <?php endif; ?>
      <a href="feed.php" class="btn-secondary">Trabalhos Disponiveis</a>
    </div>
  </div>
</section>

<!-- ===== CATEGORIAS ===== -->
<section class="categorias">
  <h3>O que precisares, nós resolvemos.</h3>
  <div class="grid-categorias">
    <div class="cat-card"><img src="un-electricista-trabajando-0.jpeg" alt="Eletricista"><p>Eletricista</p></div>
    <div class="cat-card"><img src="pintores-de-paredes.jpg" alt="Pintor"><p>Pintor</p></div>
    <div class="cat-card"><img src="canalizadores-algarve-2.jpg" alt="Canalizador"><p>Canalizador</p></div>
    <div class="cat-card"><img src="jardineiro.jpg" alt="Jardineiro"><p>Jardineiro</p></div>
  </div>
</section>

<!-- ===== MODAL PUBLICAR TRABALHO ===== -->
<div id="modal-trabalho" class="modal">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>Publicar um Trabalho</h2>

    <?php if(!empty($sucesso)) echo "<div class='sucesso'>".htmlspecialchars($sucesso)."</div>"; ?>

    <form class="form-trabalho" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="modal_trabalho" value="1">

      <label for="titulo">Título</label>
      <input type="text" name="titulo" id="titulo" required placeholder="Ex: Reparação de canalização">

      <label for="descricao">Descrição</label>
      <textarea name="descricao" id="descricao" rows="4" required placeholder="Descreve o serviço..."></textarea>

      <label for="categoria">Categoria</label>
      <select name="categoria" id="categoria" required>
        <option value="">-- Seleciona uma categoria --</option>
        <?php
        $cats = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
        foreach($cats as $c) echo "<option value='{$c['id']}'>".htmlspecialchars($c['nome'])."</option>";
        ?>
      </select>

      <label for="localizacao">Localização</label>
      <input type="text" name="localizacao" id="localizacao" required placeholder="Ex: Lisboa, Rua das Flores 12">

      <label for="orcamento">Orçamento (€)</label>
      <input type="number" name="orcamento" id="orcamento" min="10" step="0.01" required>

      <label for="imagens">Imagens (opcional)</label>
      <input type="file" name="imagens[]" id="imagens" accept=".jpg,.jpeg,.png,.gif" multiple>
      <div id="preview-container"></div>

      <button type="submit" class="btn-primary">📤 Publicar Trabalho</button>
    </form>
  </div>
</div>

<?php require_once 'footer.html'; ?>

<script>
// Modal
const modal = document.getElementById('modal-trabalho');
const abrir = document.getElementById('abrirModal');
const fechar = document.querySelector('.close-btn');

if (abrir) {
  abrir.addEventListener('click', e => {
    e.preventDefault();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  });
}

fechar.addEventListener('click', () => {
  modal.style.display = 'none';
  document.body.style.overflow = 'auto';
});

window.addEventListener('click', e => {
  if (e.target === modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  }
});

// Preview imagens
document.getElementById('imagens').addEventListener('change', function(e){
  const container = document.getElementById('preview-container');
  container.innerHTML='';
  Array.from(e.target.files).forEach(file => {
    if(!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = event => {
      const img = document.createElement('img');
      img.src = event.target.result;
      img.classList.add('preview-thumb');
      container.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
});
</script>

</body>
</html>