<?php
session_start();
if (!empty($_GET['_db_fresh'])) { $_SESSION = []; session_regenerate_id(true); }
require 'functions.php';
verificar_sessao();

$produtos   = ler_json('db_produtos.json')['produtos']   ?? [];
$entradas   = ler_json('db_entradas.json')['entradas']   ?? [];
$saidas     = ler_json('db_saidas.json')['saidas']       ?? [];

$total_estoque = 0; $baixo_estoque = 0;
foreach ($produtos as $p) {
    $total_estoque += ($p['estoque'] ?? 0) * ($p['preco_custo'] ?? 0);
    if (($p['estoque'] ?? 0) <= ($p['estoque_minimo'] ?? 0)) $baixo_estoque++;
}

$mes_atual = date('Y-m');
$receita_mes = 0; $custo_mes = 0;
foreach ($saidas as $s) {
    if (substr($s['data'] ?? '', 0, 7) === $mes_atual) $receita_mes += $s['total'] ?? 0;
}
foreach ($entradas as $e) {
    if (substr($e['data'] ?? '', 0, 7) === $mes_atual) $custo_mes += $e['total'] ?? 0;
}

// Ultimas movimentações
$ultimas_mov = [];
foreach ($entradas as $e) {
    $ultimas_mov[] = ['tipo'=>'Entrada','data'=>$e['data'],'descricao'=>($e['produto_nome']??'').' ('.$e['quantidade'].' un.)','valor'=>$e['total']??0,'cor'=>'entrada'];
}
foreach ($saidas as $s) {
    $ultimas_mov[] = ['tipo'=>'Saída','data'=>$s['data'],'descricao'=>($s['produto_nome']??'').' — '.$s['cliente'],'valor'=>$s['total']??0,'cor'=>'saida'];
}
usort($ultimas_mov, fn($a,$b) => strcmp($b['data'],$a['data']));
$ultimas_mov = array_slice($ultimas_mov, 0, 8);

// Gráfico por mês — últimos 6 meses
$por_mes = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $por_mes[$m] = ['ent' => 0, 'sai' => 0];
}
foreach ($entradas as $e) { $m=substr($e['data']??'',0,7); if(isset($por_mes[$m])) $por_mes[$m]['ent'] += $e['total']??0; }
foreach ($saidas  as $s) { $m=substr($s['data']??'',0,7); if(isset($por_mes[$m])) $por_mes[$m]['sai'] += $s['total']??0; }

$labels_mes = []; $data_ent = []; $data_sai = [];
foreach ($por_mes as $m => $v) {
    $p = explode('-',$m);
    $labels_mes[] = $p[1].'/'.substr($p[0],2);
    $data_ent[] = round($v['ent'], 2);
    $data_sai[] = round($v['sai'], 2);
}

// Gráfico categorias
$cat_qtd = [];
foreach ($produtos as $p) {
    $cat = $p['categoria'] ?? 'Sem categoria';
    $cat_qtd[$cat] = ($cat_qtd[$cat] ?? 0) + ($p['estoque'] ?? 0);
}
arsort($cat_qtd);
$cat_labels = array_keys($cat_qtd);
$cat_values = array_values($cat_qtd);

// Produtos criticos (baixo estoque)
$criticos = array_filter($produtos, fn($p) => ($p['estoque']??0) <= ($p['estoque_minimo']??0));
$criticos = array_slice(array_values($criticos), 0, 5);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — StockPro</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php require 'navbar.php'; ?>
<div class="page-wrapper">

  <!-- HEADER -->
  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="page-icon" style="background:var(--cyan-dim);">📊</div>
      <div>
        <h1 class="page-title">Dashboard</h1>
        <span class="page-sub">Visão geral · <?= date('d/m/Y') ?></span>
      </div>
    </div>
    <a href="relatorios.php" class="btn-secondary">📋 Ver Relatórios</a>
  </div>

  <!-- STAT CARDS -->
  <div class="cards-grid">
    <div class="card card-blue">
      <div class="card-icon-wrap">💰</div>
      <div class="card-info">
        <span class="card-label">Total em Estoque</span>
        <span class="card-value"><?= formatar_moeda($total_estoque) ?></span>
        <span class="card-trend"><?= count($produtos) ?> produtos cadastrados</span>
      </div>
    </div>
    <div class="card card-green">
      <div class="card-icon-wrap">📈</div>
      <div class="card-info">
        <span class="card-label">Receita do Mês</span>
        <span class="card-value"><?= formatar_moeda($receita_mes) ?></span>
        <span class="card-trend"><?= date('m/Y') ?></span>
      </div>
    </div>
    <div class="card card-red">
      <div class="card-icon-wrap">⚠️</div>
      <div class="card-info">
        <span class="card-label">Estoque Crítico</span>
        <span class="card-value"><?= $baixo_estoque ?> produto<?= $baixo_estoque!=1?'s':'' ?></span>
        <span class="card-trend">Abaixo do mínimo</span>
      </div>
    </div>
    <div class="card card-gray">
      <div class="card-icon-wrap">🔄</div>
      <div class="card-info">
        <span class="card-label">Movimentações</span>
        <span class="card-value"><?= count($entradas) + count($saidas) ?></span>
        <span class="card-trend"><?= count($entradas) ?> entradas · <?= count($saidas) ?> saídas</span>
      </div>
    </div>
  </div>

  <!-- CHARTS ROW -->
  <div class="dash-grid-3">
    <div class="section-box">
      <div class="section-bar">
        <h2 class="section-title">Fluxo Financeiro — 6 Meses</h2>
      </div>
      <div class="chart-container" style="height:230px">
        <canvas id="chartFluxo"></canvas>
      </div>
    </div>
    <div class="section-box">
      <div class="section-bar">
        <h2 class="section-title">Estoque por Categoria</h2>
      </div>
      <div class="chart-container" style="height:230px">
        <canvas id="chartCat"></canvas>
      </div>
    </div>
  </div>

  <!-- BOTTOM GRID -->
  <div class="dash-grid">

    <!-- ULTIMAS MOVIMENTACOES -->
    <div class="section-box">
      <div class="section-bar">
        <h2 class="section-title">Últimas Movimentações</h2>
        <a href="entradas.php" class="btn-ghost" style="font-size:.8rem">Ver todas →</a>
      </div>
      <?php if (empty($ultimas_mov)): ?>
      <div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">Nenhuma movimentação registrada.</div></div>
      <?php else: ?>
      <table class="tabela">
        <thead><tr><th>Tipo</th><th>Produto / Detalhe</th><th>Data</th><th style="text-align:right">Valor</th></tr></thead>
        <tbody>
          <?php foreach ($ultimas_mov as $m): ?>
          <tr>
            <td><span class="badge badge-mov-<?= $m['cor'] ?>"><?= $m['tipo'] ?></span></td>
            <td style="color:var(--text-primary)"><?= htmlspecialchars($m['descricao']) ?></td>
            <td><?= formatar_data($m['data']) ?></td>
            <td style="text-align:right;color:var(--<?= $m['cor']==='entrada'?'green':'red' ?>);font-weight:700"><?= formatar_moeda($m['valor']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- ALERTAS CRITICOS -->
    <div class="section-box">
      <div class="section-bar">
        <h2 class="section-title">⚠️ Alertas Críticos</h2>
        <a href="produtos.php" class="btn-ghost" style="font-size:.8rem">Gerenciar →</a>
      </div>
      <?php if (empty($criticos)): ?>
      <div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text">Todos os estoques estão OK!</div></div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px">
        <?php foreach ($criticos as $p):
          $pct = ($p['estoque_minimo'] > 0) ? min(100, round(($p['estoque'] / $p['estoque_minimo']) * 100)) : 0;
        ?>
        <div style="background:var(--bg-elevated);border:1px solid rgba(244,63,94,.15);border-radius:var(--radius-md);padding:14px">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <span style="font-size:.88rem;font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($p['nome']) ?></span>
            <span class="badge-alert"><?= (int)$p['estoque'] ?> / <?= (int)$p['estoque_minimo'] ?></span>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $pct < 30 ? 'var(--red)' : 'var(--amber)' ?>"></div>
          </div>
          <span style="font-size:.7rem;color:var(--text-muted);margin-top:4px;display:block"><?= htmlspecialchars($p['categoria'] ?? '') ?> · Cód: <?= htmlspecialchars($p['codigo']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

  </div>

</div>
<footer class="rodape">StockPro · Desenvolvido por Arthur</footer>

<script>
const CYAN   = '#00d4ff';
const GREEN  = '#10b981';
const RED    = '#f43f5e';
const AMBER  = '#f59e0b';
const PURPLE = '#8b5cf6';
const TEXT   = '#8892aa';
const GRID   = 'rgba(255,255,255,0.05)';

Chart.defaults.color = TEXT;
Chart.defaults.font.family = "'DM Sans', sans-serif";

// Fluxo Financeiro
new Chart(document.getElementById('chartFluxo'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels_mes) ?>,
    datasets: [
      {
        label: 'Entradas',
        data: <?= json_encode($data_ent) ?>,
        backgroundColor: 'rgba(16,185,129,0.25)',
        borderColor: GREEN,
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false,
      },
      {
        label: 'Saídas',
        data: <?= json_encode($data_sai) ?>,
        backgroundColor: 'rgba(0,212,255,0.2)',
        borderColor: CYAN,
        borderWidth: 2,
        borderRadius: 6,
        borderSkipped: false,
      }
    ]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 } } },
      tooltip: {
        backgroundColor: '#1a1e2e', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1,
        padding: 12, titleFont: { size: 13, weight: 700 }, bodyFont: { size: 12 },
        callbacks: { label: ctx => ' R$ ' + ctx.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits:2}) }
      }
    },
    scales: {
      x: { grid: { color: GRID }, ticks: { font: { size: 11 } } },
      y: { grid: { color: GRID }, ticks: { font: { size: 11 }, callback: v => 'R$' + (v/1000).toFixed(0)+'k' } }
    }
  }
});

// Categorias
const catColors = [CYAN, GREEN, AMBER, PURPLE, RED, '#06b6d4', '#ec4899'];
new Chart(document.getElementById('chartCat'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($cat_labels) ?>,
    datasets: [{
      data: <?= json_encode($cat_values) ?>,
      backgroundColor: catColors.map(c => c + '55'),
      borderColor: catColors,
      borderWidth: 2,
      hoverOffset: 8,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 12, font: { size: 11 } } },
      tooltip: {
        backgroundColor: '#1a1e2e', borderColor: 'rgba(255,255,255,.1)', borderWidth: 1, padding: 12,
        callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' un.' }
      }
    }
  }
});
</script>
</body>
</html>
