<?php
session_start();
require_once 'conexao.php';
require_once 'header.php';

// Verifica login
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'cliente') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $categoria_id = $_POST['categoria'];
    $localizacao = trim($_POST['localizacao']);
    $orcamento = floatval($_POST['orcamento']);
    $cliente_id = $_SESSION['user_id'];

    // Criar pasta do cliente
    $baseDir = "uploads/clientes/";
    $clienteDir = $baseDir . "cliente_" . $cliente_id . "/";
    $trabalhoDir = $clienteDir . "trabalhos/";

    if (!is_dir($trabalhoDir)) {
        mkdir($trabalhoDir, 0777, true);
    }

    // Upload das imagens
    $imagensGuardadas = [];
    if (!empty($_FILES['imagens']['name'][0])) {
        foreach ($_FILES['imagens']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['imagens']['name'][$key]);
            $fileTmp = $_FILES['imagens']['tmp_name'][$key];
            $fileSize = $_FILES['imagens']['size'][$key];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $permitidos = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($fileType, $permitidos) && $fileSize < 5 * 1024 * 1024) {
                $novoNome = time() . "_" . uniqid() . "." . $fileType;
                $destino = $trabalhoDir . $novoNome;
                if (move_uploaded_file($fileTmp, $destino)) {
                    $imagensGuardadas[] = "cliente_" . $cliente_id . "/trabalhos/" . $novoNome;
                }
            }
        }
    }

    // Inserir o trabalho
    $stmt = $pdo->prepare("
        INSERT INTO trabalhos (cliente_id, categoria_id, titulo, descricao, localizacao, orcamento, imagens)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $cliente_id, 
        $categoria_id, 
        $titulo, 
        $descricao, 
        $localizacao, 
        $orcamento, 
        implode(',', $imagensGuardadas)
    ]);

    $id_trabalho = $pdo->lastInsertId();

$query = $pdo->prepare("
    SELECT nome, email 
    FROM profissionais 
    WHERE categoria_id = ?
");
$query->execute([$categoria_id]);
$profissionais = $query->fetchAll(PDO::FETCH_ASSOC);


    // Notificar por e-mail
    foreach ($profissionais as $p) {
        $to = $p['email'];
        $subject = "🧰 Novo Trabalho na tua área - Mãos Certas";
        $message = "Olá {$p['nome']},\n\n"
                 . "Foi publicado um novo trabalho na categoria em que atuas:\n\n"
                 . "📌 Título: $titulo\n"
                 . "📄 Descrição: $descricao\n"
                 . "💰 Orçamento: €" . number_format($orcamento, 2) . "\n\n"
                 . "Podes ver todos os detalhes e propor o teu orçamento em:\n"
                 . "👉 http://localhost/Mão%20Certa/painel_profissional.php?id_trabalho=$id_trabalho\n\n"
                 . "Cumprimentos,\nEquipa Mãos Certas.";
        $headers = "From: notificacoes@maoscertas.pt\r\n";
        mail($to, $subject, $message, $headers);
    }

    $sucesso = "✅ O teu trabalho foi publicado com sucesso. Os profissionais foram notificados.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Publicar Trabalho | Mãos Certas</title>
  <link rel="stylesheet" href="publicar_trabalho.css">
</head>
<body>
  <section class="publicar-trabalho">
    <h1>Publicar um Trabalho</h1>
    <p class="descricao">
      Aqui podes descrever o serviço que precisas, definir o teu orçamento e escolher a categoria adequada. 
      Os profissionais registados nessa área serão automaticamente notificados e poderão enviar-te propostas.
    </p>

    <div class="nota">
      <strong>Nota de Esclarecimento:</strong><br>
      Todas as informações inseridas serão analisadas pelos profissionais. Certifica-te de que a descrição é clara, 
      o orçamento é justo e a localização está correta. Trabalhos com dados falsos ou incompletos poderão ser removidos.
    </div>

    <?php if (!empty($sucesso)): ?>
      <div class="sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="POST" class="form-trabalho" enctype="multipart/form-data">
  <fieldset>
    <legend>Detalhes do Trabalho</legend>

    <label for="titulo">Título do Trabalho</label>
    <input type="text" name="titulo" id="titulo" required placeholder="Exemplo: Reparação de canalização na cozinha">

    <label for="descricao">Descrição</label>
    <textarea name="descricao" id="descricao" rows="4" required placeholder="Descreve o serviço com o máximo de detalhe possível..."></textarea>

    <label for="categoria">Categoria</label>
    <select name="categoria" id="categoria" required>
      <option value="">-- Seleciona uma categoria --</option>
      <?php
      $cats = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
      foreach ($cats as $c) {
          echo "<option value='{$c['id']}'>" . htmlspecialchars($c['nome']) . "</option>";
      }
      ?>
    </select>

    <label for="localizacao">Localização</label>
    <input type="text" name="localizacao" id="localizacao" required placeholder="Exemplo: Lisboa, Rua das Flores 12">

    <label for="orcamento">Orçamento Máximo (€)</label>
    <input type="number" name="orcamento" id="orcamento" step="0.01" min="10" required placeholder="Exemplo: 150.00">

    <label for="imagens">Imagens do Trabalho (opcional)</label>
    <input type="file" name="imagens[]" id="imagens" accept=".jpg,.jpeg,.png,.gif" multiple>

    <div id="preview-container"></div>

    <small>Podes enviar até 5 imagens, máximo 5MB cada.</small>
  </fieldset>

  <button type="submit" class="btn-primary">📤 Publicar Trabalho</button>
</form>

<script>
document.getElementById('imagens').addEventListener('change', function(e) {
  const container = document.getElementById('preview-container');
  container.innerHTML = ''; // limpa pré-visualizações anteriores

  const files = Array.from(e.target.files);

  files.forEach(file => {
    if (!file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function(event) {
      const img = document.createElement('img');
      img.src = event.target.result;
      img.classList.add('preview-thumb');
      container.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
});
</script>