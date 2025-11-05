<?php
session_start();
require_once 'conexao.php';

// Verificar login do consultor
if(!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] != 'consultor'){
    header("Location: login.php");
    exit;
}

// Atualizar status da candidatura
if(isset($_GET['acao'], $_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("UPDATE candidaturas SET status=? WHERE id=?");
    if($_GET['acao'] == 'aceitar'){
        $stmt->execute(['aceita',$id]);

        // Email de aceitação
        $c = $pdo->prepare("SELECT nome,email FROM candidaturas WHERE id=?");
        $c->execute([$id]);
        $cand = $c->fetch(PDO::FETCH_ASSOC);
        if($cand && filter_var($cand['email'], FILTER_VALIDATE_EMAIL)){
            $to = $cand['email'];
            $subject = "Candidatura Aceite - Mãos Certas";
            $msg = "Olá {$cand['nome']},\n\nA tua candidatura foi aceite. Em breve irás receber mais detalhes.\n\nCumprimentos,\nEquipa Mãos Certas";
            @mail($to, $subject, $msg, "From: info@maoscertas.pt");
        }

    } elseif($_GET['acao'] == 'rejeitar'){
        $stmt->execute(['rejeitada',$id]);

        // Email de rejeição
        $c = $pdo->prepare("SELECT nome,email FROM candidaturas WHERE id=?");
        $c->execute([$id]);
        $cand = $c->fetch(PDO::FETCH_ASSOC);
        if($cand && filter_var($cand['email'], FILTER_VALIDATE_EMAIL)){
            $to = $cand['email'];
            $subject = "Candidatura Rejeitada - Mãos Certas";
            $msg = "Olá {$cand['nome']},\n\nInfelizmente a tua candidatura não foi aceite.\n\nCumprimentos,\nEquipa Mãos Certas";
            @mail($to, $subject, $msg, "From: info@maoscertas.pt");
        }
    }

    header("Location: consultoria.php");
    exit;
}

// Pesquisar e filtrar
$where = [];
$params = [];

if(!empty($_GET['search'])){
    $where[] = "(nome LIKE ? OR email LIKE ?)";
    $params[] = "%".$_GET['search']."%";
    $params[] = "%".$_GET['search']."%";
}

if(!empty($_GET['status'])){
    $where[] = "status=?";
    $params[] = $_GET['status'];
}

$sql = "SELECT id, nome, email, telefone, status, data_candidatura, foto_perfil FROM candidaturas";
if($where){
    $sql .= " WHERE ".implode(" AND ", $where);
}
$sql .= " ORDER BY data_candidatura DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contadores
$pendentes = $pdo->query("SELECT COUNT(*) FROM candidaturas WHERE status='pendente'")->fetchColumn();
$aceites = $pdo->query("SELECT COUNT(*) FROM candidaturas WHERE status='aceita'")->fetchColumn();
$rejeitadas = $pdo->query("SELECT COUNT(*) FROM candidaturas WHERE status='rejeitada'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Consultoria - Mãos Certas</title>
<link rel="stylesheet" href="consultodoria.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="topbar">
    Painel Consultoria - Mãos Certas
</header>

<div class="dashboard">
    <div class="sidebar">
        <a href="#">Dashboard</a>
        <a href="#">Candidaturas</a>
        <a href="#">Estatísticas</a>
        <a href="#">Exportar CSV</a>
        <a href="logout.php">Sair</a>
    </div>

    <div class="main">
        <div class="stats">
            <div class="card">Pendentes: <?=$pendentes?></div>
            <div class="card">Aceites: <?=$aceites?></div>
            <div class="card">Rejeitadas: <?=$rejeitadas?></div>
            <div class="card">Total: <?=count($candidatos)?></div>
        </div>

        <form method="GET" class="filtros">
            <input type="text" name="search" placeholder="Pesquisar nome ou email" value="<?=htmlspecialchars($_GET['search'] ?? '')?>">
            <select name="status">
                <option value="">Todos</option>
                <option value="pendente" <?=isset($_GET['status']) && $_GET['status']=='pendente' ? 'selected' : ''?>>Pendentes</option>
                <option value="aceita" <?=isset($_GET['status']) && $_GET['status']=='aceita' ? 'selected' : ''?>>Aceites</option>
                <option value="rejeitada" <?=isset($_GET['status']) && $_GET['status']=='rejeitada' ? 'selected' : ''?>>Rejeitadas</option>
            </select>
            <button type="submit">Filtrar</button>
        </form>

        <canvas id="graficoCandidaturas"></canvas>

        <table class="cand-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($candidatos as $c): ?>
                <tr>
                    <td><?=htmlspecialchars($c['nome'])?></td>
                    <td><?=htmlspecialchars($c['email'])?></td>
                    <td><?=date('d/m/Y H:i', strtotime($c['data_candidatura']))?></td>
                    <td><?=htmlspecialchars($c['status'] ?? 'pendente')?></td>
                    <td>
                        <?php if(($c['status'] ?? 'pendente') == 'pendente'): ?>
                            <a href="?acao=aceitar&id=<?=$c['id']?>" class="status-btn aceitar">✅ Aceitar</a>
                            <a href="?acao=rejeitar&id=<?=$c['id']?>" class="status-btn rejeitar">❌ Rejeitar</a>
                        <?php endif; ?>
                        <button class="btnAnalisar" data-id="<?= $c['id'] ?>">📊 Analisar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Modal principal: dados do candidato -->
        <div id="modalAnalise" class="modal" aria-hidden="true">
            <div class="modal-content">
                <button class="close-modal" id="closeModal">&times;</button>
                <h3 id="m-nome">Resumo da Candidatura</h3>
                <div class="m-body">
                    <div class="m-left">
                        <img id="m-foto" src="" alt="Foto perfil">
                        <p><strong>Email:</strong> <span id="m-email"></span></p>
                        <p><strong>Telefone:</strong> <span id="m-telefone"></span></p>
                        <p><strong>NIF:</strong> <span id="m-nif"></span></p>
                        <p><strong>Tipo ID:</strong> <span id="m-tipoid"></span></p>
                        <p><strong>Morada:</strong> <span id="m-rua"></span></p>
                        <p><strong>Cidade / Distrito:</strong> <span id="m-cidade"></span> / <span id="m-distrito"></span></p>
                    </div>
                    <div class="m-right">
                        <p><strong>Formação (ficheiro):</strong> <span id="m-formacao"></span></p>
                        <p><strong>Categorias:</strong> <span id="m-categorias"></span></p>
                        <p><strong>IBAN:</strong> <span id="m-iban"></span></p>
                        <p><strong>Banco:</strong> <span id="m-banco"></span></p>
                        <p><strong>Data candidatura:</strong> <span id="m-data"></span></p>

                        <div class="m-actions">
                            <button id="verCvBtn" class="btn">📖 Ver CV</button>
                            <a id="downloadCv" class="btn outline" target="_blank">⬇️ Abrir em nova aba</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal secundário: visualizador de PDF -->
        <div id="modalPdf" class="modal" aria-hidden="true">
            <div class="modal-content pdf-content">
                <button class="close-modal" id="closePdf">&times;</button>
                <iframe id="pdfFrame" src="" frameborder="0" style="width:100%;height:80vh;"></iframe>
            </div>
        </div>

    </div>
</div>

<script>
/* Chart */
const ctx = document.getElementById('graficoCandidaturas').getContext('2d');
const grafico = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Pendentes','Aceites','Rejeitadas'],
        datasets: [{
            label: 'Candidaturas',
            data: [<?=$pendentes?>, <?=$aceites?>, <?=$rejeitadas?>],
            backgroundColor: ['#6c757d','#0d6efd','#adb5bd']
        }]
    },
    options: { responsive:true }
});

/* Modal logic e AJAX */
const buttons = document.querySelectorAll('.btnAnalisar');
const modal = document.getElementById('modalAnalise');
const modalPdf = document.getElementById('modalPdf');

const closeModal = document.getElementById('closeModal');
const closePdf = document.getElementById('closePdf');

const setText = (id, text) => {
    const el = document.getElementById(id);
    if(el) el.textContent = text ?? '';
};

buttons.forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        fetch(`get_candidato.php?id=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(data => {
                if(data.error){
                    alert(data.error);
                    return;
                }
                // popular modal
                document.getElementById('m-nome').textContent = data.nome;
                setText('m-email', data.email);
                setText('m-telefone', data.telefone || '');
                setText('m-nif', data.nif || '');
                setText('m-tipoid', data.tipo_id || '');
                setText('m-rua', (data.rua || '') + (data.numero_porta ? ', ' + data.numero_porta : ''));
                setText('m-cidade', data.cidade || '');
                setText('m-distrito', data.distrito || '');
                setText('m-formacao', data.formacao ? data.formacao : '—');
                setText('m-categorias', data.categorias || '');
                setText('m-iban', data.iban || '');
                setText('m-banco', data.banco || '');
                setText('m-data', data.data_candidatura || '');
                // imagem
                const img = document.getElementById('m-foto');
                if(data.foto_perfil){
                    img.src = data.foto_perfil;
                    img.style.display = 'block';
                } else {
                    img.src = '';
                    img.style.display = 'none';
                }

                // Ver CV button
                const verBtn = document.getElementById('verCvBtn');
                const downloadCv = document.getElementById('downloadCv');

                if(data.formacao){
                    // apontar para visualizar.php?file=<basename>
                    const file = encodeURIComponent(data.formacao);
                    verBtn.style.display = 'inline-block';
                    downloadCv.style.display = 'inline-block';
                    verBtn.onclick = () => {
                        document.getElementById('pdfFrame').src = `visualizar.php?file=${file}`;
                        modalPdf.style.display = 'block';
                    };
                    downloadCv.href = `visualizar.php?file=${file}`;
                } else {
                    verBtn.style.display = 'none';
                    downloadCv.style.display = 'none';
                }

                modal.style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao carregar dados. Ver consola.');
            });
    });
});

closeModal.onclick = () => modal.style.display = 'none';
closePdf.onclick = () => {
    modalPdf.style.display = 'none';
    document.getElementById('pdfFrame').src = '';
};

window.onclick = function(e){
    if(e.target === modal) modal.style.display = 'none';
    if(e.target === modalPdf){
        modalPdf.style.display = 'none';
        document.getElementById('pdfFrame').src = '';
    }
};
</script>
</body>
</html>
