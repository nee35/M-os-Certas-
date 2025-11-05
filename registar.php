<?php
require_once 'conexao.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';
    $localizacao = trim($_POST['localizacao'] ?? '');

    // Validação
    if (empty($nome) || empty($email) || empty($password) || empty($localizacao)) {
        $msg = '⚠️ Preenche todos os campos obrigatórios!';
    } elseif ($password !== $confirmar) {
        $msg = '⚠️ As palavras-passe não coincidem!';
    } else {
        // Verifica se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $msg = '⚠️ Este email já está registado!';
        } else {
            // Cria a conta
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nome, email, password, telefone, localizacao) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $hash, $telefone, $localizacao]);
            $user_id = $pdo->lastInsertId();

            // Gera o código aleatório
            $codigo = rand(100000, 999999);

            // Guarda o código na tabela de confirmações
            $stmtCod = $pdo->prepare("INSERT INTO confirmacoes (user_id, codigo) VALUES (?, ?)");
            $stmtCod->execute([$user_id, $codigo]);

            // Envia email
            $assunto = "Confirmação de Conta - Mãos Certas";
            $mensagem = "
                <html><body style='font-family: Arial; background:#f7f7f7; padding:20px;'>
                <div style='background:#fff; border-radius:10px; padding:25px; max-width:600px; margin:auto;'>
                    <h2 style='color:#195abb;'>Olá {$nome},</h2>
                    <p>Obrigado por te registares na <strong>Mãos Certas</strong> 🙌</p>
                    <p>O teu código de confirmação é:</p>
                    <h1 style='color:#195abb; letter-spacing:3px;'>{$codigo}</h1>
                    <p>Introduz este código na página de confirmação para ativar a tua conta.</p>
                    <hr>
                    <p>Com os melhores cumprimentos,<br><strong>Equipa Mãos Certas</strong></p>
                </div></body></html>";

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: Mãos Certas <no-reply@maoscerta.pt>\r\n";

            @mail($email, $assunto, $mensagem, $headers);

            // Redirecionamento imediato para confirmar_conta.php
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>
            Swal.fire({
                icon: 'success',
                title: 'Conta criada!',
                text: 'Verifica o teu email e insere o código de confirmação.',
                confirmButtonColor: '#195abb'
            }).then(() => {
                window.location.href = 'confirmar_conta.php?email=" . urlencode($email) . "';
            });
            </script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registar - Mãos Certas</title>
  <link rel="stylesheet" href="registar_styles.css">
</head>
<body>
<section class="form-section">
  <div class="container">
    <h2>Registar Conta</h2>
    <?php if($msg): ?>
      <p class="msg"><?= $msg ?></p>
    <?php endif; ?>

    <form action="" method="POST">
      <label>Nome completo</label>
      <input type="text" name="nome" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Telefone</label>
      <input type="text" name="telefone" required>

      <label>Palavra-passe</label>
      <input type="password" name="password" required>

      <label>Confirmar palavra-passe</label>
      <input type="password" name="confirmar_password" required>
    
      <label>Morada</label>
      <input type="text" name="localizacao" required>

      <button type="submit" class="btn-primary">Registar</button>
    </form>
  </div>
</section>
</body>
</html>
