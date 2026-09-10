<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();

$produtos     = ler_json('db_produtos.json')['produtos']     ?? [];
$entradas     = ler_json('db_entradas.json')['entradas']     ?? [];
$saidas       = ler_json('db_saidas.json')['saidas']         ?? [];
$fornecedores = ler_json('db_fornecedores.json')['fornecedores'] ?? [];

$aba = $_GET['aba'] ?? 'estoque';

// Exportar TXT
if (isset($_GET['exportar'])) {
    $tipo=$_GET['exportar']; $linhas=[];
    $linhas[]='=== STOCKPRO — RELATÓRIO '.strtoupper($tipo).' ===';
    $linhas[]='Gerado em: '.date('d/m/Y H:i:s');
    $linhas[]=str_repeat('=',50);
    if ($tipo==='estoque') {
        $linhas[]=sprintf('%-10s %-25s %-15s %8s %10s %10s','Código','Nome','Categoria','Estoque','Custo','Venda');
        $linhas[]=str_repeat('-',80);
        foreach($produtos as $p){$linhas[]=sprintf('%-10s %-25s %-15s %8d %10s %10s',substr($p['codigo']??'',0,10),substr($p['nome']??'',0,25),substr($p['categoria']??'',0,15),(int)($p['estoque']??0),'R$'.number_format($p['preco_custo']??0,2,',','.'),'R$'.number_format($p['preco_venda']??0,2,',','.'));}
    } elseif ($tipo==='movimentacao') {
        $linhas[]='--- ENTRADAS ---';
        foreach($entradas as $e){$linhas[]=sprintf('%-10s %-12s %-20s %-20s %6d %12s',substr($e['nota_fiscal']??'',0,10),formatar_data($e['data']??''),substr($e['fornecedor_nome']??'',0,20),substr($e['produto_nome']??'',0,20),(int)($e['quantidade']??0),'R$'.number_format($e['total']??0,2,',','.'));}
        $linhas[]=''; $linhas[]='--- SAÍDAS ---';
        foreach($saidas as $s){$linhas[]=sprintf('%-10s %-12s %-20s %-20s %6d %12s',substr($s['nota_fiscal']??'',0,10),formatar_data($s['data']??''),substr($s['cliente']??'',0,20),substr($s['produto_nome']??'',0,20),(int)($s['quantidade']??0),'R$'.number_format($s['total']??0,2,',','.'));}
    } elseif ($tipo==='financeiro') {
        $te=array_sum(array_column($entradas,'total')); $ts=array_sum(array_column($saidas,'total'));
        $linhas[]='Total investido (entradas): R$'.number_format($te,2,',','.');
        $linhas[]='Total receita  (saídas):   R$'.number_format($ts,2,',','.');
        $linhas[]='Lucro bruto estimado:       R$'.number_format($ts-$te,2,',','.');
    }
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_'.$tipo.'_'.date('Ymd').'.txt"');
    echo implode("\n",$linhas); exit;
}

// Calcs
$total_ent_val=array_sum(array_column($entradas,'total'));
$total_sai_val=array_sum(array_column($saidas,'total'));
$lucro_bruto=$total_sai_val-$total_ent_val;

// Por mês (últimos 6 meses)
$por_mes=[];
for($i=5;$i>=0;$i--){$m=date('Y-m',strtotime("-$i months"));$por_mes[$m]=['ent'=>0,'sai'=>0];}
foreach($entradas as $e){$m=substr($e['data']??'',0,7);if(isset($por_mes[$m]))$por_mes[$m]['ent']+=$e['total'];}
foreach($saidas  as $s){$m=substr($s['data']??'',0,7);if(isset($por_mes[$m]))$por_mes[$m]['sai']+=$s['total'];}

$chart_labels=[]; $chart_ent=[]; $chart_sai=[];
foreach($por_mes as $m=>$v){
    $p=explode('-',$m); $chart_labels[]=$p[1].'/'.substr($p[0],2);
    $chart_ent[]=round($v['ent'],2); $chart_sai[]=round($v['sai'],2);
}

// Todos os meses para tabela
$todos_meses=[];
foreach($entradas as $e){$m=substr($e['data']??'',0,7);$todos_meses[$m]['ent']=($todos_meses[$m]['ent']??0)+$e['total'];}
foreach($saidas  as $s){$m=substr($s['data']??'',0,7);$todos_meses[$m]['sai']=($todos_meses[$m]['sai']??0)+$s['total'];}
ksort($todos_meses);

// Top produtos mais vendidos
$prod_vendas=[];
foreach($saidas as $s){$n=$s['produto_nome']??'?';$prod_vendas[$n]=($prod_vendas[$n]??0)+($s['total']??0);}
arsort($prod_vendas); $top_produtos=array_slice($prod_vendas,0,5,true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatórios — StockPro</title>
<link rel="stylesheet" href="style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php require 'navbar.php'; ?>
<div class="page-wrapper">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="page-icon" style="background:var(--amber-dim)">📋</div>
      <div>
        <h1 class="page-title">Relatórios</h1>
        <span class="page-sub">Análise completa · <?= date('d/m/Y') ?></span>
      </div>
    </div>
    <?php
      $tipo_exp = ['estoque'=>'estoque','movimentacao'=>'movimentacao','financeiro'=>'financeiro'];
      $t_exp = $tipo_exp[$aba] ?? 'estoque';
    ?>
    <a href="relatorios.php?aba=<?= $aba ?>&exportar=<?= $t_exp ?>" class="btn-secondary">⬇️ Exportar TXT</a>
  </div>

  <div class="abas">
    <a href="relatorios.php?aba=estoque"      class="aba <?= $aba==='estoque'?'aba-ativa':'' ?>">📦 Estoque</a>
    <a href="relatorios.php?aba=movimentacao" class="aba <?= $aba==='movimentacao'?'aba-ativa':'' ?>">🔄 Movimentação</a>
    <a href="relatorios.php?aba=financeiro"   class="aba <?= $aba==='financeiro'?'aba-ativa':'' ?>">💰 Financeiro</a>
  </div>

  <?php if ($aba==='estoque'): ?>

  <div class="section-box">
    <div class="section-bar">
      <h2 class="section-title">Relatório de Estoque</h2>
      <span style="font-size:.8rem;color:var(--text-muted)"><?= count($produtos) ?> produtos</span>
    </div>
    <table class="tabela">
      <thead><tr><th>Código</th><th>Nome</th><th>Categoria</th><th>Estoque</th><th>Mín.</th><th>Custo Unit.</th><th>Valor Total</th><th>Status</th></tr></thead>
      <tbody>
        <?php if(empty($produtos)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">📦</div><div class="empty-state-text">Sem produtos.</div></div></td></tr>
        <?php else: ?>
        <?php foreach($produtos as $p):
          $baixo=($p['estoque']??0)<=($p['estoque_minimo']??0);
          $val_total=($p['estoque']??0)*($p['preco_custo']??0);
        ?>
        <tr class="<?= $baixo?'row-alert':'' ?>">
          <td style="font-family:monospace;font-size:.83rem;color:var(--cyan)"><?= htmlspecialchars($p['codigo']??'') ?></td>
          <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($p['nome']??'') ?></td>
          <td><span style="background:var(--bg-elevated);border:1px solid var(--border);padding:2px 9px;border-radius:20px;font-size:.73rem;color:var(--text-secondary)"><?= htmlspecialchars($p['categoria']??'—') ?></span></td>
          <td style="font-weight:700;color:<?= $baixo?'var(--red)':'var(--text-primary)' ?>"><?= (int)($p['estoque']??0) ?></td>
          <td style="color:var(--text-muted)"><?= (int)($p['estoque_minimo']??0) ?></td>
          <td><?= formatar_moeda($p['preco_custo']??0) ?></td>
          <td style="font-weight:700;color:var(--cyan)"><?= formatar_moeda($val_total) ?></td>
          <td><?= $baixo?'<span class="badge-alert">⚠️ Baixo</span>':'<span class="badge-ok">✅ OK</span>' ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php elseif ($aba==='movimentacao'): ?>

  <div class="dash-grid">
    <div class="section-box">
      <div class="section-bar">
        <h2 class="section-title">📥 Entradas</h2>
        <span style="font-size:.82rem;color:var(--green);font-weight:700"><?= formatar_moeda($total_ent_val) ?></span>
      </div>
      <table class="tabela">
        <thead><tr><th>NF</th><th>Data</th><th>Fornecedor</th><th>Produto</th><th>Qtd</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php if(empty($entradas)): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">Sem entradas.</div></div></td></tr>
          <?php else: ?>
          <?php foreach($entradas as $e): ?>
          <tr>
            <td style="font-family:monospace;font-size:.8rem;color:var(--cyan)"><?= htmlspecialchars($e['nota_fiscal']??'') ?></td>
            <td><?= formatar_data($e['data']??'') ?></td>
            <td><?= htmlspecialchars($e['fornecedor_nome']??'') ?></td>
            <td><?= htmlspecialchars($e['produto_nome']??'') ?></td>
            <td><?= (int)($e['quantidade']??0) ?></td>
            <td style="text-align:right;color:var(--green);font-weight:700"><?= formatar_moeda($e['total']??0) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="section-box">
      <div class="section-bar">
        <h2 class="section-title">📤 Saídas</h2>
        <span style="font-size:.82rem;color:var(--cyan);font-weight:700"><?= formatar_moeda($total_sai_val) ?></span>
      </div>
      <table class="tabela">
        <thead><tr><th>NF</th><th>Data</th><th>Cliente</th><th>Produto</th><th>Qtd</th><th style="text-align:right">Total</th></tr></thead>
        <tbody>
          <?php if(empty($saidas)): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">Sem saídas.</div></div></td></tr>
          <?php else: ?>
          <?php foreach($saidas as $s): ?>
          <tr>
            <td style="font-family:monospace;font-size:.8rem;color:var(--amber)"><?= htmlspecialchars($s['nota_fiscal']??'') ?></td>
            <td><?= formatar_data($s['data']??'') ?></td>
            <td><?= htmlspecialchars($s['cliente']??'') ?></td>
            <td><?= htmlspecialchars($s['produto_nome']??'') ?></td>
            <td><?= (int)($s['quantidade']??0) ?></td>
            <td style="text-align:right;color:var(--cyan);font-weight:700"><?= formatar_moeda($s['total']??0) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php elseif ($aba==='financeiro'): ?>

  <div class="cards-grid" style="margin-bottom:24px">
    <div class="card card-red">
      <div class="card-icon-wrap">📥</div>
      <div class="card-info">
        <span class="card-label">Total Investido</span>
        <span class="card-value"><?= formatar_moeda($total_ent_val) ?></span>
        <span class="card-trend">Custo das entradas</span>
      </div>
    </div>
    <div class="card card-blue">
      <div class="card-icon-wrap">📤</div>
      <div class="card-info">
        <span class="card-label">Total Receita</span>
        <span class="card-value"><?= formatar_moeda($total_sai_val) ?></span>
        <span class="card-trend">Receita das saídas</span>
      </div>
    </div>
    <div class="card <?= $lucro_bruto>=0?'card-green':'card-red' ?>">
      <div class="card-icon-wrap">💰</div>
      <div class="card-info">
        <span class="card-label">Lucro Bruto</span>
        <span class="card-value"><?= formatar_moeda($lucro_bruto) ?></span>
        <span class="card-trend"><?= $lucro_bruto>=0?'Positivo ↑':'Negativo ↓' ?></span>
      </div>
    </div>
    <div class="card card-amber">
      <div class="card-icon-wrap">📊</div>
      <div class="card-info">
        <span class="card-label">Margem Bruta</span>
        <span class="card-value"><?= $total_ent_val>0?round(($lucro_bruto/$total_ent_val)*100,1):0 ?>%</span>
        <span class="card-trend">Sobre o custo total</span>
      </div>
    </div>
  </div>

  <div class="dash-grid">
    <!-- GRÁFICO -->
    <div class="section-box">
      <h2 class="section-title">Fluxo Financeiro — Últimos 6 Meses</h2>
      <div style="height:240px"><canvas id="chartFinanceiro"></canvas></div>
    </div>

    <!-- TOP PRODUTOS -->
    <div class="section-box">
      <h2 class="section-title">Top Produtos por Receita</h2>
      <?php if(empty($top_produtos)): ?>
      <div class="empty-state"><div class="empty-state-icon">📊</div><div class="empty-state-text">Sem dados.</div></div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php $max_val=max($top_produtos); $rank=1; foreach($top_produtos as $nome=>$val):
          $pct2=round(($val/$max_val)*100); ?>
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
            <span style="font-size:.85rem;font-weight:600;color:var(--text-primary)"><?= $rank ?>. <?= htmlspecialchars($nome) ?></span>
            <span style="font-size:.82rem;font-weight:700;color:var(--cyan)"><?= formatar_moeda($val) ?></span>
          </div>
          <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $pct2 ?>%"></div></div>
        </div>
        <?php $rank++; endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TABELA POR MÊS -->
  <div class="section-box">
    <h2 class="section-title">Resultado por Mês</h2>
    <table class="tabela">
      <thead><tr><th>Mês</th><th style="text-align:right">Entradas</th><th style="text-align:right">Saídas</th><th style="text-align:right">Saldo</th></tr></thead>
      <tbody>
        <?php if(empty($todos_meses)): ?>
        <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📊</div><div class="empty-state-text">Sem dados financeiros.</div></div></td></tr>
        <?php else: ?>
        <?php foreach($todos_meses as $m=>$v):
          $p2=explode('-',$m); $label=(count($p2)===2)?$p2[1].'/'.$p2[0]:$m;
          $ent=$v['ent']??0; $sai=$v['sai']??0; $saldo=$sai-$ent;
        ?>
        <tr>
          <td style="font-weight:600;color:var(--text-primary)"><?= htmlspecialchars($label) ?></td>
          <td style="text-align:right;color:var(--green);font-weight:700"><?= formatar_moeda($ent) ?></td>
          <td style="text-align:right;color:var(--cyan);font-weight:700"><?= formatar_moeda($sai) ?></td>
          <td style="text-align:right" class="<?= $saldo>=0?'td-pos':'td-neg' ?>"><?= formatar_moeda($saldo) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <script>
  Chart.defaults.color = '#8892aa';
  Chart.defaults.font.family = "'DM Sans', sans-serif";
  const CYAN='#00d4ff', GREEN='#10b981', GRID='rgba(255,255,255,0.05)';
  new Chart(document.getElementById('chartFinanceiro'),{
    type:'line',
    data:{
      labels:<?= json_encode($chart_labels) ?>,
      datasets:[
        {label:'Entradas',data:<?= json_encode($chart_ent) ?>,borderColor:GREEN,backgroundColor:'rgba(16,185,129,0.1)',borderWidth:2,pointBackgroundColor:GREEN,pointRadius:4,tension:.4,fill:true},
        {label:'Saídas',  data:<?= json_encode($chart_sai) ?>,borderColor:CYAN,  backgroundColor:'rgba(0,212,255,0.08)', borderWidth:2,pointBackgroundColor:CYAN,  pointRadius:4,tension:.4,fill:true}
      ]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{
        legend:{position:'top',labels:{usePointStyle:true,pointStyle:'circle',padding:16}},
        tooltip:{backgroundColor:'#1a1e2e',borderColor:'rgba(255,255,255,.1)',borderWidth:1,padding:12,callbacks:{label:ctx=>' R$ '+ctx.parsed.y.toLocaleString('pt-BR',{minimumFractionDigits:2})}}
      },
      scales:{
        x:{grid:{color:GRID}},
        y:{grid:{color:GRID},ticks:{callback:v=>'R$'+(v/1000).toFixed(0)+'k'}}
      }
    }
  });
  </script>

  <?php endif; ?>

</div>
<footer class="rodape">StockPro · Desenvolvido por Arthur</footer>
</body>
</html>
