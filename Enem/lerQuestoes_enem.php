<?php
$materia = $_POST["materia"]??"";
$ano = $_POST["ano"]??"";
$pastas = __DIR__."/q_enem_json";
$arquivos = glob($pastas."/*/questions/*/details.json");
$questoes = [];
foreach($arquivos as $a){
    $q_text = file_get_contents($a);
    $q_json = json_decode($q_text,true);

    if(is_array($q_json)){
        $questoes[] = $q_json;
    }
}

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