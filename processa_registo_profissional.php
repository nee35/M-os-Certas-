<?php
// processa_registo_profissional.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexao.php'; // deve definir $pdo

// Funções utilitárias
function clean($s) { return trim($s); }
function valid_iban($iban) {
    // validação simples (remover espaços e testar tamanho mínimo). Para validação completa usa uma lib.
    $iban = str_replace(' ', '', strtoupper($iban));
    return preg_match('/^[A-Z0-9]{15,34}$/', $iban);
}

$errors = [];

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método não suportado.";
    exit;
}

// Ler campos
$nome = clean($_POST['nome'] ?? '');
$email = clean($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$telefone = clean($_POST['telefone'] ?? '');
$nif = clean($_POST['nif'] ?? '');
$seg_social = clean($_POST['nss'] ?? ''); // se no form 'nss'
$rua = clean($_POST['rua'] ?? '');
$apartamento = clean($_POST['apartamento'] ?? '');
$numero_porta = clean($_POST['numero_porta'] ?? '');
$codigo_postal = clean($_POST['codigo_postal'] ?? '');
$categorias = $_POST['categorias'] ?? [];

// Dados bancários
$account_holder = clean($_POST['account_holder'] ?? '');
$bank_name = clean($_POST['bank_name'] ?? '');
$iban = strtoupper(str_replace(' ', '', $_POST['iban'] ?? ''));
$bic = strtoupper(clean($_POST['bic'] ?? ''));

// validações básicas
if ($nome === '') $errors[] = "Nome obrigatório.";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email inválido.";
if ($password === '' || strlen($password) < 6) $errors[] = "Password obrigatória (>=6 caracteres).";
if ($telefone === '') $errors[] = "Telefone obrigatório.";
if ($nif === '') $errors[] = "NIF obrigatório.";
if ($seg_social === '') $errors[] = "Número Segurança Social obrigatório.";
if ($rua === '' || $numero_porta === '' || $codigo_postal === '') $errors[] = "Localização incompleta.";
if (!is_array($categorias) || count($categorias) === 0) $errors[] = "Seleciona pelo menos 1 categoria.";
if (count($categorias) > 3) $errors[] = "Só podes selecionar no máximo 3 categorias.";

// valida IBAN simples
if ($iban === '' || !valid_iban($iban)) {
    $errors[] = "IBAN inválido.";
}
if ($bank_name === '') $errors[] = "Nome do banco obrigatório.";
if ($account_holder === '') $errors[] = "Nome do titular da conta obrigatório.";

// ficheiros
$foto_path = null;
$formacao_path = null;

// validar uploads
$upload_ok = true;
$max_img_bytes = 5 * 1024 * 1024; // 5MB
$max_doc_bytes = 10 * 1024 * 1024; // 10MB

// cria pastas se não existirem
$dir_fotos = __DIR__ . '/uploads/fotos';
$dir_docs  = __DIR__ . '/uploads/documentos';
if (!is_dir($dir_fotos)) mkdir($dir_fotos, 0755, true);
if (!is_dir($dir_docs)) mkdir($dir_docs, 0755, true);

// Foto de perfil (obrigatória)
if (empty($_FILES['foto_perfil']) || $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = "Foto de perfil obrigatória.";
} else {
    $f = $_FILES['foto_perfil'];
    if ($f['size'] > $max_img_bytes) $errors[] = "Foto demasiado grande (max 5MB).";
    $allowed_img = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_img)) $errors[] = "Formato de foto inválido. Aceita: jpg, jpeg, png, webp.";
    if (empty($errors)) {
        $novo = uniqid('foto_') . '.' . $ext;
        $dest = $dir_fotos . '/' . $novo;
        if (!move_uploaded_file($f['tmp_name'], $dest)) $errors[] = "Erro ao gravar foto.";
        else $foto_path = 'uploads/fotos/' . $novo;
    }
}

// Documento de formação (opcional ou obrigatório conforme teu form; aqui aceitamos opcional)
if (!empty($_FILES['formacao']) && isset($_FILES['formacao']) && $_FILES['formacao']['error'] === UPLOAD_ERR_OK) {
    $d = $_FILES['formacao'];
    if ($d['size'] > $max_doc_bytes) $errors[] = "Documento de formação demasiado grande (max 10MB).";
    $allowed_doc = ['pdf','jpg','jpeg','png'];
    $extd = strtolower(pathinfo($d['name'], PATHINFO_EXTENSION));
    if (!in_array($extd, $allowed_doc)) $errors[] = "Formato de certificação inválido. Aceita PDF/imagem.";
    if (empty($errors)) {
        $novo_doc = uniqid('form_') . '.' . $extd;
        $dest_doc = $dir_docs . '/' . $novo_doc;
        if (!move_uploaded_file($d['tmp_name'], $dest_doc)) $errors[] = "Erro ao gravar documento de formação.";
        else $formacao_path = 'uploads/documentos/' . $novo_doc;
    }
}

// se houver erros, mostra e pára
if (!empty($errors)) {
    echo "<h3>Foram encontrados erros:</h3><ul>";
    foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul><p><a href='javascript:history.back()'>Voltar</a></p>";
    exit;
}

// agora insere na BD dentro de transacção
try {
    $pdo->beginTransaction();

    // verifica existência de email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("Email já registado.");
    }

    // inserir user (tipo = profissional)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (nome, email, password, telefone, nif, seg_social, rua, apartamento, numero, codigo_postal, tipo, foto_perfil, formacao, bank_name, iban, bic, account_holder)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'profissional', ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nome, $email, $hash, $telefone, $nif, $seg_social, $rua, $apartamento, $numero_porta, $codigo_postal,
        $foto_path, $formacao_path, $bank_name, $iban, $bic, $account_holder
    ]);
    $user_id = $pdo->lastInsertId();

    // associar categorias (profissionais_categorias)
    $insCat = $pdo->prepare("INSERT INTO profissionais_categorias (profissional_id, categoria_id) VALUES (?, ?)");
    foreach ($categorias as $cat_id) {
        $insCat->execute([$user_id, (int)$cat_id]);
    }

    $pdo->commit();

    echo "<h3>Registo realizado com sucesso.</h3>";
    echo "<p><a href='login.php'>Fazer login</a></p>";
} catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<h3>Erro:</h3><p>" . htmlspecialchars($ex->getMessage()) . "</p>";
    echo "<p><a href='javascript:history.back()'>Voltar</a></p>";
    exit;
}
