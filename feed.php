<?php
require_once 'conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'header.php';

// 🔒 Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Função para caminho seguro das imagens
function caminhoImagem($path) {
    if (empty($path)) return 'assets/default-user.png';
    $path = trim($path, '/ ');
    if (str_starts_with($path, 'uploads/')) {
        $finalPath = $path;
    } else {
        $finalPath = 'uploads/clientes/' . $path;
    }
    return file_exists($finalPath) ? $finalPath : 'assets/default-user.png';
}

// 🧩 FILTRO e PESQUISA
$categoriaFiltro = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;
$termoPesquisa   = isset($_GET['q']) ? trim($_GET['q']) : '';

$query = "
    SELECT t.*, u.nome AS cliente_nome, u.foto_perfil, c.nome AS categoria_nome
    FROM trabalhos t
    JOIN users u ON t.cliente_id = u.id
    JOIN categorias c ON t.categoria_id = c.id
    WHERE t.status = 'aberto'
";

$params = [];

// Filtro por categoria
if ($categoriaFiltro) {
    $query .= " AND t.categoria_id = :categoria";
    $params[':categoria'] = $categoriaFiltro;
}

// Filtro por pesquisa (título ou descrição)
if (!empty($termoPesquisa)) {
    $query .= " AND (t.titulo LIKE :q OR t.descricao LIKE :q)";
    $params[':q'] = "%" . $termoPesquisa . "%";
}

$query .= " ORDER BY t.data_publicacao DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$trabalhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Feed Profissional - Mãos Certas</title>
  <link rel="stylesheet" href="feed.css">
</head>
<body>

<div class="feed-layout">

  <!-- MENU LATERAL ESQUERDO -->
  <?php include 'feed_sidebar.php'; ?>

  <!-- FEED CENTRAL -->
  <main class="feed-central">
    <div class="barra-topo">
      <h2> Trabalhos Publicados</h2>

      <!-- Barra de pesquisa -->
      <form method="GET" action="feed.php" class="barra-pesquisa">
        <input 
          type="text" 
          name="q" 
          placeholder="Pesquisar por título ou descrição..." 
          value="<?= htmlspecialchars($termoPesquisa); ?>"
        >
        <?php if ($categoriaFiltro): ?>
          <input type="hidden" name="categoria" value="<?= $categoriaFiltro; ?>">
        <?php endif; ?>
        <button type="submit">🔍 Pesquisar</button>
      </form>
    </div>

    <?php if (empty($trabalhos)): ?>
      <p>Não existem trabalhos correspondentes ao filtro aplicado.</p>
    <?php else: ?>
      <?php foreach ($trabalhos as $t): ?>
        <?php
          $imagens = !empty($t['imagens']) ? explode(',', $t['imagens']) : [];
          $principal = $imagens[0] ?? null;
          $extras = array_slice($imagens, 1, 3);
          $restantes = max(count($imagens) - 4, 0);
        ?>

        <div class="post-card">
          <!-- CABEÇALHO -->
          <div class="post-header">
            <img src="<?= htmlspecialchars(caminhoImagem($t['foto_perfil'])); ?>" class="avatar" alt="Foto de perfil">
            <div>
              <h4><?= htmlspecialchars($t['cliente_nome']); ?></h4>
              <span><?= date('d/m/Y H:i', strtotime($t['data_publicacao'])); ?></span>
            </div>
          </div>

          <!-- CONTEÚDO -->
          <div class="post-content">
            <h3>
              <a href="trabalho_detalhes.php?id=<?= $t['id']; ?>">
                <?= htmlspecialchars($t['titulo']); ?>
              </a>
            </h3>
            <p><?= nl2br(htmlspecialchars($t['descricao'])); ?></p>

            <?php if (!empty($imagens)): ?>
              <div class="galeria-prof">
                <?php if ($principal): ?>
                  <div class="imagem-principal">
                    <img src="<?= htmlspecialchars(caminhoImagem($principal)); ?>" alt="Imagem principal do trabalho">
                  </div>
                <?php endif; ?>

                <?php if (!empty($extras)): ?>
                  <div class="miniaturas">
                    <?php foreach ($extras as $i => $img): ?>
                      <?php if ($i === count($extras) - 1 && $restantes > 0): ?>
                        <!-- 🔗 A última miniatura (com +X) leva ao trabalho_detalhes -->
                        <a href="trabalho_detalhes.php?id=<?= $t['id']; ?>" class="mini mais" title="Ver mais imagens">
                          <img src="<?= htmlspecialchars(caminhoImagem($img)); ?>" alt="Imagem adicional do trabalho">
                          <span>+<?= $restantes; ?></span>
                        </a>
                      <?php else: ?>
                        <div class="mini">
                          <img src="<?= htmlspecialchars(caminhoImagem($img)); ?>" alt="Imagem adicional do trabalho">
                        </div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <!-- METADADOS -->
            <div class="post-meta">
              <span><strong>Categoria:</strong> <?= htmlspecialchars($t['categoria_nome']); ?></span>
              <span><strong>Localização:</strong> <?= htmlspecialchars($t['localizacao']); ?></span>
              <span><strong>Orçamento:</strong> <?= number_format($t['orcamento'], 2, ',', '.'); ?> €</span>
            </div>
          </div>

          <!-- AÇÕES -->
          <div class="post-actions">
            <a href="trabalho_detalhes.php?id=<?= $t['id']; ?>" class="btn-detalhes">🔍 Ver detalhes</a>

            <?php if (isset($_SESSION['user_tipo']) && $_SESSION['user_tipo'] === 'profissional'): ?>
              <a href="chat.php?cliente_id=<?= $t['cliente_id']; ?>" class="btn-contactar">💬 Entrar em contacto</a>
            <?php else: ?>
              <span class="aviso-contato">⚠️ Apenas profissionais podem entrar em contacto</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <!-- PAINEL DIREITO -->
  <?php include 'feed_direita.php'; ?>

</div>

</body>
</html>
