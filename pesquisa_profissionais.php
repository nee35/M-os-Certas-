<?php
require_once 'conexao.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') exit;

// 🔍 Procurar profissionais pelo nome OU por categoria associada
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        u.id, 
        u.nome, 
        u.foto_perfil,
        GROUP_CONCAT(DISTINCT c.nome SEPARATOR ', ') AS categorias
    FROM users u
    LEFT JOIN profissionais_categorias pc ON pc.profissional_id = u.id
    LEFT JOIN categorias c ON c.id = pc.categoria_id
    WHERE u.tipo = 'profissional'
      AND (
        u.nome LIKE :q
        OR c.nome LIKE :q
      )
    GROUP BY u.id, u.nome, u.foto_perfil
    ORDER BY u.nome ASC
    LIMIT 10
");

$stmt->execute([':q' => '%' . $q . '%']);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<ul class="resultados-lista">
<?php if ($resultados): ?>
    <?php foreach ($resultados as $r): ?>
        <li>
            <a href="chat.php?user_id=<?= $r['id']; ?>">
                <img src="<?= !empty($r['foto_perfil']) 
                    ? 'uploads/clientes/' . htmlspecialchars($r['foto_perfil']) 
                    : 'assets/default-user.png'; ?>" alt="">
                <div>
                    <strong><?= htmlspecialchars($r['nome']); ?></strong><br>
                    <small><?= htmlspecialchars($r['categorias'] ?? 'Sem categorias associadas'); ?></small>
                </div>
            </a>
        </li>
    <?php endforeach; ?>
<?php else: ?>
    <li><em>Nenhum profissional encontrado.</em></li>
<?php endif; ?>
</ul>
