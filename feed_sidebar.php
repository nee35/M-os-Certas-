<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

/**
 * Só mostrar categorias que têm pelo menos um trabalho publicado
 */
$stmt = $pdo->query("
    SELECT c.id, c.nome, COUNT(t.id) AS total_trabalhos
    FROM categorias c
    JOIN trabalhos t ON t.categoria_id = c.id
    WHERE t.status = 'aberto'
    GROUP BY c.id, c.nome
    ORDER BY c.nome ASC
");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categoria selecionada (para destacar visualmente)
$categoriaAtiva = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;
?>

<aside class="sidebar">
    <div class="perfil">
        <img 
            src="<?= !empty($_SESSION['user_foto'])
                ? htmlspecialchars($_SESSION['user_foto'])
                : 'assets/default-user.png'; ?>" 
            alt="Foto de perfil" 
            class="perfil-foto"
        >
        <h4><?= htmlspecialchars($_SESSION['user_nome']); ?></h4>
        <p class="tipo-user"><?= ucfirst($_SESSION['user_tipo']); ?></p>
    </div>

    <nav class="menu-lateral">
        <ul>
            <li><a href="index.php">🏠 Início</a></li>
            <li><a href="servicos.php">🛠️ Serviços</a></li>
            <li><a href="favoritos.php">❤️ Favoritos</a></li>
            <li><a href="estatisticas.php">📊 Estatísticas</a></li>
            <li><a href="logout.php">🚪 Terminar Sessão</a></li>
        </ul>
    </nav>

    <div class="categorias">
        <h3>🔍 Filtrar por Categoria</h3>
        <ul>
            <?php if ($categorias): ?>
                <li>
                    <a href="feed.php" class="<?= !$categoriaAtiva ? 'active' : ''; ?>">
                        Mostrar todas (<?= array_sum(array_column($categorias, 'total_trabalhos')); ?>)
                    </a>
                </li>
                <?php foreach ($categorias as $c): ?>
                    <li>
                        <a href="feed.php?categoria=<?= $c['id']; ?>" 
                           class="<?= $categoriaAtiva === (int)$c['id'] ? 'active' : ''; ?>">
                            <?= htmlspecialchars($c['nome']); ?>
                            <span class="badge"><?= $c['total_trabalhos']; ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li><em>Nenhum trabalho disponível</em></li>
            <?php endif; ?>
        </ul>
    </div>
</aside>    