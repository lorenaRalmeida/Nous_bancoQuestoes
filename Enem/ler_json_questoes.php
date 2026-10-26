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
        $disciplina = $questao["discipline"];
        $ano = $questao["year"];
        $indice = $questao["index"];
        $idioma = $questao["language"] ?? null;
        if ($disciplina == "linguagens" && $idioma != null) {
            $questao["id"] = "q_" . $ano . "_" . $disciplina . "_" . $idioma . "_" . $indice;
        } else {
            $questao["id"] = "q_" . $ano . "_" . $disciplina . "_" . $indice;
        }
    }
}
?>