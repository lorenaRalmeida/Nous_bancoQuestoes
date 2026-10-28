<?php
session_start();
include 'conexao.php';

if (isset($_GET['id']) && isset($_SESSION['usuario_id'])) {
    $id = intval($_GET['id']);
    $usuario_id = $_SESSION['usuario_id'];

    $stmt = $conn->prepare("DELETE FROM tarefas WHERE id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $id, $usuario_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: index.php");
exit;
?>
