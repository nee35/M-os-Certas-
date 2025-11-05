<?php
require_once 'conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'header.php';

// Verifica se o ID existe
if (!isset($_GET['id'])) {
    header('Location: feed_profissional.php');
    exit;
}

$id = intval($_GET['id']);

// Buscar dados do trabalho
$stmt = $pdo->prepare("
    SELECT t.*, u.nome AS cliente_nome, u.email, u.telefone, c.nome AS categoria_nome
    FROM trabalhos t
    JOIN users u ON t.cliente_id = u.id
    JOIN categorias c ON t.categoria_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$trabalho = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trabalho) {
    echo "<p>Trabalho não encontrado.</p>";
    exit;
}

// Processar imagens
$imagens = !empty($trabalho['imagens']) ? explode(',', $trabalho['imagens']) : [];
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($trabalho['titulo']); ?> - Mãos Certas</title>
  <link rel="stylesheet" href="detalhes_trabalho.css">
  <script src="https://maps.googleapis.com/maps/api/js?key=AQUI_VAI_A_TUA_API_KEY"></script>
</head>
<body>

<div class="detalhes-container">
  <div class="detalhes-header">
    <h1><?= htmlspecialchars($trabalho['titulo']); ?></h1>
    <span class="categoria"><?= htmlspecialchars($trabalho['categoria_nome']); ?></span>
  </div>

  <div class="detalhes-content">
    <!-- GALERIA DE IMAGENS -->
    <div class="galeria-detalhes">
      <?php foreach ($imagens as $img): ?>
        <img src="uploads/clientes/<?= htmlspecialchars(trim($img)); ?>" alt="Imagem do trabalho" class="thumb" onclick="abrirModal(this)">
      <?php endforeach; ?>
    </div>

    <!-- MODAL DE IMAGEM -->
    <div id="imagemModal" class="modal" onclick="fecharModal()">
      <span class="fechar">&times;</span>
      <img class="modal-content" id="imagemAmpliada">
    </div>

    <!-- INFORMAÇÕES -->
    <div class="info-bloco">
      <h2>📋 Descrição</h2>
      <p><?= nl2br(htmlspecialchars($trabalho['descricao'])); ?></p>

      <h3>💰 Orçamento estimado:</h3>
      <p><strong><?= number_format($trabalho['orcamento'], 2, ',', '.'); ?> €</strong></p>

      <h3>📍 Localização:</h3>
      <p><?= htmlspecialchars($trabalho['localizacao']); ?></p>
      <div id="map"></div>

      <h3>👤 Cliente:</h3>
      <p><?= htmlspecialchars($trabalho['cliente_nome']); ?></p>
      <p>Email: <?= htmlspecialchars($trabalho['email']); ?></p>
      <p>Telefone: <?= htmlspecialchars($trabalho['telefone']); ?></p>

      <?php if (isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'profissional'): ?>
        <div class="bloco-contato">
          <a href="chat.php?cliente_id=<?= $trabalho['cliente_id']; ?>" class="btn-contactar">
            💬 Entrar em contacto
          </a>
        </div>
      <?php else: ?>
        <p class="aviso-contato">
          ⚠️ Apenas profissionais registados podem entrar em contacto com o cliente.
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function abrirModal(img) {
  const modal = document.getElementById("imagemModal");
  const modalImg = document.getElementById("imagemAmpliada");
  modal.style.display = "flex";
  modalImg.src = img.src;
}

function fecharModal() {
  document.getElementById("imagemModal").style.display = "none";
}

function initMap() {
  const local = "<?= addslashes($trabalho['localizacao']); ?>";
  const mapa = new google.maps.Map(document.getElementById("map"), {
    zoom: 13,
    center: { lat: 0, lng: 0 },
  });
  const geocoder = new google.maps.Geocoder();
  geocoder.geocode({ address: local }, function(results, status) {
    if (status === 'OK') {
      mapa.setCenter(results[0].geometry.location);
      new google.maps.Marker({
        map: mapa,
        position: results[0].geometry.location
      });
    }
  });
}
window.onload = initMap;
</script>

</body>
</html>
