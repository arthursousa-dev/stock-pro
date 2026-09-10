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
        $acao=$_POST['acao']??'';
        $db=ler_json('db_fornecedores.json'); $forn=$db['fornecedores']??[]; $next_id=$db['next_id']??1;
        if ($acao==='salvar') {
            $id=(int)($_POST['id']??0); $nome=trim($_POST['nome']??'');
            $email=trim($_POST['email']??''); $tel=trim($_POST['telefone']??''); $cnpj=trim($_POST['cnpj']??'');
            if (!$nome) { $erro='Nome é obrigatório.'; }
            else {
                if ($id>0) {
                    foreach($forn as &$f){if($f['id']==$id){$f['nome']=$nome;$f['email']=$email;$f['telefone']=$tel;$f['cnpj']=$cnpj;break;}}
                    $msg='Fornecedor atualizado!';
                } else {
                    $forn[]=['id'=>$next_id,'nome'=>$nome,'email'=>$email,'telefone'=>$tel,'cnpj'=>$cnpj];
                    $next_id++; $msg='Fornecedor cadastrado!';
                }
                $db['fornecedores']=$forn; $db['next_id']=$next_id;
                salvar_json('db_fornecedores.json',$db);
            }
        } elseif ($acao==='excluir' && verificar_permissao(['admin'])) {
            $id=(int)($_POST['id']??0);
            $db['fornecedores']=array_values(array_filter($forn,fn($f)=>$f['id']!=$id));
            salvar_json('db_fornecedores.json',$db); $msg='Fornecedor excluído.';
        }
    }
}

$db_forn=ler_json('db_fornecedores.json'); $fornecedores=$db_forn['fornecedores']??[];
$busca=trim($_GET['busca']??'');
$lista=$busca?array_filter($fornecedores,fn($f)=>str_contains(mb_strtolower($f['nome']??''),mb_strtolower($busca))):$fornecedores;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fornecedores — StockPro</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php require 'navbar.php'; ?>
<div class="page-wrapper">

  <div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="page-icon" style="background:var(--purple-dim)">🏭</div>
      <div>
        <h1 class="page-title">Fornecedores</h1>
        <span class="page-sub"><?= count($fornecedores) ?> fornecedores cadastrados</span>
      </div>
    </div>
    <?php if($pode_editar): ?>
    <button class="btn-primary" onclick="abrirModalForn()">+ Novo Fornecedor</button>
    <?php endif; ?>
  </div>

  <?php if($msg): ?><div class="alert alert-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($erro): ?><div class="alert alert-err">⚠️ <?= htmlspecialchars($erro) ?></div><?php endif; ?>

  <div class="section-box">
    <div class="section-bar">
      <h2 class="section-title">Cadastro de Fornecedores</h2>
      <form method="GET" class="filtro-form" style="margin:0">
        <input type="text" name="busca" class="input-filtro" placeholder="🔍 Buscar fornecedor..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn-secondary">Buscar</button>
        <?php if($busca): ?><a href="fornecedores.php" class="btn-ghost">Limpar</a><?php endif; ?>
      </form>
    </div>

    <?php if(empty($lista)): ?>
    <div class="empty-state"><div class="empty-state-icon">🏭</div><div class="empty-state-text">Nenhum fornecedor encontrado.</div></div>
    <?php else: ?>

    <!-- CARDS GRID para fornecedores -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px">
      <?php foreach($lista as $f): ?>
      <div style="background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-md);padding:18px;transition:all .2s;position:relative;overflow:hidden" onmouseover="this.style.borderColor='var(--border-bright)'" onmouseout="this.style.borderColor='var(--border)'">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px">
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:38px;height:38px;border-radius:10px;background:var(--purple-dim);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0">🏭</div>
            <div>
              <div style="font-weight:700;color:var(--text-primary);font-size:.92rem"><?= htmlspecialchars($f['nome']) ?></div>
              <?php if(!empty($f['cnpj'])): ?>
              <div style="font-size:.72rem;color:var(--text-muted);font-family:monospace"><?= htmlspecialchars($f['cnpj']) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php if($pode_editar): ?>
          <div style="display:flex;gap:4px;flex-shrink:0">
            <button class="btn-xs btn-edit" onclick="editarForn(<?= htmlspecialchars(json_encode($f)) ?>)" title="Editar">✏️</button>
            <?php if(verificar_permissao(['admin'])): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Excluir fornecedor?')">
            <?= csrf_campo() ?>
              <input type="hidden" name="acao" value="excluir">
              <input type="hidden" name="id" value="<?= $f['id'] ?>">
              <button type="submit" class="btn-xs btn-del" title="Excluir">🗑️</button>
            </form>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px;border-top:1px solid var(--border);padding-top:10px">
          <?php if(!empty($f['email'])): ?>
          <div style="display:flex;align-items:center;gap:7px;font-size:.82rem;color:var(--text-secondary)">
            <span style="opacity:.5">✉️</span> <?= htmlspecialchars($f['email']) ?>
          </div>
          <?php endif; ?>
          <?php if(!empty($f['telefone'])): ?>
          <div style="display:flex;align-items:center;gap:7px;font-size:.82rem;color:var(--text-secondary)">
            <span style="opacity:.5">📞</span> <?= htmlspecialchars($f['telefone']) ?>
          </div>
          <?php endif; ?>
          <?php if(empty($f['email']) && empty($f['telefone'])): ?>
          <div style="font-size:.78rem;color:var(--text-muted);font-style:italic">Sem contato cadastrado</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if($pode_editar): ?>
<div class="modal-overlay" id="modalForn" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalFornTitulo">Novo Fornecedor</h3>
      <button class="modal-close" onclick="document.getElementById('modalForn').style.display='none'">✕</button>
    </div>
    <form method="POST">
            <?= csrf_campo() ?>
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" id="forn_id" value="0">
      <div class="form-grid">
        <div class="form-group"><label>Nome *</label><input type="text" name="nome" id="forn_nome" required class="input-field" placeholder="Razão social"></div>
        <div class="form-group"><label>CNPJ</label><input type="text" name="cnpj" id="forn_cnpj" class="input-field" placeholder="00.000.000/0000-00"></div>
        <div class="form-group"><label>E-mail</label><input type="email" name="email" id="forn_email" class="input-field" placeholder="contato@empresa.com"></div>
        <div class="form-group"><label>Telefone</label><input type="text" name="telefone" id="forn_tel" class="input-field" placeholder="(00) 00000-0000"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" onclick="document.getElementById('modalForn').style.display='none'">Cancelar</button>
        <button type="submit" class="btn-primary">Salvar Fornecedor</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirModalForn(){
  document.getElementById('modalFornTitulo').textContent='Novo Fornecedor';
  ['forn_id','forn_nome','forn_cnpj','forn_email','forn_tel'].forEach(id=>document.getElementById(id).value=id==='forn_id'?'0':'');
  document.getElementById('modalForn').style.display='flex';
}
function editarForn(f){
  document.getElementById('modalFornTitulo').textContent='Editar Fornecedor';
  document.getElementById('forn_id').value=f.id;
  document.getElementById('forn_nome').value=f.nome||'';
  document.getElementById('forn_cnpj').value=f.cnpj||'';
  document.getElementById('forn_email').value=f.email||'';
  document.getElementById('forn_tel').value=f.telefone||'';
  document.getElementById('modalForn').style.display='flex';
}
window.addEventListener('click',e=>{if(e.target.id==='modalForn')e.target.style.display='none';});
</script>
<?php endif; ?>
<footer class="rodape">StockPro · Desenvolvido por Arthur</footer>
</body>
</html>
