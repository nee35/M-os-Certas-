<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

$user_id = $_SESSION['user_id'];

// Obter últimos utilizadores com quem houve contacto (clientes ou profissionais)
$stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.foto_perfil,
           (SELECT COUNT(*) FROM mensagens 
            WHERE destinatario_id = :uid AND remetente_id = u.id AND lida = 0) AS nao_lidas
    FROM users u
    WHERE u.id != :uid
    ORDER BY u.data_registo DESC
    LIMIT 5
");
$stmt->execute([':uid' => $user_id]);
$contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<aside class="feed-direita">
    <div class="box">
        <h4>📬 Pedidos de Contacto</h4>
        <ul class="lista-pedidos">
            <?php if ($contactos): ?>
                <?php foreach ($contactos as $c): ?>
                    <li>
                        <img src="<?= !empty($c['foto_perfil']) ? 'uploads/clientes/' . htmlspecialchars($c['foto_perfil']) : 'assets/default-user.png'; ?>" alt="Foto de <?= htmlspecialchars($c['nome']); ?>">
                        <div class="info-contato">
                            <span><?= htmlspecialchars($c['nome']); ?></span>
                            <?php if ($c['nao_lidas'] > 0): ?>
                                <small class="mensagens-nao-lidas"><?= $c['nao_lidas']; ?> nova(s)</small>
                            <?php endif; ?>
                        </div>
                        <a href="chat.php?user_id=<?= $c['id']; ?>" class="btn-mini">💬 Abrir Chat</a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Sem pedidos recentes.</p>
            <?php endif; ?>
        </ul>
    </div>
</aside>