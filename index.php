\<?php
// bloqueio para apenas usuarios logados poder acessar o site
session_start();
if (!isset($\_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
// conecta ao banco para buscar os dados do usuário logado
include('conexao.php');
$id_usuario = $\_SESSION['usuario_id'];
$sql = "SELECT nome, email FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
// dados gerais para caso ocorra algum erro
$nome_usuario = $usuario['nome'] ?? 'Estudante';
$email_usuario = $usuario['email'] ?? 'estudante\@exemplo.com';
// gera as iniciais para o avatar
$partes = explode(' ', trim($nome_usuario));
$iniciais = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr(end($partes), 0, 1) : ''));
$primeiro_nome = $partes[0] ?? 'Estudante';
// busca os compromissos da agenda do usuário logado
$sql_agenda = "SELECT \* FROM compromissos WHERE usuario_id = ? ORDER BY data_compromisso ASC";
$stmt_agenda = $conn->prepare($sql_agenda);
$stmt_agenda->bind_param("i", $id_usuario);
$stmt_agenda->execute();
$resultado_agenda = $stmt_agenda->get_result();
// busca as tarefas do usuário logado
$sql_tarefas = "SELECT \* FROM tarefas WHERE usuario_id = ? ORDER BY id DESC";
$stmt_tarefas = $conn->prepare($sql_tarefas);
$stmt_tarefas->bind_param("i", $id_usuario);
$stmt_tarefas->execute();
$resultado_tarefas = $stmt_tarefas->get_result();
?>
\<!DOCTYPE *html*>
\<html *lang*="pt-BR">
\<head>
  \<meta *charset*="UTF-8" />
  \<meta *name*="viewport" *content*="width=device-width, initial-scale=1.0" />
  \<title>Nous · Portal de Estudos\</title>
  \<link *rel*="preconnect" *href*="https\\://fonts.googleapis.com">
  \<link *rel*="preconnect" *href*="https\\://fonts.gstatic.com" *crossorigin*>
  \<link *href*="https\\://fonts.googleapis.com/css2?family=Inter:wght\@400;500;600;700;800;900&display=swap" *rel*="stylesheet">
  \<link *rel*="stylesheet" *href*="https\\://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  \<link *rel*="stylesheet" *href*="style.css">
\</head>
\<body>
  \<script>
    // mantém o mesmo tema ao navegar entre o portal e o login.
    try {
      if (localStorage.getItem('nous-theme') === 'dark') {
        document.body.classList.add('dark');
      }
    } catch (\_) {}
  \</script>
  \<div *id*="dashboard-screen">
    \<!-- navbar -->
    \<nav *class*="navbar" *aria-label*="Menu superior">
      \<a *href*="#home" *class*="navbar-logo" *aria-label*="Nous" *data-target*="home">
        \<img *src*="logo-nous-claro.png" *alt*="Logo Nous" *class*="logo-img logo-for-light">
        \<img *src*="logo-nous-escuro.png" *alt*="Logo Nous" *class*="logo-img logo-for-dark">
      \</a>
      \<div *class*="navbar-nav">
        \<a *href*="#" *class*="nav-item active" *data-target*="home">Início\</a>
        \<a *href*="#" *class*="nav-item" *data-target*="progresso">Progresso\</a>
        \<a *href*="#" *class*="nav-item" *data-target*="tarefas">Tarefas\</a>
        \<a *href*="#" *class*="nav-item" *data-target*="materias">Matérias\</a>
        \<a *href*="#" *class*="nav-item" *data-target*="agenda">Agenda\</a>
        \<a *href*="#" *class*="nav-item" *data-target*="avisos">Avisos\</a>
      \</div>
      \<div *class*="navbar-actions">
        \<a *href*="#" *class*="nav-link" *data-target*="turmas">Turmas\</a>
        \<a *href*="logout.php" *class*="btn-cadastrar" *style*="background: #e74c3c;">Sair\</a>
        \<button *class*="globe-btn" *type*="button" *onclick*="toggleTheme()" *aria-label*="Alternar tema" *title*="Alternar tema">
          \<i *class*="fa-solid fa-moon" *aria-hidden*="true">\</i>
        \</button>
      \</div>
    \</nav>
    \<div *class*="main-wrapper">
      \<aside *class*="sidebar">
        \<nav *class*="menu" *aria-label*="Menu lateral">
          \<a *href*="#" *class*="nav-item active" *data-target*="home">\<i *class*="fa-solid fa-house">\</i>\<span>Início\</span>\</a>
          \<a *href*="#" *class*="nav-item" *data-target*="progresso">\<i *class*="fa-solid fa-chart-column">\</i>\<span>Progresso\</span>\</a>
          \<a *href*="#" *class*="nav-item" *data-target*="tarefas">\<i *class*="fa-solid fa-pen-to-square">\</i>\<span>Tarefas\</span>\</a>
          \<a *href*="#" *class*="nav-item" *data-target*="materias">\<i *class*="fa-solid fa-book">\</i>\<span>Matérias\</span>\</a>
          \<a *href*="#" *class*="nav-item" *data-target*="agenda">\<i *class*="fa-solid fa-calendar-days">\</i>\<span>Agenda\</span>\</a>
          \<a *href*="#" *class*="nav-item" *data-target*="avisos">\<i *class*="fa-solid fa-bell">\</i>\<span>Avisos\</span>\</a>
            \<div *class*="sidebar-group-label">Banco de Questões\</div>
            \<a *href*="enem/ler_q_json.php" *class*="nav-item vestibular-nav">\<span>ENEM\</span>\</a>
            \<a *href*="fuvest/ler_q_json.php" *class*="nav-item vestibular-nav">\<span>FUVEST\</span>\</a>
            \<a *href*="unicamp/ler_q_json.php" *class*="nav-item vestibular-nav">\<span>Unicamp\</span>\</a>
            \<a *href*="unesp/ler_q_json.php" *class*="nav-item vestibular-nav">\<span>Unesp\</span>\</a>
            \<a *href*="#" *class*="nav-item" *data-target*="perfil">\<i *class*="fa-solid fa-user">\</i>\<span>Perfil\</span>\</a>
        \</nav>
        \<nav *class*="menu logout" *aria-label*="Sair">
          \<a *href*="logout.php">\<i *class*="fa-solid fa-right-from-bracket">\</i>\<span>Sair\</span>\</a>
        \</nav>
      \</aside>
      \<main *class*="content">
        \<!-- início -->
        \<section *id*="section-home" *class*="page-section active">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>Oii, \<?php echo htmlspecialchars($primeiro_nome); ?>!!\</h1>
              \<p>Seja bem-vindo ao seu novo portal de estudos.\</p>
            \</div>
          \</div>
          \<div *class*="hero">
            \<div>
              \<small>Hoje é um ótimo dia para começar\</small>
              \<h1>Dê o primeiro passo rumo à aprovação!\</h1>
              \<p>Adicione suas matérias e crie suas primeiras metas de estudo.\</p>
            \</div>
            \<i *class*="fa-solid fa-rocket hero-visual" *aria-hidden*="true">\</i>
          \</div>
          \<div *class*="grid-2col">
            \<div>
              \<div *class*="section-title">
                \<h2>Explore o Nous\</h2>
                \<a>Ver tudo\</a>
              \</div>
                            \<div *class*="explore-cards vestibulares-grid">
                \<article *class*="explore-card vestibular-card">
                  \<span *class*="vestibular-sigla">ENEM\</span>
                  \<h3>Questões ENEM\</h3>
                  \<p>Pratique com questões do Exame Nacional do Ensino Médio.\</p>
                  \<a *class*="vestibular-botao" *href*="enem/ler_q_json.php">Explorar questões\</a>
                \</article>
                \<article *class*="explore-card vestibular-card">
                  \<span *class*="vestibular-sigla">FUVEST\</span>
                  \<h3>Questões FUVEST\</h3>
                  \<p>Estude para o vestibular da Universidade de São Paulo.\</p>
                  \<a *class*="vestibular-botao" *href*="fuvest/ler_q_json.php">Explorar questões\</a>
                \</article>
                \<article *class*="explore-card vestibular-card">
                  \<span *class*="vestibular-sigla">UNICAMP\</span>
                  \<h3>Questões Unicamp\</h3>
                  \<p>Resolva exercícios e prepare-se para a Unicamp.\</p>
                  \<a *class*="vestibular-botao" *href*="unicamp/ler_q_json.php">Explorar questões\</a>
                \</article>
                \<article *class*="explore-card vestibular-card">
                  \<span *class*="vestibular-sigla">UNESP\</span>
                  \<h3>Questões Unesp\</h3>
                  \<p>Treine para o vestibular da Unesp.\</p>
                  \<a *class*="vestibular-botao" *href*="unesp/ler_q_json.php">Explorar questões\</a>
                \</article>
              \</div>
            \</div>
            \<div>
              \<div *class*="section-title">
                \<h2>Avisos Recentes\</h2>
                \<a>Ver todos\</a>
              \</div>
              \<div *class*="notice-card" *style*="flex-direction:column; gap:8px; padding:16px">
                \<h4 *style*="font-size:14px; color:var(--roxo)">Bem-vindo ao Nous\</h4>
                \<p *style*="font-size:12px; margin:0">Configure seu perfil para personalizar sua experiência.\</p>
              \</div>
            \</div>
          \</div>
        \</section>
        \<!-- progresso -->
        \<section *id*="section-progresso" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-chart-line">\</i> Seu Progresso\</h1>
            \</div>
          \</div>
          \<div *class*="stats-grid">
            \<div *class*="stat-card">
              \<div *class*="stat-icon">\<i *class*="fa-solid fa-fire">\</i>\</div>
              \<div *class*="stat-info">\<strong>0 Dias\</strong>\<span>Sequência\</span>\</div>
            \</div>
            \<div *class*="stat-card">
              \<div *class*="stat-icon">\<i *class*="fa-solid fa-clock">\</i>\</div>
              \<div *class*="stat-info">\<strong>0h\</strong>\<span>Horas\</span>\</div>
            \</div>
            \<div *class*="stat-card">
              \<div *class*="stat-icon">\<i *class*="fa-solid fa-check-double">\</i>\</div>
              \<div *class*="stat-info">\<strong>0\</strong>\<span>Tarefas\</span>\</div>
            \</div>
            \<div *class*="stat-card">
              \<div *class*="stat-icon">\<i *class*="fa-solid fa-bullseye">\</i>\</div>
              \<div *class*="stat-info">\<strong>0%\</strong>\<span>Acerto\</span>\</div>
            \</div>
          \</div>
          \<div *class*="empty-state">
            \<div *class*="icon-wrap">\<i *class*="fa-solid fa-chart-simple">\</i>\</div>
            \<h3>Sem dados no momento\</h3>
            \<p>Seus gráficos aparecerão aqui.\</p>
          \</div>
        \</section>
        \<!-- tarefas -->
        \<section *id*="section-tarefas" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-pen-to-square">\</i> Tarefas\</h1>
              \<p>Gerencie suas atividades diárias e metas de estudo.\</p>
            \</div>
          \</div>
          \<!-- formulário para adicionar tarefa -->
          \<div *class*="card-form" *style*="background: var(--bg-card, #fff); padding: 20px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            \<h3 *style*="margin-bottom: 15px; font-size: 1.1rem; color: var(--text-main);">Nova Tarefa\</h3>
            \<form *action*="salvar_tarefa.php" *method*="POST" *style*="display: flex; gap: 15px; flex-wrap: wrap;">
              \<div *style*="flex: 1; min-width: 250px;">
                \<input *type*="text" *name*="titulo" *required* *placeholder*="Ex: Realizar 10 questões de Matemática" *style*="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem;">
              \</div>
              \<div>
                \<button *type*="submit" *class*="btn-primary" *style*="background: #4f46e5; color: white; border: none; padding: 11px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; height: 100%;">\<i *class*="fa-solid fa-plus">\</i> Adicionar Tarefa\</button>
              \</div>
            \</form>
          \</div>
          \<!-- listagem dinâmica de tarefas -->
          \<div *class*="tarefas-list">
            \<?php if ($resultado_tarefas->num_rows > 0): ?>
              \<div *style*="display: grid; gap: 12px;">
                \<?php while($tarefa = $resultado_tarefas->fetch_assoc()): ?>
                  \<div *style*="background: var(--bg-card, #fff); padding: 16px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.03); border-left: 4px solid #10b981;">
                    \<div>
                      \<h4 *style*="margin: 0; font-size: 1rem; color: var(--text-main);">\<?php echo htmlspecialchars($tarefa['titulo']); ?>\</h4>
                    \</div>
                    \<a *href*="excluir_tarefa.php?id=\<?php echo $tarefa['id']; ?>" *onclick*="return confirm('Deseja concluir/excluir esta tarefa?');" *style*="color: #ef4444; font-size: 0.9rem; text-decoration: none;">\<i *class*="fa-solid fa-check">\</i> Concluir\</a>
                  \</div>
                \<?php endwhile; ?>
              \</div>
            \<?php else: ?>
              \<div *class*="empty-state">
                \<div *class*="icon-wrap">\<i *class*="fa-regular fa-clipboard">\</i>\</div>
                \<h3>Nenhuma tarefa pendente\</h3>
                \<p>Crie sua primeira tarefa acima!\</p>
              \</div>
            \<?php endif; ?>
          \</div>
        \</section>
        \<!-- matérias -->
        \<section *id*="section-materias" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-book">\</i> Matérias\</h1>
            \</div>
          \</div>
          \<div *class*="empty-state">
            \<div *class*="icon-wrap">\<i *class*="fa-solid fa-book-open">\</i>\</div>
            \<h3>Nenhuma matéria cadastrada\</h3>
            \<p>Adicione suas matérias.\</p>
            \<button *class*="btn-primary">Adicionar matéria\</button>
          \</div>
        \</section>
        \<!-- agenda -->
        \<section *id*="section-agenda" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-calendar-days">\</i> Minha Agenda\</h1>
              \<p>Organize seus simulados, provas e metas de estudo por data.\</p>
            \</div>
          \</div>
          \<!-- formulário para adicionar compromisso -->
          \<div *class*="card-form" *style*="background: var(--bg-card, #fff); padding: 20px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            \<h3 *style*="margin-bottom: 15px; font-size: 1.1rem; color: var(--text-main);">Adicionar Novo Compromisso\</h3>
            \<form *action*="salvar_agenda.php" *method*="POST" *style*="display: flex; gap: 15px; flex-wrap: wrap;">
              \<div *style*="flex: 2; min-width: 200px;">
                \<label *style*="display: block; font-size: 0.85rem; margin-bottom: 5px; color: var(--text-muted);">Título do Evento (ex: Simulado do ENEM)\</label>
                \<input *type*="text" *name*="titulo" *required* *placeholder*="O que você vai fazer?" *style*="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem;">
              \</div>
              \<div *style*="flex: 1; min-width: 150px;">
                \<label *style*="display: block; font-size: 0.85rem; margin-bottom: 5px; color: var(--text-muted);">Data\</label>
                \<input *type*="date" *name*="data_compromisso" *required* *style*="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem;">
              \</div>
              \<div *style*="display: flex; align-items: flex-end;">
                \<button *type*="submit" *style*="background: #4f46e5; color: white; border: none; padding: 11px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem;">\<i *class*="fa-solid fa-plus">\</i> Adicionar\</button>
              \</div>
            \</form>
          \</div>
          \<!-- listagem dinâmica de compromissos -->
          \<div *class*="agenda-list">
            \<?php if ($resultado_agenda->num_rows > 0): ?>
              \<div *style*="display: grid; gap: 12px;">
                \<?php while($evento = $resultado_agenda->fetch_assoc()): ?>
                  \<div *style*="background: var(--bg-card, #fff); padding: 16px 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.03); border-left: 4px solid #4f46e5;">
                    \<div>
                      \<h4 *style*="margin: 0 0 5px 0; font-size: 1rem; color: var(--text-main);">\<?php echo htmlspecialchars($evento['titulo']); ?>\</h4>
                      \<span *style*="font-size: 0.85rem; color: var(--text-muted);">\<i *class*="fa-regular fa-calendar">\</i> \<?php echo date('d/m/Y', strtotime($evento['data_compromisso'])); ?>\</span>
                    \</div>
                    \<a *href*="excluir_agenda.php?id=\<?php echo $evento['id']; ?>" *onclick*="return confirm('Tem certeza que deseja excluir este compromisso?');" *style*="color: #ef4444; font-size: 0.9rem; text-decoration: none;">\<i *class*="fa-solid fa-trash-can">\</i> Excluir\</a>
                  \</div>
                \<?php endwhile; ?>
              \</div>
            \<?php else: ?>
              \<div *class*="empty-state">
                \<div *class*="icon-wrap">\<i *class*="fa-regular fa-calendar-xmark">\</i>\</div>
                \<h3>Nenhum evento agendado\</h3>
                \<p>Sua agenda está vazia. Adicione sua primeira meta acima!\</p>
              \</div>
            \<?php endif; ?>
          \</div>
        \</section>
        \<!-- avisos -->
        \<section *id*="section-avisos" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-bell">\</i> Avisos\</h1>
            \</div>
          \</div>
          \<div *class*="empty-state">
            \<div *class*="icon-wrap">\<i *class*="fa-regular fa-bell-slash">\</i>\</div>
            \<h3>Nenhum aviso no momento\</h3>
            \<p>Você está em dia!\</p>
          \</div>
        \</section>
        \<!-- perfil -->
        \<section *id*="section-perfil" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-user">\</i> Meu Perfil\</h1>
            \</div>
          \</div>
          \<div *class*="profile-header">
            \<div *class*="profile-avatar">\<?php echo htmlspecialchars($iniciais); ?>\</div>
            \<div *class*="profile-info">
              \<h2>\<?php echo htmlspecialchars($nome_usuario); ?>\</h2>
              \<p>\<i *class*="fa-regular fa-envelope">\</i> \<?php echo htmlspecialchars($email_usuario); ?>\</p>
            \</div>
          \</div>
        \</section>
        \<!-- turmas -->
        \<section *id*="section-turmas" *class*="page-section">
          \<div *class*="page-header">
            \<div *class*="page-title">
              \<h1>\<i *class*="fa-solid fa-users">\</i> Turmas\</h1>
            \</div>
          \</div>
          \<div *class*="empty-state">
            \<div *class*="icon-wrap">\<i *class*="fa-solid fa-people-group">\</i>\</div>
            \<h3>Nenhuma turma disponível\</h3>
            \<p>As informações das turmas poderão aparecer aqui.\</p>
          \</div>
        \</section>
        \<footer *class*="dashboard-footer">
          \<span *class*="footer-text">Siga o Nous nas redes sociais\</span>
          \<div *class*="social-links">
            \<a *href*="#" *aria-label*="Instagram">\<i *class*="fa-brands fa-instagram">\</i>\</a>
            \<a *href*="#" *aria-label*="TikTok">\<i *class*="fa-brands fa-tiktok">\</i>\</a>
          \</div>
        \</footer>
      \</main>
    \</div>
  \</div>
  \<script>
    function updateThemeIcon() {
      const icon = document.querySelector('.globe-btn i');
      const button = document.querySelector('.globe-btn');
      if (!icon || !button) return;
      const dark = document.body.classList.contains('dark');
      icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
      button.title = dark ? 'Ativar tema claro' : 'Ativar tema escuro';
      button.setAttribute('aria-label', button.title);
    }
    function toggleTheme() {
      const dark = document.body.classList.toggle('dark');
      try {
        localStorage.setItem('nous-theme', dark ? 'dark' : 'light');
      } catch (\_) {}
      updateThemeIcon();
    }
    updateThemeIcon();
    document.querySelectorAll('.nav-item').forEach(*link* => {
      link.addEventListener('click', (*e*) => {
        e.preventDefault();
        const target = link.getAttribute('data-target');
        document.querySelectorAll('.nav-item').forEach(*l* => l.classList.remove('active'));
        document.querySelectorAll('.page-section').forEach(*sec* => sec.classList.remove('active'));
        document.querySelectorAll(\`.nav-item[data-target="${target}"]\`).forEach(*l* => l.classList.add('active'));
        const section = document.getElementById('section-' + target);
        if (section) {
          section.classList.add('active');
        }
      });
    });
  \</script>
\</body>
\</html>
