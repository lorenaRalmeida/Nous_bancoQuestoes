<?php
// Cabeçalho compartilhado pela tela inicial, bancos de questões e quiz.
$base = $base ?? '';
require_once __DIR__.'/app.php';
login_obrigatorio($base);
?>
<nav class="navbar" aria-label="Menu superior">
  <a href="<?= $base ?>index.php" class="navbar-logo" aria-label="Nous — início">
    <img src="../../imagens/logo-nous-claro.png" alt="Logo Nous" class="logo-img logo-for-light">
    <img src="../../imagens/logo-nous-escuro.png" alt="Logo Nous" class="logo-img logo-for-dark">
  </a>
  <div class="navbar-nav">
    <a href="<?= $base ?>index.php" class="nav-item" data-target="home">Início</a>
    <a href="<?= $base ?>index.php?secao=progresso" class="nav-item" data-target="progresso">Progresso</a>
    <a href="<?= $base ?>index.php?secao=tarefas" class="nav-item" data-target="tarefas">Tarefas</a>
    <a href="<?= $base ?>index.php?secao=agenda" class="nav-item" data-target="agenda">Agenda</a>
    <a href="<?= $base ?>index.php?secao=avisos" class="nav-item" data-target="avisos">Avisos</a>
  </div>
  <div class="navbar-actions">
    <a href="<?= $base ?>index.php?secao=perfil" class="nav-link">Perfil</a>
    <a href="<?= $base ?>logout.php" class="btn-cadastrar btn-sair">Sair</a>
    <button class="globe-btn" type="button" data-theme-toggle aria-label="Alternar tema" title="Alternar tema">
      <span class="tema-simbolo" aria-hidden="true">☾</span>
    </button>
  </div>
</nav>

