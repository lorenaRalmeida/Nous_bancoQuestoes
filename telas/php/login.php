<?php
// pra não dar problema de sessão
include(__DIR__.'/../banco/conexao.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensagem_login = "";
$mensagem_cadastro = "";
$tipo_mensagem = "";
$mostrar_cadastro = false; 

// se o cadastro ocorreu com sucesso, força a exibição da tela de login com a mensagem verde
if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'sucesso') {
    $mensagem_login = "Conta criada com sucesso! Faça login abaixo com seu e-mail e senha.";
    $tipo_mensagem = "sucesso";
    $mostrar_cadastro = false;
}

// formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($email) && !empty($senha)) {
        $stmt = $conn->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado && $resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();

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
        }
    } else {
        $mensagem_login = "Preencha todos os campos!";
    }
}

// processa o formulário de cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastro') {
    $mostrar_cadastro = true; // se der erro, mantém na aba de cadastro
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!empty($nome) && !empty($email) && !empty($senha)) {
        $stmtCheck = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
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

                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmtInsert = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");

                if ($stmtInsert) {
                    $stmtInsert->bind_param("sss", $nome, $email, $senhaHash);

                    if ($stmtInsert->execute()) {
                        $stmtInsert->close();
                        // redireciona para a tela de login com sucesso
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
  <link rel="stylesheet" href="../css/style.css">
<script src="../js/tema.js" defer></script><script src="../js/login.js" defer></script></head>
<body class="auth-body">
  

  <div class="auth-wrapper">
    <div class="auth-container">

      <div class="auth-banner">
        <div class="banner-logo">
          <img src="../../imagens/logo-nous-banner-claro.png" alt="Logo Nous" class="logo-img-banner logo-for-light">
          <img src="../../imagens/logo-nous-banner-escuro.png" alt="Logo Nous" class="logo-img-banner logo-for-dark">
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
          <img src="../../imagens/logo-nous-claro.png" alt="Logo Nous" class="logo-img logo-for-light">
          <img src="../../imagens/logo-nous-escuro.png" alt="Logo Nous" class="logo-img logo-for-dark">
        </div>

        <!-- tela de login -->
        <div id="login-box" style="<?= $mostrar_cadastro ? 'display:none;' : 'display:block;' ?>">
          <div class="auth-header">
            <h1>Entrar na conta</h1>
            <p>Ainda não tem conta? <a href="#cadastro" data-auth-toggle>Cadastre-se grátis</a></p>
          </div>

          <?php if ($mensagem_login !== '' && $tipo_mensagem !== 'sucesso'): ?>
            <p class="auth-message auth-message-error" role="alert" style="color: #e74c3c; background: rgba(231, 76, 60, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 14px;"><?= htmlspecialchars($mensagem_login, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <?php if ($mensagem_login !== '' && $tipo_mensagem === 'sucesso'): ?>
            <p class="auth-message auth-message-success" role="status" style="color: #27ae60; background: rgba(39, 174, 96, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 14px;"><?= htmlspecialchars($mensagem_login, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <form action="login.php" method="POST" autocomplete="off">
            <input type="hidden" name="acao" value="login">

            <div class="form-group">
              <label for="login-email">E-mail</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="login-email" type="email" name="email" placeholder="seu@email.com" autocomplete="off" required>
              </div>
            </div>

            <div class="form-group">
              <label for="login-password">Senha</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="login-password" type="password" name="senha" placeholder="Sua senha" autocomplete="off" required>
              </div>
            </div>

            <button type="submit" class="btn-submit">Entrar <i class="fa-solid fa-arrow-right"></i></button>
          </form>

          
        </div>

        <!-- tela de cadastro -->
        <div id="register-box" style="<?= $mostrar_cadastro ? 'display:block;' : 'display:none;' ?>">
          <div class="auth-header">
            <h1>Criar conta</h1>
            <p>Já tem uma conta? <a href="login.php" data-auth-toggle>Faça login</a></p>
          </div>

          <?php if ($mensagem_cadastro !== '' && $tipo_mensagem === 'erro'): ?>
            <p class="auth-message auth-message-error" role="alert" style="color: #e74c3c; background: rgba(231, 76, 60, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; font-size: 14px;"><?= htmlspecialchars($mensagem_cadastro, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <form action="login.php" method="POST" autocomplete="off">
            <input type="hidden" name="acao" value="cadastro">

            <div class="form-group">
              <label for="register-name">Nome completo</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <input id="register-name" type="text" name="nome" placeholder="Seu nome" autocomplete="off" required>
              </div>
            </div>

            <div class="form-group">
              <label for="register-email">E-mail</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="register-email" type="email" name="email" placeholder="seu@email.com" autocomplete="off" required>
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

    <button class="auth-theme-btn" type="button" data-theme-toggle aria-label="Alternar tema" title="Alternar tema">
      <i class="fa-solid fa-moon" aria-hidden="true"></i>
    </button>
  </div>

  
</body>
</html>
