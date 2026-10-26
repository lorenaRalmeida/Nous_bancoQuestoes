<?php
$materia = $_POST["materia"]??"";
$ano = $_POST["ano"]??"";
require_once  __DIR__ ."/ler_json_questoes.php";
console.log($questoes);
// filtros de questões
$qfiltradas = array_filter(
    $questoes,
    function ($questao) use ($materia,$ano) {
        if($materia=="" && $ano==""){
            return true;
        }else if($materia!="" && $ano!=""){
            return
                $questao["discipline"] == $materia &&
                $questao["year"] == $ano;
        }else if($materia!=""){
            return $questao["discipline"] == $materia;
        }else{
            return $questao["year"] == $ano;
        }
    }
);

header("Content-Type: application/json; charset=UTF-8");
echo json_encode(
    array_values($qfiltradas),
    JSON_UNESCAPED_UNICODE
);
exit;
?>