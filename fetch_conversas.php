<?php
require_once 'conexao.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        u.id, 
        u.nome, 
        u.foto_perfil,
        MAX(m.data_envio) AS ultima_mensagem,
        SUM(CASE WHEN m.lida = 0 AND m.destinatario_id = :me THEN 1 ELSE 0 END) AS nao_lidas
    FROM mensagens m
    JOIN users u 
        ON (CASE 
                WHEN m.remetente_id = :me THEN m.destinatario_id = u.id
                WHEN m.destinatario_id = :me THEN m.remetente_id = u.id
            END)
    WHERE m.remetente_id = :me OR m.destinatario_id = :me
    GROUP BY u.id, u.nome, u.foto_perfil
    ORDER BY ultima_mensagem DESC
");
$stmt->execute([':me' => $user_id]);
$conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($conversas): ?>
    <?php foreach ($conversas as $c): ?>
        <li>
            <img src="<?= !empty($c['foto_perfil']) ? 'uploads/clientes/' . htmlspecialchars($c['foto_perfil']) : 'assets/default-user.png'; ?>" alt="Foto de <?= htmlspecialchars($c['nome']); ?>">
            <div class="info-contato">
                <span class="nome"><?= htmlspecialchars($c['nome']); ?></span>
                <?php if ($c['nao_lidas'] > 0): ?>
                    <small class="mensagens-nao-lidas"><?= $c['nao_lidas']; ?> nova(s)</small>
                <?php else: ?>
                    <small class="ultima">Última: <?= date('d/m H:i', strtotime($c['ultima_mensagem'])); ?></small>
                <?php endif; ?>
            </div>
            <a href="chat.php?user_id=<?= $c['id']; ?>" class="btn-mini">Abrir</a>
        </li>
    <?php endforeach; ?>
<?php else: ?>
    <p>Nenhuma conversa iniciada.</p>
<?php endif; ?>
