<?php
session_start();
require_once 'conexao.php';

$sucesso = "";
$erro = "";

// Quando o formulário é enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidatura'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);

    if (empty($nome) || empty($email)) {
        $erro = "Preenche todos os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "O email fornecido não é válido.";
    } else {
        try {
            // Guardar na base de dados
            $stmt = $pdo->prepare("INSERT INTO candidatos (nome, email, data_candidatura) VALUES (?, ?, NOW())");
            $stmt->execute([$nome, $email]);

// Corpo do email com cláusulas do contrato
$assunto = "Candidatura Profissional - Mãos Certas";
$mensagem = "
<html>
<head>
  <title>Candidatura Profissional</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f6f9; color:#333; margin:0; padding:0; }
    .container { max-width: 600px; margin: 20px auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,0.1); }
    h2 { color:#0077ff; margin-bottom:10px; }
    h3 { color:#0077ff; margin-top:25px; margin-bottom:10px; }
    p { line-height:1.6; }
    ul { margin-left:20px; line-height:1.5; }
    a.button { display:inline-block; padding:12px 25px; margin:15px 0; background:#0077ff; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold; transition:0.3s; }
    a.button:hover { background:#0056b3; }
    .footer { font-size:0.9em; color:#555; margin-top:25px; }
  </style>
</head>
<body>
  <div class='container'>
    <h2>Olá {$nome}, obrigado por te candidatares!</h2>
    <p>A tua candidatura foi registada com sucesso na plataforma <strong>Mãos Certas</strong>.</p>
    <p>Fica atento: um profissional poderá entrar em contacto contigo em breve ou receberás um email com os detalhes do serviço.</p>

    <a href='http://localhost/M%C3%A3o%20Certa/registar_profissional.php' class='button'>Completa os teus dados</a>

    <h3>Detalhes da Candidatura</h3>
    <ul>
        <li>Nome: {$nome}</li>
        <li>Email: {$email}</li>
        <li>Data de candidatura: ".date('d/m/Y H:i')."</li>
    </ul>

    <h3>Cláusulas e Condições</h3>
    <p>Ao aceitar a candidatura e participar na plataforma Mãos Certas, concordas com:</p>
    <ul>
        <li>Fornecer informações verdadeiras e atualizadas.</li>
        <li>Cumprir os trabalhos acordados de forma profissional e responsável.</li>
        <li>Respeitar a confidencialidade dos clientes e dados da plataforma.</li>
        <li>Não utilizar a plataforma para fins ilegais ou fraudulentos.</li>
        <li>Aceitar os termos e condições completos disponíveis em <a href='https://www.maoscertas.pt/termos'>Termos e Condições</a>.</li>
    </ul>

    <p class='footer'>Este é um email automático. Por favor, não responda diretamente a este email.</p>
  </div>
</body>
</html>
";

            

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Mãos Certas <nao-responder@maoscertas.pt>\r\n";
            $headers .= "Reply-To: info@maoscertas.pt\r\n";

            if(mail($email, $assunto, $mensagem, $headers)){
                $sucesso = "✅ O email com a candidatura foi enviado e os dados foram guardados com sucesso!";
            } else {
                $erro = "❌ Os dados foram guardados, mas ocorreu um erro ao enviar o email.";
            }
        } catch (PDOException $e) {
            $erro = "❌ Ocorreu um erro ao guardar os dados: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="candidatura.css">
<title>Candidatura Profissional - Mãos Certas</title>
<link rel="stylesheet" href="candidatura.css">
</head>
<body>

<div class="container">
    <h1>Candidatura Como Profissional</h1>

    <?php if(!empty($sucesso)) echo "<div class='success'>".htmlspecialchars($sucesso)."</div>"; ?>
    <?php if(!empty($erro)) echo "<div class='error'>".htmlspecialchars($erro)."</div>"; ?>

    <form method="POST">
        <input type="hidden" name="candidatura" value="1">
        <label for="nome">Nome Completo</label>
        <input type="text" name="nome" id="nome" required placeholder="Ex: João Silva">

        <label for="email">Email</label>
        <input type="email" name="email" id="email" required placeholder="Ex: joao@email.com">

        <button type="submit"> Enviar Candidatura</button>
    </form>

    <div class="clausulas">
        <h4>Nota:</h4>
        <p>Ao enviar a candidatura, concordas com as cláusulas e termos da Mãos Certas, que incluem compromisso com profissionalismo, confidencialidade e veracidade das informações fornecidas.</p>
    </div>
</div>

</body>
</html>
