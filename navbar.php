<?php
$usuario = get_usuario_logado();
$pagina_atual = basename($_SERVER['PHP_SELF']);
$nav_items = [
    'index.php'        => ['icon'=>'⬡','label'=>'Dashboard'],
    'produtos.php'     => ['icon'=>'◈','label'=>'Produtos'],
    'entradas.php'     => ['icon'=>'↓','label'=>'Entradas'],
    'saidas.php'       => ['icon'=>'↑','label'=>'Saídas'],
    'fornecedores.php' => ['icon'=>'◎','label'=>'Fornecedores'],
    'relatorios.php'   => ['icon'=>'≡','label'=>'Relatórios'],
];
$initials = strtoupper(substr($usuario['nome'],0,1));
?>
<nav class="navbar">
  <a href="index.php" class="nav-brand">
    <div class="nav-brand-icon">📦</div>
    Stock<span>Pro</span>
  </a>
  <ul class="nav-links">
    <?php foreach($nav_items as $href => $item): ?>
    <li><a href="<?= $href ?>" class="<?= $pagina_atual===$href?'active':'' ?>"><?= $item['label'] ?></a></li>
    <?php endforeach; ?>
  </ul>
  <div class="nav-user">
    <?php if (pode_editar()): ?>
    <span class="nav-perfil-aviso">✏️ Edição ativa</span>
    <?php else: ?>
    <span class="nav-perfil-aviso">🔒 Leitura</span>
    <?php endif; ?>
    <div class="user-info">
      <span class="user-name"><?= htmlspecialchars($usuario['nome']) ?></span>
      <span class="user-badge badge-<?= htmlspecialchars($usuario['perfil']) ?>"><?= perfil_label($usuario['perfil']) ?></span>
    </div>
    <div class="user-avatar"><?= $initials ?></div>
    <a href="logout.php" class="btn-logout">Sair</a>
  </div>
</nav>
