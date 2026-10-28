<?php
$servidor = 'localhost';
$usuario = 'root';
$senha = 'LorenaMath6';
$banco = 'bd_nous';

$conn = new mysqli($servidor, $usuario, $senha, $banco);
$conn->set_charset('utf8mb4');
