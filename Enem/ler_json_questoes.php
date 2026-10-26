<?php
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $pastas = __DIR__."/q_enem_json";
    $arquivos = glob($pastas."/*/questions/*/details.json");
    $questoes = [];
foreach($arquivos as $a){
    $q_text = file_get_contents($a);
    $q_json = json_decode($q_text,true);

    if(!is_array($q_json)){
        continue;
        }
        $disciplina = $q_json["discipline"];
        $ano = $q_json["year"];
        $indice = $q_json["index"];
        $idioma = $q_json["language"] ?? null;
        if ($disciplina == "linguagens" && $idioma != null) {
            $q_json["id"] = "q_" . $ano . "_" . $disciplina . "_" . $idioma . "_" . $indice;
        } else {
            $q_json["id"] = "q_" . $ano . "_" . $disciplina . "_" . $indice;
        }
        $questoes[] = $q_json;
}
    $materia = $_GET["materia"]??"";
    $ano = $_GET["ano"]??"";
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
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Questões ENEM</title>
</head>
<body>

    <h1>Banco de Questões ENEM</h1>
    <p>Selecione a área do conhecimento e o ano da prova.</p>

    <form id="formSelect" method="GET">

        <label for="materia">Área do conhecimento:</label>
        <select name="materia" id="materia">
            <option value="">Todas as áreas</option>
            <option value="ciencias-natureza">Ciências da Natureza</option>
            <option value="ciencias-humanas">Ciências Humanas</option>
            <option value="linguagens">Linguagens</option>
            <option value="matematica">Matemática</option>
        </select>

        <br><br>

        <label for="ano">Ano da prova:</label>
        <select name="ano" id="ano">
            <option value="">Todos os anos</option>
            <option value="2023">2023</option>
            <option value="2022">2022</option>
            <option value="2021">2021</option>
            <option value="2020">2020</option>
            <option value="2019">2019</option>
            <option value="2018">2018</option>
            <option value="2017">2017</option>
            <option value="2016">2016</option>
            <option value="2015">2015</option>
            <option value="2014">2014</option>
            <option value="2013">2013</option>
            <option value="2012">2012</option>
            <option value="2011">2011</option>
            <option value="2010">2010</option>
            <option value="2009">2009</option>
        </select>

        <br><br>

        <button type="submit">Buscar questões</button>

    </form>

    <main id="listaQuestoes">
<?php
let $i=1;
foreach($qfiltradas as $questao){
?><div class="cartao">
    <h2><?php echo $i; ?></h2>
    <h3><?php echo $questao['title'];?></h3>
    
</div>
}
?>
    </main>
</body>
</html>
