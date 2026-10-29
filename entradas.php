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
        $forn_id=(int)($_POST['fornecedor_id']??0); $prod_id=(int)($_POST['produto_id']??0);
        $qtd=(int)($_POST['quantidade']??0); $preco=(float)str_replace(',','.',($_POST['preco_unitario']??0));
        if (!$nf||!$data||!$forn_id||!$prod_id||$qtd<=0||$preco<=0) { $erro='Preencha todos os campos corretamente.'; }
        else {
            $db_forn=ler_json('db_fornecedores.json'); $fornecedor=null;
            foreach($db_forn['fornecedores']??[] as $f){if($f['id']==$forn_id){$fornecedor=$f;break;}}
            if (!$fornecedor) { $erro='Fornecedor inválido.'; }
            else {
                $total=round($qtd*$preco,2);
                $erro = registrarMovimento('entradas', [
                    'nota_fiscal' => $nf, 'data' => $data, 'fornecedor_id' => $forn_id,
                    'produto_id' => $prod_id, 'quantidade' => $qtd,
                    'preco_unitario' => $preco, 'total' => $total,
                ]) ?? '';
                if (!$erro) $msg='Entrada registrada com sucesso!';
            }
        }
    }
}

$db_ent=ler_json('db_entradas.json'); $entradas=$db_ent['entradas']??[];
$db_prod=ler_json('db_produtos.json'); $produtos=$db_prod['produtos']??[];
$db_forn=ler_json('db_fornecedores.json'); $fornecedores=$db_forn['fornecedores']??[];

$filtro_forn=trim($_GET['fornecedor']??'');
$filtro_data_ini=trim($_GET['data_ini']??'');
$filtro_data_fim=trim($_GET['data_fim']??'');

$lista=array_filter($entradas,function($e)use($filtro_forn,$filtro_data_ini,$filtro_data_fim){
    $ok=true;
    if($filtro_forn) $ok=$ok&&str_contains(mb_strtolower($e['fornecedor_nome']??''),mb_strtolower($filtro_forn));
    if($filtro_data_ini) $ok=$ok&&($e['data']??'')>=$filtro_data_ini;
    if($filtro_data_fim) $ok=$ok&&($e['data']??'')<=$filtro_data_fim;
    return $ok;
});
usort($lista,fn($a,$b)=>strcmp($b['data'],$a['data']));
$total_ent=array_sum(array_column($entradas,'total'));
$pag = paginar(array_values($lista));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Entradas — StockPro</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'navbar.php'; ?>
<div class="page-wrapper">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="page-icon" style="background:var(--green-dim)">📥</div>
      <div>
        <h1 class="page-title">Entradas</h1>
        <span class="page-sub"><?= count($entradas) ?> registros · Total <?= formatar_moeda($total_ent) ?></span>
      </div>
    </div>
    <?php if($pode_editar): ?>
    <button class="btn-primary" onclick="document.getElementById('modalEntrada').style.display='flex'">+ Nova Entrada</button>
    <?php endif; ?>
  </div>

  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($erro): ?><div class="alert alert-err">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif; ?>
  <?php if(!$pode_editar): ?>
  <div class="alert" style="background:var(--amber-dim);border:1px solid rgba(245,158,11,.25);color:var(--amber)">🔒 Modo somente leitura.</div>
  <?php endif; ?>

  <div class="section-box">
    <div class="section-bar">
      <h2 class="section-title">Histórico de Entradas</h2>
      <form method="GET" class="filtro-form" style="margin:0">
        <input type="text" name="fornecedor" class="input-filtro" placeholder="🔍 Fornecedor..." value="<?= htmlspecialchars($filtro_forn) ?>">
        <input type="date" name="data_ini" class="input-filtro" value="<?= htmlspecialchars($filtro_data_ini) ?>" title="Data inicial">
        <input type="date" name="data_fim" class="input-filtro" value="<?= htmlspecialchars($filtro_data_fim) ?>" title="Data final">
        <button type="submit" class="btn-secondary">Filtrar</button>
        <?php if($filtro_forn||$filtro_data_ini||$filtro_data_fim): ?><a href="entradas.php" class="btn-ghost">Limpar</a><?php endif; ?>
      </form>
    </div>

    <table class="tabela">
      <thead>
        <tr><th>NF</th><th>Data</th><th>Fornecedor</th><th>Produto</th><th>Qtd</th><th>Preço Unit.</th><th style="text-align:right">Total</th></tr>
      </thead>
      <tbody>
        <?php if(empty($lista)): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">Nenhuma entrada encontrada.</div></div></td></tr>
        <?php else: ?>
        <?php foreach($pag['itens'] as $e): ?>
        <tr>
          <td style="font-family:monospace;font-size:.83rem;color:var(--cyan)"><?= htmlspecialchars($e['nota_fiscal']??'') ?></td>
          <td><?= formatar_data($e['data']??'') ?></td>
          <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($e['fornecedor_nome']??'') ?></td>
          <td><?= htmlspecialchars($e['produto_nome']??'') ?></td>
          <td><span style="background:var(--green-dim);color:var(--green);padding:2px 8px;border-radius:20px;font-size:.8rem;font-weight:700"><?= (int)($e['quantidade']??0) ?> un.</span></td>
          <td><?= formatar_moeda($e['preco_unitario']??0) ?></td>
          <td style="text-align:right;color:var(--green);font-weight:700"><?= formatar_moeda($e['total']??0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
    <?= controlesPaginacao($pag) ?>
  </div>
</div>

<?php if($pode_editar): ?>
<div class="modal-overlay" id="modalEntrada" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3>Nova Entrada</h3>
      <button class="modal-close" onclick="document.getElementById('modalEntrada').style.display='none'">✕</button>
    </div>
    <form method="POST">
            <?= csrf_campo() ?>
      <div class="form-grid">
        <div class="form-group"><label>Nota Fiscal *</label><input type="text" name="nota_fiscal" required class="input-field" placeholder="NF-000"></div>
        <div class="form-group"><label>Data *</label><input type="date" name="data" required class="input-field" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label>Fornecedor *</label>
          <select name="fornecedor_id" required class="input-field">
            <option value="">Selecione...</option>
            <?php foreach($fornecedores as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Produto *</label>
          <select name="produto_id" required class="input-field">
            <option value="">Selecione...</option>
            <?php foreach($produtos as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= $p['codigo'] ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Quantidade *</label><input type="number" name="quantidade" required min="1" class="input-field" value="1"></div>
        <div class="form-group"><label>Preço Unitário (R$) *</label><input type="number" name="preco_unitario" required step="0.01" min="0.01" class="input-field" value="0.00"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="document.getElementById('modalEntrada').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-primary">Registrar Entrada</button>
      </div>
    </form>
  </div>
</div>
<script>window.addEventListener('click',e=>{if(e.target.id==='modalEntrada')e.target.style.display='none';});</script>
<?php endif; ?>
<footer class="rodape">StockPro · Desenvolvido por Arthur</footer>
</body>
</html>
