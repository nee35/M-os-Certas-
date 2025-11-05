<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
  <div class="container">
    <h1 class="logo">Mãos <span>Certas</span></h1>
    <nav>
      <ul>

        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="/dashboard.php">Painel</a></li>
        <?php else: ?>
          <li><a href="/login.php">Entrar</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
