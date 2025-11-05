<?php
session_start();
require_once 'conexao.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foto = $_FILES['foto_perfil'] ?? null;
    $formacoes_extra = $_FILES['formacoes_extra'] ?? null;

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $tipo_id = $_POST['tipo_id'] ?? '';
    $num_id = trim($_POST['num_id'] ?? '');
    $nif = trim($_POST['nif'] ?? '');
    $num_seguranca = trim($_POST['num_seguranca'] ?? '');
    $rua = trim($_POST['rua'] ?? '');
    $apartamento = trim($_POST['apartamento'] ?? '');
    $numero_porta = trim($_POST['numero_porta'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $distrito = trim($_POST['distrito'] ?? '');
    $iban = trim($_POST['iban'] ?? '');
    $banco = trim($_POST['banco'] ?? '');
    $categorias = $_POST['categorias'] ?? [];

    try {
        // upload
        function upload($file, $prefix, $subpasta = '') {
            if (!empty($file['name']) && $file['error'] === 0) {
                $dir = 'uploads/' . ($subpasta ? trim($subpasta, '/') . '/' : '');
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nomeFicheiro = time() . "_{$prefix}_" . uniqid() . "." . $ext;
                $path = $dir . $nomeFicheiro;
                move_uploaded_file($file['tmp_name'], $path);
                return $path;
            }
            return '';
        }

        // foto perfil
        $foto_path = upload($foto, 'foto', 'fotos_perfil');
        if (empty($foto_path)) throw new Exception('Por favor, envia uma foto de perfil válida.');

        $doc_id_frente_path = upload($_FILES['doc_id_frente'], 'frente');
        $doc_id_verso_path = upload($_FILES['doc_id_verso'], 'verso');
        $registo_criminal_path = upload($_FILES['registo_criminal'], 'registo');
        $curriculo_path = upload($_FILES['curriculo_europass'], 'cv');
        $declaracao_path = upload($_FILES['declaracao_12ano'], '12ano');

        $formacoes_extra_paths = [];
        if ($formacoes_extra && isset($formacoes_extra['name'])) {
            foreach ($formacoes_extra['name'] as $i => $nome_arquivo) {
                if ($formacoes_extra['error'][$i] === 0) {
                    $path = 'uploads/' . time() . '_formacao_' . basename($nome_arquivo);
                    move_uploaded_file($formacoes_extra['tmp_name'][$i], $path);
                    $formacoes_extra_paths[] = $path;
                }
            }
        }

        $check = $pdo->prepare("SELECT COUNT(*) FROM candidaturas WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            $msg = "<script>Swal.fire({icon:'warning',title:'Candidatura já existente',text:'Já existe uma candidatura associada a este email.'});</script>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO candidaturas 
            (nome, email, telefone, tipo_id, num_id, nif, num_seguranca, rua, apartamento, numero_porta, codigo_postal, cidade, distrito, iban, banco, foto_perfil, formacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $tipo_id, $num_id, $nif, $num_seguranca, $rua, $apartamento, $numero_porta, $codigo_postal, $cidade, $distrito, $iban, $banco, $foto_path, $curriculo_path]);
            
            $candidatura_id = $pdo->lastInsertId();
            $stmtCat = $pdo->prepare("INSERT INTO candidatura_categorias (candidatura_id, categoria_id) VALUES (?, ?)");
            foreach ($categorias as $cat_id) $stmtCat->execute([$candidatura_id, $cat_id]);

            $msg = "<script>
                Swal.fire({icon:'success',title:'Candidatura enviada!',text:'A tua candidatura foi recebida e será analisada.',confirmButtonColor:'#195abb'})
                .then(()=>window.location.href='index.php');
            </script>";
        }
    } catch (Exception $e) {
        $msg = "<script>Swal.fire({icon:'error',title:'Erro',text:'".addslashes($e->getMessage())."'});</script>";
    }
}
$cats = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Registo de Profissional - Mãos Certas</title>
<link rel="stylesheet" href="registar_profissional_styles.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.foto-container{
  display:flex;align-items:center;gap:20px;
  background:#fff;padding:20px;border-radius:15px;
  box-shadow:0 2px 10px rgba(0,0,0,0.1);
  margin:20px auto;max-width:600px
}
.foto-preview{
  width:120px;height:120px;border-radius:50%;
  object-fit:cover;border:3px solid #0077ff
}
.foto-input label{
  background:#0077ff;color:#fff;padding:10px 18px;
  border-radius:8px;font-weight:600;cursor:pointer;
  transition:0.3s;display:inline-block
}
.foto-input label:hover{background:#005ecc}
.foto-input input{display:none}
</style>
</head>
<body>
<header class="gov-header">
  <div class="logo">MÃOS CERTAS</div>
  <h1>Registo Como Profissional</h1>
</header>

<?= $msg ?>

<!-- FOTO DE PERFIL -->
<div class="foto-container">
  <img id="preview-img" src="assets/default-user.png" class="foto-preview" alt="Pré-visualização da foto">
  <div class="foto-input">
    <label for="foto_perfil">📸 Escolher Foto de Perfil</label>
    <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" form="formProfissional" required>
    <small style="display:block;margin-top:8px;color:#555;">A foto será usada no teu perfil profissional.</small>
  </div>
</div>

<form id="formProfissional" class="gov-form" action="" method="POST" enctype="multipart/form-data">
<section class="form-section white">
<h2>1. Dados Pessoais</h2>
<div class="grid">
<label>Nome completo <span>*</span><input type="text" name="nome" maxlength="100" required></label>
<label>Email <span>*</span><input type="email" name="email" required></label>
<label>Telefone (+351) <span>*</span><input type="tel" name="telefone" pattern="351[0-9]{9}" maxlength="12" placeholder="351XXXXXXXXX" required></label>
<label>NIF <span>*</span><input type="text" name="nif" pattern="[0-9]{9}" maxlength="9" placeholder="9 dígitos" required></label>
<label>Tipo de Identificação <span>*</span>
<select name="tipo_id" required>
<option value="">Selecione</option>
<option value="cc">Cartão de Cidadão</option>
<option value="passaporte">Passaporte</option>
</select></label>
<label>Número de Identificação <span>*</span><input type="text" name="num_id" maxlength="20" required></label>
<label>Número de Segurança Social <span>*</span><input type="text" name="num_seguranca" maxlength="11" pattern="[0-9]{11}" placeholder="11 dígitos" required></label>
</div>
</section>

<section class="form-section gray">
<h2>2. Localização</h2>
<div class="grid">
<label>Rua <span>*</span><input type="text" name="rua" required></label>
<label>Número da Porta <span>*</span><input type="text" name="numero_porta" required></label>
<label>Apartamento <span>*</span><input type="text" name="apartamento"></label>
<label>Código Postal <span>*</span><input type="text" name="codigo_postal" pattern="[0-9]{4}-[0-9]{3}" placeholder="0000-000" required></label>
</div>
</section>

<section class="form-section white">
<h2>3. Dados Bancários</h2>
<div class="grid">
<label>IBAN (PT50...) <span>*</span><input type="text" name="iban" pattern="PT50[A-Z0-9]{21}" placeholder="PT50XXXXXXXXXXXXXXX" required></label>
<label>Banco <span>*</span><input type="text" name="banco" required></label>
</div>
</section>

<section class="form-section gray">
<h2>4. Documentos</h2>
<div class="grid">
<label>Documento de Identificação - Frente <span>*</span><input type="file" name="doc_id_frente" accept="image/*,application/pdf" required></label>
<label>Documento de Identificação - Verso <span>*</span><input type="file" name="doc_id_verso" accept="image/*,application/pdf" required></label>
<label>Registo Criminal (PDF) <span>*</span><input type="file" name="registo_criminal" accept="application/pdf" required></label>
<label>Currículo Europass (PDF) <span>*</span><input type="file" name="curriculo_europass" accept="application/pdf" required></label>
<label>Declaração de Conclusão do 12.º Ano (PDF) <span>*</span><input type="file" name="declaracao_12ano" accept="application/pdf" required></label>
<label>Formações / Cursos Complementares (PDF/jpg/png) <small>(opcional)</small><input type="file" name="formacoes_extra[]" accept=".pdf,image/*" multiple></label>
</div>
</section>

<section class="form-section white">
<h2>5. Categorias Profissionais em que Encaixas</h2>
<p>Escolha até <strong>3 categorias</strong>.</p>
<input type="text" id="busca-categoria" placeholder="Pesquisar categorias...">
<div class="checkbox-group" id="checkbox-group">
<?php foreach ($cats as $c): ?>
<label><input type="checkbox" name="categorias[]" value="<?= $c['id'] ?>"> <?= htmlspecialchars($c['nome']) ?></label>
<?php endforeach; ?>
</div>
</section>

<div class="submit-container">
<button type="submit" class="submit-btn">Submeter Candidatura</button>
</div>
</form>

<footer class="gov-footer">
<p>© 2025 Mãos Certas — Plataforma Profissional | Desenvolvido com padrões institucionais</p>
</footer>

<script>
const inputFoto=document.getElementById('foto_perfil');
const previewImg=document.getElementById('preview-img');
inputFoto.addEventListener('change',()=>{const f=inputFoto.files[0];if(f){const r=new FileReader();r.onload=e=>previewImg.src=e.target.result;r.readAsDataURL(f);}else{previewImg.src='assets/default-user.png';}});
const inputs=document.querySelectorAll('#checkbox-group input[type="checkbox"]');
inputs.forEach(i=>i.addEventListener('change',()=>{const c=document.querySelectorAll('#checkbox-group input:checked');if(c.length>3){i.checked=false;alert("Pode selecionar no máximo 3 categorias.");}}));
const busca=document.getElementById('busca-categoria');
const checkboxes=document.querySelectorAll('#checkbox-group label');
busca.addEventListener('input',()=>{const v=busca.value.toLowerCase();checkboxes.forEach(l=>{l.style.display=l.textContent.toLowerCase().includes(v)?'flex':'none';});});
</script>
</body>
</html>
