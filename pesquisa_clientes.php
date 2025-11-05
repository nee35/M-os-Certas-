<?php
require_once 'conexao.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ✅ Só profissionais podem aceder a esta pesquisa
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'profissional') {
    http_response_code(403);
    exit('Acesso negado.');
}

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    exit; // nada a mostrar
}

// 🔍 Procurar clientes pelo nome ou email
$stmt = $pdo->prepare("
    SELECT id, nome, email, foto_perfil
    FROM users
    WHERE tipo = 'cliente'
      AND (nome LIKE :q OR email LIKE :q)
    ORDER BY nome ASC
    LIMIT 10
");
$stmt->execute([':q' => "%$q%"]);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Mostrar resultados em HTML (para injetar via AJAX)
if ($clientes):
?>
    <ul class="resultados-lista">
        <?php foreach ($clientes as $c): ?>
            <li>
                <a href="chat.php?user_id=<?= $c['id']; ?>">
                    <img src="<?= !empty($c['foto_perfil'])
                        ? 'uploads/clientes/' . htmlspecialchars($c['foto_perfil'])
                        : 'assets/default-user.png'; ?>" 
                        alt="<?= htmlspecialchars($c['nome']); ?>">
                    <div>
                        <strong><?= htmlspecialchars($c['nome']); ?></strong><br>
                        <small><?= htmlspecialchars($c['email']); ?></small>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p style="padding: 0.5rem; color: #666;">Nenhum cliente encontrado.</p>
<?php endif; ?>
