<?php
require_once __DIR__ . '/bootstrap_sessao.php';
require 'functions.php';
if (!empty($_GET['_db_fresh'])) { $_SESSION = []; session_regenerate_id(true); }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido($_POST['csrf_token'] ?? null)) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $usuario = autenticar($email, $senha);
        if ($usuario) {
            $_SESSION['logado'] = true;
            $_SESSION['email']  = $usuario['email'];
            $_SESSION['nome']   = $usuario['nome'];
            $_SESSION['perfil'] = $usuario['perfil'];
            header('Location: index.php');
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — StockPro</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#07090f;--bg-card:#13161f;--bg-el:#1a1e2e;--bg-hov:#1f2435;
  --border:rgba(255,255,255,.07);--border-b:rgba(255,255,255,.13);
  --cyan:#00d4ff;--cyan-dim:rgba(0,212,255,.15);--cyan-glow:rgba(0,212,255,.3);
  --red:#f43f5e;--red-dim:rgba(244,63,94,.15);
  --green:#10b981;
  --text:#f0f4ff;--text-s:#8892aa;--text-m:#4b5370;
  --t:.2s cubic-bezier(.4,0,.2,1);
}
html,body{height:100%}
body{
  display:flex;min-height:100vh;
  font-family:'DM Sans',system-ui,sans-serif;
  background:var(--bg);
  color:var(--text);
  -webkit-font-smoothing:antialiased;
  background-image:
    radial-gradient(ellipse 70% 50% at 20% 50%, rgba(0,212,255,.05) 0%,transparent 60%),
    radial-gradient(ellipse 50% 60% at 80% 50%, rgba(139,92,246,.04) 0%,transparent 60%);
}

/* LEFT PANEL */
.col-left{
  flex:1.1;
  background:linear-gradient(145deg, rgba(0,212,255,.06) 0%, rgba(13,16,26,.9) 40%, rgba(139,92,246,.05) 100%);
  border-right:1px solid var(--border);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:60px 50px;
  position:relative;overflow:hidden;
}
.col-left::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2300d4ff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.brand-wrap{text-align:center;position:relative;z-index:1}
.brand-icon{
  width:80px;height:80px;
  background:linear-gradient(135deg,var(--cyan),#0096cc);
  border-radius:20px;
  display:flex;align-items:center;justify-content:center;
  font-size:2.2rem;
  margin:0 auto 24px;
  box-shadow:0 0 48px rgba(0,212,255,.25);
}
.brand-name{
  font-family:'Sora',sans-serif;
  font-size:2.8rem;font-weight:800;
  letter-spacing:-1px;
  color:var(--text);
  margin-bottom:12px;
}
.brand-name span{color:var(--cyan)}
.brand-desc{font-size:1rem;color:var(--text-s);line-height:1.7;max-width:300px;margin:0 auto 40px}
.features{display:flex;flex-direction:column;gap:14px;width:100%;max-width:300px}
.feat{
  display:flex;align-items:center;gap:12px;
  background:rgba(255,255,255,.04);
  border:1px solid var(--border);
  border-radius:12px;padding:12px 16px;
}
.feat-icon{
  width:34px;height:34px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0;
}
.feat-text{font-size:.85rem;color:var(--text-s);font-weight:500}

/* RIGHT PANEL */
.col-right{
  flex:0.9;
  display:flex;align-items:center;justify-content:center;
  padding:40px 50px;
}
.form-box{width:100%;max-width:380px}
.form-box h2{
  font-family:'Sora',sans-serif;
  font-size:1.9rem;font-weight:800;
  color:var(--text);margin-bottom:6px;letter-spacing:-.4px;
}
.form-box .sub{font-size:.9rem;color:var(--text-s);margin-bottom:32px}
.form-group{margin-bottom:20px}
.form-group label{display:block;font-size:.75rem;font-weight:700;color:var(--text-s);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.form-group input{
  width:100%;padding:12px 15px;
  border:1px solid var(--border-b);
  border-radius:10px;font-size:.95rem;
  color:var(--text);background:var(--bg-el);
  transition:all var(--t);font-family:inherit;
}
.form-group input::placeholder{color:var(--text-m)}
.form-group input:focus{outline:none;border-color:var(--cyan);box-shadow:0 0 0 3px var(--cyan-dim);background:var(--bg-hov)}
.btn-login{
  width:100%;padding:13px;
  background:linear-gradient(135deg,var(--cyan),#0096cc);
  color:#000;border:none;border-radius:10px;
  font-size:.95rem;font-weight:700;cursor:pointer;
  transition:all var(--t);font-family:inherit;
  box-shadow:0 4px 20px rgba(0,212,255,.3);
  letter-spacing:.1px;
}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 8px 28px rgba(0,212,255,.4);filter:brightness(1.05)}
.btn-login:active{transform:translateY(0)}
.erro{
  background:var(--red-dim);color:var(--red);
  border:1px solid rgba(244,63,94,.25);
  border-radius:10px;padding:12px 15px;
  font-size:.87rem;margin-bottom:18px;
  display:flex;align-items:center;gap:8px;
  animation:shake .3s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
.creds{
  margin-top:20px;padding:14px 16px;
  background:rgba(245,158,11,.06);
  border:1px solid rgba(245,158,11,.2);
  border-radius:10px;font-size:.78rem;color:#d97706;
}
.creds strong{display:block;margin-bottom:6px;color:#f59e0b}
.creds code{background:rgba(245,158,11,.12);padding:1px 6px;border-radius:5px;font-size:.76rem;font-family:monospace}
.divider{height:1px;background:var(--border);margin:24px 0}
.sig{font-size:.7rem;color:var(--text-m);text-align:center}

@media(max-width:800px){
  body{flex-direction:column}
  .col-left{flex:none;min-height:auto;padding:40px 30px}
  .brand-name{font-size:2rem}
  .features{display:none}
  .col-right{flex:1;padding:30px 24px}
}
</style>
</head>
<body>

<div class="col-left">
  <div class="brand-wrap">
    <div class="brand-icon">📦</div>
    <div class="brand-name">Stock<span>Pro</span></div>
    <p class="brand-desc">Sistema completo de gestão de estoque com controle de entradas, saídas e relatórios em tempo real.</p>
    <div class="features">
      <div class="feat">
        <div class="feat-icon" style="background:rgba(0,212,255,.15)">📊</div>
        <span class="feat-text">Dashboard com gráficos em tempo real</span>
      </div>
      <div class="feat">
        <div class="feat-icon" style="background:rgba(16,185,129,.15)">📦</div>
        <span class="feat-text">Controle total de estoque e alertas</span>
      </div>
      <div class="feat">
        <div class="feat-icon" style="background:rgba(139,92,246,.15)">📋</div>
        <span class="feat-text">Relatórios financeiros exportáveis</span>
      </div>
    </div>
  </div>
</div>

<div class="col-right">
  <div class="form-box">
    <h2>Bem-vindo</h2>
    <p class="sub">Entre com sua conta para continuar</p>

    <?php if ($erro): ?>
    <div class="erro">⚠️ <?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <?= csrf_campo() ?>
      <div class="form-group">
        <label>E-mail</label>
        <input type="email" name="email" placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
      </div>
      <div class="form-group">
        <label>Senha</label>
        <input type="password" name="senha" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">Entrar →</button>
    </form>

    <div class="creds">
      <strong>🔑 Credenciais de teste</strong>
      <code>admin@estoque.com</code> / <code>admin123</code><br>
      <code>gerente@estoque.com</code> / <code>gerente123</code><br>
      <code>operacional@estoque.com</code> / <code>operacional123</code>
    </div>

    <div class="divider"></div>
    <p class="sig">StockPro · Desenvolvido por Arthur</p>
  </div>
</div>

</body>
</html>
