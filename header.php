<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

// Buscar o nome do utilizador logado (se ainda não estiver na sessão)
if (isset($_SESSION['user_id']) && !isset($_SESSION['user_nome'])) {
    $stmt = $pdo->prepare("SELECT nome FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_nome'] = $user['nome'];
    }
}
?>
<header>
  <div class="container header-container">
    <div class="logo">
      <a href="index.php">
        <img src="logo.png" alt="Logótipo Mão Certa">
      </a>
    </div>

    <nav>
      <ul>
        <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Início</a></li>
        <li><a href="servicos.php" class="<?= basename($_SERVER['PHP_SELF']) === 'servicos.php' ? 'active' : '' ?>">Serviços</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <li><span>Sr(a): <?= htmlspecialchars($_SESSION['user_nome'] ?? 'Utilizador') ?></span></li>
          <li><a href="logout.php" class="btn">Sair</a></li>
        <?php else: ?>
          <li><a href="login.php">Entrar</a></li>
          <li><a href="registar.php" class="btn">Criar Conta</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>