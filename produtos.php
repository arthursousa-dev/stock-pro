<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
verificar_sessao();

$pode_editar = pode_editar();
$msg = ''; $erro = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!$pode_editar) {
        $erro = 'Você não tem permissão para realizar esta ação.';
    } elseif (!csrf_valido($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';
        $db = ler_json('db_produtos.json');
        $produtos = $db['produtos'] ?? [];
        if ($acao === 'salvar') {
            $id=$p_id=(int)($_POST['id']??0);
            $codigo=trim($_POST['codigo']??''); $nome=trim($_POST['nome']??'');
            $cat=trim($_POST['categoria']??'');
            $custo=(float)str_replace(',','.',($_POST['preco_custo']??0));
            $venda=(float)str_replace(',','.',($_POST['preco_venda']??0));
            $estq=(int)($_POST['estoque']??0); $min=(int)($_POST['estoque_minimo']??0);
            if (!$codigo || !$nome) { $erro = 'Código e nome são obrigatórios.'; }
            else {
                if ($id > 0) {
                    foreach ($produtos as &$p) {
                        if ($p['id']==$id) { $p['codigo']=$codigo;$p['nome']=$nome;$p['categoria']=$cat;$p['preco_custo']=$custo;$p['preco_venda']=$venda;$p['estoque']=$estq;$p['estoque_minimo']=$min; break; }
                    }
                    $db['produtos']=$produtos;
                    salvar_json('db_produtos.json', $db);
                    $msg = 'Produto atualizado!';
                } else {
                    inserirRegistro('produtos', [
                        'codigo' => $codigo, 'nome' => $nome, 'categoria' => $cat,
                        'preco_custo' => $custo, 'preco_venda' => $venda,
                        'estoque' => $estq, 'estoque_minimo' => $min,
                    ]);
                    $msg = 'Produto cadastrado!';
                }
            }
        } elseif ($acao === 'excluir' && verificar_permissao(['admin'])) {
            $id=(int)($_POST['id']??0);
            $db['produtos']=array_values(array_filter($produtos, fn($p)=>$p['id']!=$id));
            salvar_json('db_produtos.json', $db); $msg = 'Produto excluído.';
        }
    }
}

$db_prod = ler_json('db_produtos.json');
$produtos = $db_prod['produtos'] ?? [];
$busca = trim($_GET['busca']??''); $filtro_cat = trim($_GET['categoria']??'');
$categorias = array_unique(array_column($produtos,'categoria')); sort($categorias);
$lista = array_filter($produtos, function($p) use ($busca,$filtro_cat) {
    $ok=true;
    if ($busca) { $b=mb_strtolower($busca); $ok=str_contains(mb_strtolower($p['nome']??''),$b)||str_contains(mb_strtolower($p['codigo']??''),$b); }
    if ($filtro_cat) $ok=$ok&&($p['categoria']??'')===$filtro_cat;
    return $ok;
});
$total_val = array_sum(array_map(fn($p)=>($p['estoque']??0)*($p['preco_custo']??0), $produtos));
$baixo_count = count(array_filter($produtos, fn($p)=>($p['estoque']??0)<=($p['estoque_minimo']??0)));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Produtos — StockPro</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'navbar.php'; ?>
<div class="page-wrapper">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="page-icon" style="background:var(--cyan-dim)">📦</div>
      <div>
        <h1 class="page-title">Produtos</h1>
        <span class="page-sub"><?= count($produtos) ?> itens · Valor total <?= formatar_moeda($total_val) ?></span>
      </div>
    </div>
    <?php if($pode_editar): ?>
    <button class="btn-primary" onclick="abrirModal()">+ Novo Produto</button>
    <?php endif; ?>
  </div>

  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($erro): ?><div class="alert alert-err">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <?php if($baixo_count > 0): ?>
  <div class="alert alert-err" style="margin-bottom:18px">⚠️ <strong><?= $baixo_count ?> produto<?= $baixo_count!=1?'s':'' ?></strong> com estoque abaixo do mínimo</div>
  <?php endif; ?>

  <div class="section-box">
    <div class="section-bar">
      <h2 class="section-title">Catálogo de Produtos</h2>
      <form method="GET" class="filtro-form" style="margin:0">
        <input type="text" name="busca" class="input-filtro" placeholder="🔍 Buscar produto..." value="<?= htmlspecialchars($busca) ?>">
        <select name="categoria" class="input-filtro">
          <option value="">Todas as categorias</option>
          <?php foreach($categorias as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $filtro_cat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-secondary">Filtrar</button>
        <?php if($busca||$filtro_cat): ?><a href="produtos.php" class="btn-ghost">Limpar</a><?php endif; ?>
      </form>
    </div>

    <table class="tabela">
      <thead>
        <tr>
          <th>Código</th><th>Nome</th><th>Categoria</th>
          <th>Custo Unit.</th><th>Venda Unit.</th><th>Margem</th>
          <th>Estoque</th><th>Status</th>
          <?php if($pode_editar): ?><th>Ações</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($lista)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">📭</div><div class="empty-state-text">Nenhum produto encontrado.</div></div></td></tr>
        <?php else: ?>
        <?php foreach($lista as $p):
          $baixo = ($p['estoque']??0) <= ($p['estoque_minimo']??0);
          $margem = ($p['preco_custo']??0) > 0 ? round((($p['preco_venda']??0)-($p['preco_custo']??0))/($p['preco_custo']??1)*100,1) : 0;
        ?>
        <tr class="<?= $baixo?'row-alert':'' ?>">
          <td style="color:var(--cyan);font-weight:600;font-family:monospace;font-size:.85rem"><?= htmlspecialchars($p['codigo']??'') ?></td>
          <td style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($p['nome']??'') ?></td>
          <td>
            <span style="background:var(--bg-elevated);border:1px solid var(--border);padding:2px 9px;border-radius:20px;font-size:.75rem;color:var(--text-secondary)">
              <?= htmlspecialchars($p['categoria']??'—') ?>
            </span>
          </td>
          <td><?= formatar_moeda($p['preco_custo']??0) ?></td>
          <td style="color:var(--green);font-weight:600"><?= formatar_moeda($p['preco_venda']??0) ?></td>
          <td>
            <span style="color:<?= $margem>=0?'var(--green)':'var(--red)' ?>;font-weight:700;font-size:.85rem"><?= $margem ?>%</span>
          </td>
          <td>
            <span class="<?= $baixo?'estq-baixo':'estq-ok' ?>" style="font-weight:700">
              <?= (int)($p['estoque']??0) ?> un.
            </span>
            <div class="progress-bar-wrap" style="width:60px;margin-top:4px">
              <?php $pct2 = ($p['estoque_minimo']??0)>0 ? min(100,round(($p['estoque']/max(1,$p['estoque_minimo']))*50)) : 50; ?>
              <div class="progress-bar-fill" style="width:<?= min(100,$pct2*2) ?>%;background:<?= $baixo?'var(--red)':'var(--green)' ?>"></div>
            </div>
          </td>
          <td><?= $baixo ? '<span class="badge-alert">⚠️ Baixo</span>' : '<span class="badge-ok">✅ OK</span>' ?></td>
          <?php if($pode_editar): ?>
          <td class="td-acoes">
            <button class="btn-xs btn-edit" onclick="editarProduto(<?= htmlspecialchars(json_encode($p)) ?>)" title="Editar">✏️</button>
            <?php if(verificar_permissao(['admin'])): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Excluir produto?')">
            <?= csrf_campo() ?>
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn-xs btn-del" title="Excluir">🗑️</button>
            </form>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($pode_editar): ?>
<div class="modal-overlay" id="modalProduto" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalTitulo">Novo Produto</h3>
      <button class="modal-close" onclick="fecharModal()">✕</button>
    </div>
    <form method="POST">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" id="prod_id" value="0">
      <div class="form-grid">
        <div class="form-group"><label>Código *</label><input type="text" name="codigo" id="prod_codigo" required class="input-field" placeholder="EX-001"></div>
        <div class="form-group"><label>Nome *</label><input type="text" name="nome" id="prod_nome" required class="input-field" placeholder="Nome do produto"></div>
        <div class="form-group"><label>Categoria</label><input type="text" name="categoria" id="prod_categoria" class="input-field" placeholder="Ex: Eletrônicos"></div>
        <div class="form-group"><label>Custo Unit. (R$)</label><input type="number" name="preco_custo" id="prod_custo" step="0.01" min="0" class="input-field" value="0"></div>
        <div class="form-group"><label>Venda Unit. (R$)</label><input type="number" name="preco_venda" id="prod_venda" step="0.01" min="0" class="input-field" value="0"></div>
        <div class="form-group"><label>Estoque Atual</label><input type="number" name="estoque" id="prod_estoque" min="0" class="input-field" value="0"></div>
        <div class="form-group"><label>Estoque Mínimo</label><input type="number" name="estoque_minimo" id="prod_min" min="0" class="input-field" value="0"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn-primary">Salvar Produto</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirModal(){
  document.getElementById('modalTitulo').textContent='Novo Produto';
  ['prod_id','prod_codigo','prod_nome','prod_categoria'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('prod_id').value='0';
  ['prod_custo','prod_venda','prod_estoque','prod_min'].forEach(id=>document.getElementById(id).value='0');
  document.getElementById('modalProduto').style.display='flex';
}
function editarProduto(p){
  document.getElementById('modalTitulo').textContent='Editar Produto';
  document.getElementById('prod_id').value=p.id;
  document.getElementById('prod_codigo').value=p.codigo;
  document.getElementById('prod_nome').value=p.nome;
  document.getElementById('prod_categoria').value=p.categoria||'';
  document.getElementById('prod_custo').value=p.preco_custo;
  document.getElementById('prod_venda').value=p.preco_venda;
  document.getElementById('prod_estoque').value=p.estoque;
  document.getElementById('prod_min').value=p.estoque_minimo;
  document.getElementById('modalProduto').style.display='flex';
}
function fecharModal(){document.getElementById('modalProduto').style.display='none';}
window.addEventListener('click',e=>{if(e.target.id==='modalProduto')fecharModal();});
</script>
<?php endif; ?>
<footer class="rodape">StockPro · Desenvolvido por Arthur</footer>
</body>
</html>
