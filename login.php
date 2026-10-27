<?php
include('conexao.php');
session_start();

$mensagem_login = "";
$mensagem_cadastro = "";
$tipo_mensagem = "";

// Se o usuário veio de um cadastro com sucesso via URL
if (isset($_GET['cadastro']) && $_GET['cadastro'] == 'sucesso') {
    $mensagem_cadastro = "Conta criada com sucesso! Faça login abaixo.";
    $tipo_mensagem = "sucesso";
}

// Processa o envio do formulário de LOGIN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'login') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($email) && !empty($senha)) {
        $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            
            // Valida a senha criptografada
            if (password_verify($senha, $usuario['senha'])) {
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
        $mensagem_login = "Preencha todos os campos!";
    }
}

// Processa o envio do formulário de CADASTRO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'cadastro') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($nome) && !empty($email) && !empty($senha)) {
        // Verifica se o e-mail já existe
        $sqlCheck = "SELECT id FROM usuarios WHERE email = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $mensagem_cadastro = "Este e-mail já está cadastrado!";
            $tipo_mensagem = "erro";
        } else {
            $stmtCheck->close();
            
            // Criptografa a senha com segurança
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sqlInsert = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("sss", $nome, $email, $senhaHash);

            if ($stmtInsert->execute()) {
                header("Location: login.php?cadastro=sucesso");
                exit;
            } else {
                $mensagem_cadastro = "Erro ao cadastrar. Tente novamente.";
                $tipo_mensagem = "erro";
            }
            $stmtInsert->close();
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nous · Acesso</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

  <div class="auth-wrapper">
    <div class="auth-container">
      
      <div class="auth-banner">
        <h2>Bem-vindo ao futuro dos seus estudos.</h2>
        <p>O Nous centraliza sua rotina, acompanha seu progresso e te ajuda a alcançar a aprovação.</p>
        <div class="auth-banner-features">
          <div class="auth-banner-feature"><i class="fa-solid fa-check"></i> Mais de 10.000 questões</div>
          <div class="auth-banner-feature"><i class="fa-solid fa-check"></i> Simulados no formato oficial</div>
          <div class="auth-banner-feature"><i class="fa-solid fa-check"></i> Estatísticas em tempo real</div>
        </div>
      </div>

      <div class="auth-form-side">
        
        <!-- TELA DE LOGIN -->
        <div id="login-box">
          <div class="auth-header">
            <h1>Entrar na conta</h1>
            <p>Ainda não tem conta? <a onclick="toggleAuth()" style="cursor: pointer; color: var(--roxo);">Cadastre-se grátis</a></p>
          </div>

          <?php if (!empty($mensagem_login)): ?>
            <p style="color: #e74c3c; font-size: 14px; margin-bottom: 10px; font-weight: 600;"><?php echo $mensagem_login; ?></p>
          <?php endif; ?>

          <?php if (!empty($mensagem_cadastro) && $tipo_mensagem == 'sucesso'): ?>
            <p style="color: #27ae60; font-size: 14px; margin-bottom: 10px; font-weight: 600;"><?php echo $mensagem_cadastro; ?></p>
          <?php endif; ?>

          <form action="login.php" method="POST">
            <input type="hidden" name="acao" value="login">
            <div class="form-group">
              <label>E-mail</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" name="email" placeholder="seu@email.com" autocomplete="off" required>
              </div>
            </div>
            <div class="form-group">
              <label>Senha</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="senha" placeholder="Sua senha" autocomplete="off" required>
              </div>
            </div>
            <button type="submit" class="btn-submit">Entrar <i class="fa-solid fa-arrow-right"></i></button>
          </form>
          <div class="auth-divider">OU</div>
          <button class="btn-social"><i class="fa-brands fa-google" style="color:#DB4437"></i> Continuar com Google</button>
        </div>

        <!-- TELA DE CADASTRO (Começa oculta) -->
        <div id="register-box" style="display: none;">
          <div class="auth-header">
            <h1>Criar conta</h1>
            <p>Já tem uma conta? <a onclick="toggleAuth()" style="cursor: pointer; color: var(--roxo);">Faça login</a></p>
          </div>

          <?php if (!empty($mensagem_cadastro) && $tipo_mensagem == 'erro'): ?>
            <p style="color: #e74c3c; font-size: 14px; margin-bottom: 10px; font-weight: 600;"><?php echo $mensagem_cadastro; ?></p>
          <?php endif; ?>

          <form action="login.php" method="POST">
            <input type="hidden" name="acao" value="cadastro">
            <div class="form-group">
              <label>Nome completo</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-user"></i>
                <input type="text" name="nome" placeholder="Seu nome" required>
              </div>
            </div>
            <div class="form-group">
              <label>E-mail</label>
              <div class="form-input-wrap">
                <i class="fa-regular fa-envelope"></i>
                <input type="email" name="email" placeholder="seu@email.com" required>
              </div>
            </div>
            <div class="form-group">
              <label>Senha</label>
              <div class="form-input-wrap">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="senha" placeholder="Crie uma senha" required>
              </div>
            </div>
            <button type="submit" class="btn-submit">Criar minha conta <p style="display:inline;"><i class="fa-solid fa-check"></i></p></button>
          </form>
        </div>

      </div>
    </div>
    
  </div>

  <script>
    function toggleAuth() {
      const login = document.getElementById('login-box');
      const register = document.getElementById('register-box');
      if (login.style.display === 'none') {
        login.style.display = 'block';
        register.style.display = 'none';
      } else {
        login.style.display = 'none';
        register.style.display = 'block';
      }
    }

    window.onload = function() {
      // Se o usuário veio do botão Sair, limpa os inputs de login para facilitar a troca
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('logout')) {
        const emailInput = document.querySelector('#login-box input[name="email"]');
        const senhaInput = document.querySelector('#login-box input[name="senha"]');
        if (emailInput) emailInput.value = '';
        if (senhaInput) senhaInput.value = '';
      }

      if (window.location.hash === '#cadastro') {
        document.getElementById('login-box').style.display = 'none';
        document.getElementById('register-box').style.display = 'block';
      }
    }
  </script>
</body>
</html>
