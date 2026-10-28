<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_id = $_SESSION['usuario_id'];
    $titulo = trim($_POST['titulo']);

    if (!empty($titulo)) {
        $stmt = $conn->prepare("INSERT INTO tarefas (usuario_id, titulo) VALUES (?, ?)");
        $stmt->bind_param("is", $usuario_id, $titulo);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php");
exit;
?>
