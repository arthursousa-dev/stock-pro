<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();

$pode_editar = pode_editar();
$msg=''; $erro='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!$pode_editar) { $erro='Você não tem permissão.'; }
    elseif (!csrf_valido($_POST['csrf_token'] ?? null)) { $erro='Sessão expirada. Recarregue a página e tente novamente.'; }
    else {
        $nf=trim($_POST['nota_fiscal']??''); $data=trim($_POST['data']??'');
        $cliente=trim($_POST['cliente']??''); $prod_id=(int)($_POST['produto_id']??0);
        $qtd=(int)($_POST['quantidade']??0); $preco=(float)str_replace(',','.',($_POST['preco_unitario']??0));
        if (!$nf||!$data||!$cliente||!$prod_id||$qtd<=0||$preco<=0) { $erro='Preencha todos os campos corretamente.'; }
        else {
            $db_prod=ler_json('db_produtos.json'); $produto=null;
            foreach($db_prod['produtos']??[] as $p){if($p['id']==$prod_id){$produto=$p;break;}}
            if (!$produto) { $erro='Produto não encontrado.'; }
            elseif(($produto['estoque']??0)<$qtd) { $erro='Estoque insuficiente! Disponível: '.(int)($produto['estoque']??0).' un.'; }
            else {
                $db_sai=ler_json('db_saidas.json'); $saidas=$db_sai['saidas']??[]; $next_id=$db_sai['next_id']??1;
                $total=round($qtd*$preco,2);
                $saidas[]=['id'=>$next_id,'nota_fiscal'=>$nf,'data'=>$data,'cliente'=>$cliente,'produto_id'=>$prod_id,'produto_nome'=>$produto['nome'],'quantidade'=>$qtd,'preco_unitario'=>$preco,'total'=>$total];
                $db_sai['saidas']=$saidas; $db_sai['next_id']=$next_id+1;
                salvar_json('db_saidas.json',$db_sai);
                foreach($db_prod['produtos'] as &$p){if($p['id']==$prod_id){$p['estoque']-=$qtd;break;}}
                salvar_json('db_produtos.json',$db_prod);
                $msg='Saída registrada com sucesso!';
            }
        }
    }
}

$db_sai=ler_json('db_saidas.json'); $saidas=$db_sai['saidas']??[];
$db_prod=ler_json('db_produtos.json'); $produtos=$db_prod['produtos']??[];

$filtro_cli=trim($_GET['cliente']??'');
$filtro_data_ini=trim($_GET['data_ini']??'');
$filtro_data_fim=trim($_GET['data_fim']??'');

$lista=array_filter($saidas,function($s)use($filtro_cli,$filtro_data_ini,$filtro_data_fim){
    $ok=true;
    if($filtro_cli) $ok=$ok&&str_contains(mb_strtolower($s['cliente']??''),mb_strtolower($filtro_cli));
    if($filtro_data_ini) $ok=$ok&&($s['data']??'')>=$filtro_data_ini;
    if($filtro_data_fim) $ok=$ok&&($s['data']??'')<=$filtro_data_fim;
    return $ok;
});
usort($lista,fn($a,$b)=>strcmp($b['data'],$a['data']));
$total_sai=array_sum(array_column($saidas,'total'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Saídas — StockPro</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'navbar.php'; ?>
<div class="page-wrapper">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="page-icon" style="background:var(--cyan-dim)">📤</div>
      <div>
        <h1 class="page-title">Saídas</h1>
        <span class="page-sub"><?= count($saidas) ?> registros · Total <?= formatar_moeda($total_sai) ?></span>
      </div>
    </div>
    <?php if($pode_editar): ?>
    <button class="btn-primary" onclick="document.getElementById('modalSaida').style.display='flex'">+ Nova Saída</button>
    <?php endif; ?>
  </div>

  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($erro): ?><div class="alert alert-err">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <?php if(!$pode_editar): ?>
  <div class="alert" style="background:var(--amber-dim);border:1px solid rgba(245,158,11,.25);color:var(--amber)">🔒 Modo somente leitura.</div>
  <?php endif; ?>

  <div class="section-box">
    <div class="section-bar">
      <h2 class="section-title">Histórico de Saídas</h2>
      <form method="GET" class="filtro-form" style="margin:0">
        <input type="text" name="cliente" class="input-filtro" placeholder="🔍 Cliente..." value="<?= htmlspecialchars($filtro_cli) ?>">
        <input type="date" name="data_ini" class="input-filtro" value="<?= htmlspecialchars($filtro_data_ini) ?>">
        <input type="date" name="data_fim" class="input-filtro" value="<?= htmlspecialchars($filtro_data_fim) ?>">
        <button type="submit" class="btn-secondary">Filtrar</button>
        <?php if($filtro_cli||$filtro_data_ini||$filtro_data_fim): ?><a href="saidas.php" class="btn-ghost">Limpar</a><?php endif; ?>
      </form>
    </div>

    <table class="tabela">
      <thead>
        <tr><th>NF</th><th>Data</th><th>Cliente</th><th>Produto</th><th>Qtd</th><th>Preço Unit.</th><th style="text-align:right">Total</th></tr>
      </thead>
      <tbody>
        <?php if(empty($lista)): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">Nenhuma saída encontrada.</div></div></td></tr>
        <?php else: ?>
        <?php foreach($lista as $s): ?>
        <tr>
          <td style="font-family:monospace;font-size:.83rem;color:var(--amber)"><?= htmlspecialchars($s['nota_fiscal']??'') ?></td>
          <td><?= formatar_data($s['data']??'') ?></td>
          <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($s['cliente']??'') ?></td>
          <td><?= htmlspecialchars($s['produto_nome']??'') ?></td>
          <td><span style="background:var(--cyan-dim);color:var(--cyan);padding:2px 8px;border-radius:20px;font-size:.8rem;font-weight:700"><?= (int)($s['quantidade']??0) ?> un.</span></td>
          <td><?= formatar_moeda($s['preco_unitario']??0) ?></td>
          <td style="text-align:right;color:var(--cyan);font-weight:700"><?= formatar_moeda($s['total']??0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($pode_editar): ?>
<div class="modal-overlay" id="modalSaida" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3>Nova Saída</h3>
      <button class="modal-close" onclick="document.getElementById('modalSaida').style.display='none'">✕</button>
    </div>
    <form method="POST">
            <?= csrf_campo() ?>
      <div class="form-grid">
        <div class="form-group"><label>Nota Fiscal *</label><input type="text" name="nota_fiscal" required class="input-field" placeholder="NS-000"></div>
        <div class="form-group"><label>Data *</label><input type="date" name="data" required class="input-field" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label>Cliente *</label><input type="text" name="cliente" required class="input-field" placeholder="Nome do cliente"></div>
        <div class="form-group"><label>Produto *</label>
          <select name="produto_id" required class="input-field">
            <option value="">Selecione...</option>
            <?php foreach($produtos as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> — Estq: <?= (int)($p['estoque']??0) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Quantidade *</label><input type="number" name="quantidade" required min="1" class="input-field" value="1"></div>
        <div class="form-group"><label>Preço Unitário (R$) *</label><input type="number" name="preco_unitario" required step="0.01" min="0.01" class="input-field" value="0.00"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="document.getElementById('modalSaida').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-primary">Registrar Saída</button>
      </div>
    </form>
  </div>
</div>
<script>window.addEventListener('click',e=>{if(e.target.id==='modalSaida')e.target.style.display='none';});</script>
<?php endif; ?>
<footer class="rodape">StockPro · Desenvolvido por Arthur</footer>
</body>
</html>
