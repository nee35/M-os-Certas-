<?php
require_once 'conexao.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Acesso negado');
}

$meu_id = $_SESSION['user_id'];
$outro_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if ($outro_id <= 0) exit;

// Marcar mensagens recebidas como lidas
$pdo->prepare("UPDATE mensagens SET lida = 1 WHERE remetente_id = ? AND destinatario_id = ?")
    ->execute([$outro_id, $meu_id]);

// Buscar todas as mensagens entre os dois utilizadores
$stmt = $pdo->prepare("
    SELECT * FROM mensagens
    WHERE (remetente_id = :me AND destinatario_id = :outro)
       OR (remetente_id = :outro AND destinatario_id = :me)
    ORDER BY data_envio ASC
");
$stmt->execute([':me' => $meu_id, ':outro' => $outro_id]);
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retorna apenas o HTML da conversa
foreach ($mensagens as $m):
?>
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
                        📎 Download: <?= basename($m['anexo']); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <span><?= date('H:i', strtotime($m['data_envio'])); ?></span>
    </div>
<?php endforeach; ?>
