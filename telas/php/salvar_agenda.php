<?php
session_start();
include __DIR__.'/../banco/conexao.php';

// proteção: só deixa salvar se estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $titulo = trim($_POST['titulo']);
    $data_compromisso = trim($_POST['data_compromisso']);

    if (!empty($titulo) && !empty($data_compromisso)) {
        // prepared statement para segurança contra sql injection
        $stmt = $conn->prepare("INSERT INTO compromissos (usuario_id, titulo, data_compromisso) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $usuario_id, $titulo, $data_compromisso);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php");
exit;
?>
