<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexao.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Login válido
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_tipo'] = $user['tipo'] ?? 'cliente';

        // Redireciona conforme o tipo de utilizador
        switch ($_SESSION['user_tipo']) {
            case 'profissional':
                header("Location: painel_profissional.php");
                break;

            case 'consultor':
                header("Location: consultodoria.php");
                break;

            case 'admin':
                header("Location: administracao.php");
                break;

            case 'cliente':
            default:
                header("Location: index.php");
                break;
        }
        exit;
    } else {
        $erro = "Email ou palavra-passe incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Entrar - Mão Certa</title>
  <link rel="stylesheet" href="registar_styles.css">
</head>
<body>
<section class="form-section">
  <div class="container">
    <h2>Entrar na sua Conta</h2>

    <?php if ($erro): ?>
      <p class="msg erro"><?= htmlspecialchars($erro) ?></p>
    <?php endif; ?>

    <form action="" method="POST">
      <label for="email">Email</label>
      <input type="email" name="email" id="email" required>

      <label for="password">Palavra-passe</label>
      <input type="password" name="password" id="password" required>

      <button type="submit" class="btn-primary">Entrar</button>
    </form>

    <p>Não tem conta? <a href="registar.php">Criar conta</a></p>
  </div>
</section>
</body>
</html>
