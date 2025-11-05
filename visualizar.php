<?php
// visualizar.php?file=nome_do_ficheiro.pdf
$uploads_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

if(empty($_GET['file'])){
    http_response_code(400);
    echo "Ficheiro não especificado.";
    exit;
}

$file = basename($_GET['file']); // evita traversal
$full = realpath($uploads_dir . $file);

// validar
if(!$full || strpos($full, realpath($uploads_dir)) !== 0){
    http_response_code(403);
    echo "Acesso negado.";
    exit;
}

if(!file_exists($full)){
    http_response_code(404);
    echo "Ficheiro não encontrado.";
    exit;
}

// detetar mime
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $full);
finfo_close($finfo);

// headers para visualização inline
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="'.basename($full).'"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;

