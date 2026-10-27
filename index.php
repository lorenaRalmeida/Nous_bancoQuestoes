<?php
// 1. Inicia a sessão e verifica se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Conecta ao banco para buscar os dados reais do usuário logado
include('conexao.php');

$id_usuario = $_SESSION['usuario_id'];
$sql = "SELECT nome, email FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Dados padrão caso ocorra algum imprevisto
$nome_usuario = $usuario['nome'] ?? 'Estudante';
$email_usuario = $usuario['email'] ?? 'estudante@exemplo.com';

// Gera as iniciais para o avatar (ex: Maria Silva -> MS)
$partes = explode(' ', trim($nome_usuario));
$iniciais = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr(end($partes), 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nous · Portal de Estudos</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="style.css">
</head>
<body>

  <div id="dashboard-screen">
    
    <!-- NAVBAR -->
    <nav class="navbar">
      <a class="navbar-logo" href="index.php">
        <div class="logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <strong>Nous</strong>
      </a>

      <div class="navbar-nav">
        <a href="#" class="nav-item active" data-target="home">Início</a>
        <a href="#" class="nav-item" data-target="progresso">Progresso</a>
        <a href="#" class="nav-item" data-target="tarefas">Tarefas</a>
        <a href="#" class="nav-item" data-target="materias">Matérias</a>
        <a href="#" class="nav-item" data-target="agenda">Agenda</a>
        <a href="#" class="nav-item" data-target="avisos">Avisos</a>
      </div>
      
      <div class="navbar-actions">
        <a href="#" class="nav-link">Turmas</a>
        <a href="logout.php" class="btn-cadastrar" style="background: #e74c3c;">Sair</a>
        <button class="globe-btn" onclick="toggleTheme()"><i class="fa-solid fa-globe"></i></button>
      </div>
    </nav>

    <div class="main-wrapper">
      
      <aside class="sidebar">
        <nav class="menu">
          <a href="#" class="nav-item active" data-target="home"><i class="fa-solid fa-house"></i><span>Início</span></a>
          <a href="#" class="nav-item" data-target="progresso"><i class="fa-solid fa-chart-column"></i><span>Progresso</span></a>
          <a href="#" class="nav-item" data-target="tarefas"><i class="fa-solid fa-pen-to-square"></i><span>Tarefas</span></a>
          <a href="#" class="nav-item" data-target="materias"><i class="fa-solid fa-book"></i><span>Matérias</span></a>
          <a href="#" class="nav-item" data-target="agenda"><i class="fa-solid fa-calendar-days"></i><span>Agenda</span></a>
          <a href="#" class="nav-item" data-target="avisos"><i class="fa-solid fa-bell"></i><span>Avisos</span></a>
          <a href="#" class="nav-item" data-target="perfil"><i class="fa-solid fa-user"></i><span>Perfil</span></a>
        </nav>
        <nav class="menu logout">
          <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Sair</span></a>
        </nav>
      </aside>

      <main class="content">
        
        <section id="section-home" class="page-section active">
          <div class="page-header">
            <div class="page-title">
              <h1>Olá, <?php echo htmlspecialchars(explode(' ', $nome_usuario)[0]); ?>! 👋</h1>
              <p>Seja bem-vindo ao seu novo portal de estudos.</p>
            </div>
          </div>

          <div class="hero">
            <div>
              <small>Hoje é um ótimo dia para começar</small>
              <h1>Dê o primeiro passo rumo à aprovação!</h1>
              <p>Adicione suas matérias e crie suas primeiras metas de estudo.</p>
            </div>
            <i class="fa-solid fa-rocket hero-visual"></i>
          </div>

          <div class="grid-2col">
            <div>
              <div class="section-title"><h2>Explore o Nous</h2><a>Ver tudo</a></div>
              <div class="explore-cards">
                <div class="explore-card">
                  <h3>Simulado ENEM</h3>
                  <p>Teste seus conhecimentos no formato oficial.</p>
                  <button>Iniciar</button>
                  <i class="fa-solid fa-file-pen"></i>
                </div>
                <div class="explore-card">
                  <h3>Banco de Questões</h3>
                  <p>+10.000 questões filtradas por matéria.</p>
                  <button>Explorar</button>
                  <i class="fa-solid fa-database"></i>
                </div>
              </div>
            </div>
            <div>
              <div class="section-title"><h2>Avisos Recentes</h2><a>Ver todos</a></div>
              <div class="notice-card" style="flex-direction:column; gap:8px; padding:16px">
                <h4 style="font-size:14px; color:var(--roxo)">Bem-vindo ao Nous</h4>
                <p style="font-size:12px; margin:0">Configure seu perfil para personalizar sua experiência.</p>
              </div>
            </div>
          </div>
        </section>

        <section id="section-progresso" class="page-section">
          <div class="page-header"><div class="page-title"><h1><i class="fa-solid fa-chart-line"></i> Seu Progresso</h1></div></div>
          <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-fire"></i></div><div class="stat-info"><strong>0 Dias</strong><span>Sequência</span></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-clock"></i></div><div class="stat-info"><strong>0h</strong><span>Horas</span></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-check-double"></i></div><div class="stat-info"><strong>0</strong><span>Tarefas</span></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-bullseye"></i></div><div class="stat-info"><strong>0%</strong><span>Acerto</span></div></div>
          </div>
          <div class="empty-state">
            <div class="icon-wrap"><i class="fa-solid fa-chart-simple"></i></div>
            <h3>Sem dados no momento</h3>
            <p>Seus gráficos aparecerão aqui.</p>
          </div>
        </section>

        <section id="section-tarefas" class="page-section">
          <div class="page-header"><div class="page-title"><h1><i class="fa-solid fa-pen-to-square"></i> Tarefas</h1></div></div>
          <div class="empty-state">
            <div class="icon-wrap"><i class="fa-regular fa-clipboard"></i></div>
            <h3>Nenhuma tarefa pendente</h3>
            <p>Crie sua primeira tarefa.</p>
            <button class="btn-primary">Criar tarefa</button>
          </div>
        </section>

        <section id="section-materias" class="page-section">
          <div class="page-header"><div class="page-title"><h1><i class="fa-solid fa-book"></i> Matérias</h1></div></div>
          <div class="empty-state">
            <div class="icon-wrap"><i class="fa-solid fa-book-open"></i></div>
            <h3>Nenhuma matéria cadastrada</h3>
            <p>Adicione suas matérias.</p>
            <button class="btn-primary">Adicionar matéria</button>
          </div>
        </section>

        <section id="section-agenda" class="page-section">
          <div class="page-header"><div class="page-title"><h1><i class="fa-solid fa-calendar-days"></i> Agenda</h1></div></div>
          <div class="empty-state">
            <div class="icon-wrap"><i class="fa-regular fa-calendar-xmark"></i></div>
            <h3>Nenhum evento agendado</h3>
            <p>Sua agenda está vazia.</p>
          </div>
        </section>

        <section id="section-avisos" class="page-section">
          <div class="page-header"><div class="page-title"><h1><i class="fa-solid fa-bell"></i> Avisos</h1></div></div>
          <div class="empty-state">
            <div class="icon-wrap"><i class="fa-regular fa-bell-slash"></i></div>
            <h3>Nenhum aviso no momento</h3>
            <p>Você está em dia!</p>
          </div>
        </section>

        <section id="section-perfil" class="page-section">
          <div class="page-header"><div class="page-title"><h1><i class="fa-solid fa-user"></i> Meu Perfil</h1></div></div>
          <div class="profile-header">
            <div class="profile-avatar"><?php echo $iniciais; ?></div>
            <div class="profile-info">
              <h2><?php echo htmlspecialchars($nome_usuario); ?></h2>
              <p><i class="fa-regular fa-envelope"></i> <?php echo htmlspecialchars($email_usuario); ?></p>
            </div>
          </div>
        </section>

        <footer class="dashboard-footer">
          <span class="footer-text">Siga o Nous nas redes sociais</span>
          <div class="social-links">
            <a href="#"><i class="fa-brands fa-instagram"></i></a>
            <a href="#"><i class="fa-brands fa-tiktok"></i></a>
          </div>
        </footer>

      </main>
    </div>
  </div>

  <script>
    function toggleTheme() {
      document.body.classList.toggle('dark');
    }

    document.querySelectorAll('.nav-item').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = link.getAttribute('data-target');
        document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.page-section').forEach(sec => sec.classList.remove('active'));
        document.querySelectorAll(`.nav-item[data-target="${target}"]`).forEach(l => l.classList.add('active'));
        document.getElementById('section-' + target).classList.add('action'); // mantido funcional
      });
    });
  </script>
</body>
</html>