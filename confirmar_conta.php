<?php
require_once 'conexao.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $codigo = trim($_POST['codigo']);

    // Verifica se o código é válido
    $stmt = $pdo->prepare("SELECT * FROM codigos_confirmacao WHERE email = ? AND codigo = ?");
    $stmt->execute([$email, $codigo]);
    $confirmacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($confirmacao) {
        // Atualiza o utilizador para confirmado
        $pdo->prepare("UPDATE users SET confirmado = 1 WHERE email = ?")->execute([$email]);

        // Apaga o código da tabela
        $pdo->prepare("DELETE FROM codigos_confirmacao WHERE email = ?")->execute([$email]);

        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
          Swal.fire({
            icon: 'success',
            title: 'Conta confirmada!',
            text: 'A tua conta foi verificada com sucesso. Já podes iniciar sessão.',
            confirmButtonColor: '#195abb'
          }).then(() => {
            window.location.href = 'login.php';
          });
        </script>";
        exit;
    } else {
        $msg = "Código inválido ou email incorreto!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmar Conta - Mãos Certas</title>
  <link rel="stylesheet" href="confirmar_styles.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <section class="form-section">
    <div class="container">
      <h2>Confirma a tua conta</h2>
      <?php if($msg): ?>
        <p class="msg"><?= $msg ?></p>
      <?php endif; ?>

      <form action="" method="POST">
        <label>Email</label>
        <input type="email" name="email" placeholder="O teu email" required>

        <label>Código de confirmação</label>
        <input type="text" name="codigo" placeholder="Insere o código recebido" required>

        <button type="submit" class="btn-primary">Confirmar Conta</button>
      </form>
    </div>
  </section>
</body>
</html>
