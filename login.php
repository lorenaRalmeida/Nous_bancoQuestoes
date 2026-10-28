<?php
include('conexao.php');
session_start();

$mensagem_login = "";
$mensagem_cadastro = "";
$tipo_mensagem = "";
$mostrar_cadastro = false;

// Mensagem após cadastro concluído.
if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso') {
    $mensagem_cadastro = "Conta criada com sucesso! Faça login abaixo.";
    $tipo_mensagem = "sucesso";
}

// Processa o formulário de login.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email !== '' && $senha !== '') {
        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado && $resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();

                // Confere a senha armazenada como hash.
                if (password_verify($senha, $usuario['senha'])) {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];

                    header("Location: index.php");
                    exit;
                } else {
                    $mensagem_login = "Senha incorreta!";
                }
            } else {
                $mensagem_login = "E-mail não encontrado!";
            }

            $stmt->close();
        } else {
            $mensagem_login = "Não foi possível processar o login. Tente novamente.";
        }
    } else {
        $mensagem_login = "Preencha todos os campos!";
    }
}

// Processa o formulário de cadastro.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastro') {
    $mostrar_cadastro = true;
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome !== '' && $email !== '' && $senha !== '') {
        // Verifica se o e-mail já está cadastrado.
        $sqlCheck = "SELECT id FROM usuarios WHERE email = ?";
        $stmtCheck = $conn->prepare($sqlCheck);

        if ($stmtCheck) {
            $stmtCheck->bind_param("s", $email);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                $mensagem_cadastro = "Este e-mail já está cadastrado!";
                $tipo_mensagem = "erro";
                $stmtCheck->close();
            } else {
                $stmtCheck->close();

                // Armazena somente o hash da senha, nunca a senha em texto puro.
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $sqlInsert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
                $stmtInsert = $conn->prepare($sqlInsert);

                if ($stmtInsert) {
                    $stmtInsert->bind_param("sss", $nome, $email, $senhaHash);

                    if ($stmtInsert->execute()) {
                        $stmtInsert->close();
                        header("Location: login.php?cadastro=sucesso");
                        exit;
                    } else {
                        $mensagem_cadastro = "Erro ao cadastrar. Tente novamente.";
                        $tipo_mensagem = "erro";
                    }

                    $stmtInsert->close();
                } else {
                    $mensagem_cadastro = "Erro ao preparar o cadastro. Tente novamente.";
                    $tipo_mensagem = "erro";
                }
            }
        } else {
            $mensagem_cadastro = "Não foi possível verificar o e-mail. Tente novamente.";
            $tipo_mensagem = "erro";
        }
    } else {
        $mensagem_cadastro = "Preencha todos os campos!";
        $tipo_mensagem = "erro";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nous · Acesso</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
  <script>
    // Aplica o tema salvo pelo usuário antes de exibir a página.
    try {
      if (localStorage.getItem('nous-theme') === 'dark') {
        document.body.classList.add('dark');
      }
    } catch (erro) {}
  </script>

  <div class="auth-wrapper">
    <div class="auth-container">

      <div class="auth-banner">
        <div class="banner-logo">
          <img src="logo-nous-banner-claro.png" alt="Logo Nous" class="logo-img-banner logo-for-light">
          <img src="logo-nous-banner-escuro.png" alt="Logo Nous" class="logo-img-banner logo-for-dark">
        </div>

        <h2>Bem-vindo ao futuro dos seus estudos.</h2>
        <p>O Nous centraliza sua rotina, acompanha seu progresso e te ajuda a alcançar a aprovação.</p>

        <div class="auth-banner-features">
          <div class="auth-banner-feature"><i class="fa-solid fa-check"></i> Mais de 10.000 questões</div>
          <div class="auth-banner-feature"><i class="fa-solid fa-check"></i> Simulados no formato oficial</div>
          <div class="auth-banner-feature"><i class="fa-solid fa-check"></i> Estatísticas em tempo real</div>
        </div>
      </div>

      <div class="auth-form-side">
        <div class="auth-mobile-logo" aria-label="Nous">
          <img src="logo-nous-claro.png" alt="Logo Nous" class="logo-img logo-for-light">
          <img src="logo-nous-escuro.png" alt="Logo Nous" class="logo-img logo-for-dark">
        </div>

        <!-- TELA DE LOGIN -->
        <div id="login-box" <?= $mostrar_cadastro ? 'style="display:none;"' : '' ?>>
          <div class="auth-header">
            <h1>Entrar na conta</h1>
            <p>Ainda não tem conta? <a href="#cadastro" onclick="toggleAuth(event)">Cadastre-se grátis</a></p>
          </div>

          <?php if ($mensagem_login !== ''): ?>
            <p class="auth-message auth-message-error" role="alert"><?= htmlspecialchars($mensagem_login, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <?php if ($mensagem_cadastro !== '' && $tipo_mensagem === 'sucesso'): ?>
            <p class="auth-message auth-message-success" role="status"><?= htmlspecialchars($mensagem_cadastro, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <form action="login.php" method="POST">
            <input type="hidden" name="acao" value="login">

            <div class="form-group">
              <label for="login-email">E-mail</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="login-email" type="email" name="email" placeholder="seu@email.com" autocomplete="email" required>
              </div>
            </div>

            <div class="form-group">
              <label for="login-password">Senha</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="login-password" type="password" name="senha" placeholder="Sua senha" autocomplete="current-password" required>
              </div>
            </div>

            <button type="submit" class="btn-submit">Entrar <i class="fa-solid fa-arrow-right"></i></button>
          </form>

          <div class="auth-divider">OU</div>
          <button class="btn-social" type="button"><i class="fa-brands fa-google" style="color:#DB4437" aria-hidden="true"></i> Continuar com Google</button>
        </div>

        <!-- TELA DE CADASTRO -->
        <div id="register-box" <?= $mostrar_cadastro ? '' : 'style="display:none;"' ?>>
          <div class="auth-header">
            <h1>Criar conta</h1>
            <p>Já tem uma conta? <a href="login.php" onclick="toggleAuth(event)">Faça login</a></p>
          </div>

          <?php if ($mensagem_cadastro !== '' && $tipo_mensagem === 'erro'): ?>
            <p class="auth-message auth-message-error" role="alert"><?= htmlspecialchars($mensagem_cadastro, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <form action="login.php#cadastro" method="POST">
            <input type="hidden" name="acao" value="cadastro">

            <div class="form-group">
              <label for="register-name">Nome completo</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <input id="register-name" type="text" name="nome" placeholder="Seu nome" autocomplete="name" required>
              </div>
            </div>

            <div class="form-group">
              <label for="register-email">E-mail</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="register-email" type="email" name="email" placeholder="seu@email.com" autocomplete="email" required>
              </div>
            </div>

            <div class="form-group">
              <label for="register-password">Senha</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="register-password" type="password" name="senha" placeholder="Crie uma senha" autocomplete="new-password" required>
              </div>
            </div>

            <button type="submit" class="btn-submit">Criar minha conta <i class="fa-solid fa-check"></i></button>
          </form>
        </div>
      </div>
    </div>

    <button class="auth-theme-btn" type="button" onclick="toggleTheme()" aria-label="Alternar tema" title="Alternar tema">
      <i class="fa-solid fa-moon" aria-hidden="true"></i>
    </button>
  </div>

  <script>
    function updateThemeIcon() {
      const icon = document.querySelector('.auth-theme-btn i');
      const button = document.querySelector('.auth-theme-btn');

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
      } catch (erro) {}

      updateThemeIcon();
    }

    function toggleAuth(event) {
      if (event) event.preventDefault();

      const login = document.getElementById('login-box');
      const register = document.getElementById('register-box');
      const mostrarCadastro = login.style.display !== 'none';

      login.style.display = mostrarCadastro ? 'none' : 'block';
      register.style.display = mostrarCadastro ? 'block' : 'none';

      if (mostrarCadastro) {
        history.replaceState(null, '', '#cadastro');
      } else {
        history.replaceState(null, '', location.pathname);
      }
    }

    // Abre o cadastro quando o endereço termina em #cadastro ou quando
    // o PHP devolve a página após um erro de cadastro.
    if (window.location.hash === '#cadastro') {
      document.getElementById('login-box').style.display = 'none';
      document.getElementById('register-box').style.display = 'block';
    }

    updateThemeIcon();
  </script>
</body>
</html>
