<?php
$qfiltradas=[];
$busca = isset($_GET["materia"]) || isset($_GET["ano"]);

// NOVO: valores dos filtros definidos antes do if, para os selects poderem
// continuar marcados mesmo na primeira visita (sem aviso de variável inexistente)
$materia = $_GET["materia"] ?? "";
$ano = $_GET["ano"] ?? "";

if ($busca) {
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

function enunciado(string $enunciado): string
{
    $partes = preg_split(
        '/(!\[.*?\]\(.*?\))/',
        $enunciado,
        -1,
        PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
    );
    $html = "";
    foreach ($partes as $parte) {
        if (preg_match('/!\[(.*?)\]\((.*?)\)/', $parte, $resultado)) {
            $alt = htmlspecialchars($resultado[1], ENT_QUOTES, 'UTF-8');
            $src = htmlspecialchars($resultado[2], ENT_QUOTES, 'UTF-8');
            $html .= '<img src="' . $src . '" alt="' . $alt . '">' . "\n";
        } else {
            $texto = htmlspecialchars($parte, ENT_QUOTES, 'UTF-8');
            $html .= "<p>{$texto}</p>\n";
        }
    }

    return $html;
}


// MUDOU: agora recebe também a letra escolhida e a correta,
// para colocar as classes "certa" / "errada" (que o CSS pinta de verde / vermelho)
function alternativas(array $alternativas, string $escolhida = "", string $correta = ""): string
{
    $html = "";

    foreach ($alternativas as $alternativa) {
        $letra = htmlspecialchars($alternativa["letter"] ?? "", ENT_QUOTES, 'UTF-8');
        $texto = htmlspecialchars($alternativa["text"] ?? "", ENT_QUOTES, 'UTF-8');
        $file  = $alternativa["file"] ?? null;

        // classe da alternativa: só muda depois que a questão foi respondida
        $classe = "alternativa";
        if ($escolhida != "") {
            if ($letra == $correta) {
                $classe .= " certa";
            } else if ($letra == $escolhida) {
                $classe .= " errada";
            }
        }

        $html .= '<button type="submit" name="letra" value="' . $letra . '" class="' . $classe . '"';
        if ($escolhida != "") {
            $html .= ' disabled';   // depois de responder, não deixa clicar de novo
        }
        $html .= '>';

        $html .= '<span class="letra">' . $letra . '</span>';
        $html .= '<span class="texto">' . $texto;

        if ($file !== null) {
            $src = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
            $html .= '<img src="' . $src . '" alt="imagem da alternativa ' . $letra . '">';
        }

        $html .= '</span></button>' . "\n";
    }

    return $html;
}

function conferir($letraEscolhida, $letraCorreta)
{
    if ($letraEscolhida == $letraCorreta) {
        return "Resposta correta! A alternativa certa é a " . $letraCorreta . ".";
    } else {
        return "Resposta incorreta. A alternativa correta era a " . $letraCorreta . ".";
    }
}

// NOVO: nomes bonitos das áreas, para mostrar no cartão da questão
$areas = [
    "ciencias-natureza" => "Ciências da Natureza",
    "ciencias-humanas"  => "Ciências Humanas",
    "linguagens"        => "Linguagens",
    "matematica"        => "Matemática",
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nous · Banco de Questões ENEM</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="q_enem_tela.css">
</head>
<body>

    <!-- ===================== NAVBAR (igual às outras páginas) ===================== -->
    <nav class="navbar">
        <a href="../inicio.html" class="navbar-logo">
            <div class="navbar-logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <div class="navbar-logo-text"><strong>Nous</strong><span>portal de estudos</span></div>
        </a>

        <div class="navbar-nav">
            <a href="../index.html">Início</a>
            <a href="../progresso.html">Progresso</a>
            <a href="../tarefas.html">Tarefas</a>
            <a href="../materias.html" class="active">Matérias</a>
            <a href="../agenda.html">Agenda</a>
            <a href="../avisos.html">Avisos</a>
        </div>

        <div class="navbar-actions">
            <a class="nav-link">Turmas</a>
            <a class="nav-link">Entrar</a>
            <a class="btn-cadastrar">Cadastrar</a>
            <button class="globe-btn" type="button"><i class="fa-solid fa-globe"></i></button>
        </div>
    </nav>

    <div class="main-wrapper">

        <!-- ===================== SIDEBAR ===================== -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div>
                    <strong>Nous</strong>
                    <span>portal de estudos</span>
                </div>
            </div>
            <nav class="menu">
                <a href="../inicio.html"><i class="fa-solid fa-house"></i><span>Início</span></a>
                <a href="../progresso.html"><i class="fa-solid fa-chart-column"></i><span>Progresso</span></a>
                <a href="../tarefas.html"><i class="fa-solid fa-pen-to-square"></i><span>Tarefas</span></a>
                <a href="../materias.html" class="active"><i class="fa-solid fa-book"></i><span>Matérias</span></a>
                <a href="../agenda.html"><i class="fa-solid fa-calendar-days"></i><span>Agenda</span></a>
                <a href="../avisos.html"><i class="fa-solid fa-bell"></i><span>Avisos</span></a>
            </nav>
            <nav class="menu logout">
                <a><i class="fa-solid fa-right-from-bracket"></i><span>Sair</span></a>
            </nav>
        </aside>

        <!-- ===================== CONTEÚDO ===================== -->
        <main class="content">
        <div class="conteudo-questoes">

            <div class="page-header">
                <h1><i class="fa-solid fa-file-pen"></i> Banco de Questões ENEM</h1>
                <p>Selecione a área do conhecimento e o ano da prova.</p>
            </div>

            <!-- FILTROS (GET) -->
            <form id="formSelect" class="filtros" method="GET">
                <div class="filtros-titulo"><i class="fa-solid fa-filter"></i> Filtrar questões</div>

                <div class="filtros-grid">
                    <div class="campo">
                        <label for="materia">Área do conhecimento</label>
                        <select name="materia" id="materia">
                            <option value="">Todas as áreas</option>
                            <?php foreach ($areas as $valor => $nome) { ?>
                                <option value="<?= $valor ?>" <?= $materia == $valor ? "selected" : "" ?>><?= $nome ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="ano">Ano da prova</label>
                        <select name="ano" id="ano">
                            <option value="">Todos os anos</option>
                            <?php for ($a = 2026; $a >= 2026; $a--) { ?>
                                <option value="<?= $a ?>" <?= $ano == $a ? "selected" : "" ?>><?= $a ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="filtros-acoes">
                    <button type="submit" class="btn btn-primario"><i class="fa-solid fa-magnifying-glass"></i> Buscar questões</button>
                    <a href="ler_json_questoes.php" class="btn btn-secundario"><i class="fa-solid fa-rotate-left"></i> Limpar filtros</a>
                </div>
            </form>

            <!-- ===================== LISTA DE QUESTÕES ===================== -->
            <section id="listaQuestoes">

<?php if (!$busca) { ?>

                <div class="empty-state">
                    <div class="icone"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h3>Pronto para estudar?</h3>
                    <p>Escolha a área e o ano da prova e clique em "Buscar questões" para começar a resolver.</p>
                </div>

<?php } else if (count($qfiltradas) == 0) { ?>

                <div class="empty-state">
                    <div class="icone"><i class="fa-regular fa-face-frown"></i></div>
                    <h3>Nenhuma questão encontrada</h3>
                    <p>Tente outra combinação de área e ano, ou limpe os filtros.</p>
                </div>

<?php } else { ?>

                <p class="contagem"><strong><?= count($qfiltradas) ?></strong> questões encontradas</p>

<?php
$i=1;
foreach($qfiltradas as $questao){
    $id = $questao["id"];
    $correta = $questao["correctAlternative"];

    // letra que a pessoa clicou NESTA questão (vazio se ainda não respondeu)
    $escolhida = "";
    if (isset($_POST["id"]) && isset($_POST["letra"]) && $_POST["id"] == $id) {
        $escolhida = $_POST["letra"];
    }

    $nomeArea = $areas[$questao["discipline"]] ?? $questao["discipline"];
?>
                <article class="cartao" id="<?= $id ?>">

                    <header class="q-topo">
                        <span class="q-num"><?= $i ?></span>
                        <div class="q-titulo">
                            <h3><?= htmlspecialchars($questao['title']) ?></h3>
                            <div class="q-chips">
                                <span class="chip indigo"><i class="fa-regular fa-calendar"></i> <?= $questao['year'] ?></span>
                                <span class="chip"><i class="fa-solid fa-book"></i> <?= $nomeArea ?></span>
                                <?php if (!empty($questao['language'])) { ?>
                                    <span class="chip"><i class="fa-solid fa-language"></i> <?= htmlspecialchars($questao['language']) ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </header>

                    <div class="enunciado"><?= enunciado($questao["context"] ?? "") ?></div>

                    <p class="intro"><?= htmlspecialchars($questao['alternativesIntroduction'] ?? "") ?></p>

                    <!-- action="#id": mantém a URL (com os filtros) e volta para esta questão -->
                    <form method="post" action="#<?= $id ?>" class="alternativas <?= $escolhida != "" ? "respondida" : "" ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <?= alternativas($questao["alternatives"], $escolhida, $correta) ?>
                    </form>

                    <?php if ($escolhida != "") {
                        $acertou = ($escolhida == $correta);
                    ?>
                        <div class="resultado <?= $acertou ? "ok" : "erro" ?>">
                            <i class="fa-solid <?= $acertou ? "fa-circle-check" : "fa-circle-xmark" ?>"></i>
                            <span><?= conferir($escolhida, $correta) ?></span>
                        </div>
                    <?php } ?>

                </article>
<?php
    $i++;
}
?>

<?php } ?>
            </section>

            <footer class="footer">
                <span>Nous · seu espaço para estudar</span>
                <span>v3.0 · 2026</span>
            </footer>

        </div>
        </main>
    </div>

    <button class="theme-btn" type="button" title="Alternar tema"><i class="fa-solid fa-moon"></i></button>

    <script>
        // Modo escuro (o CSS já tem as cores: é só ligar/desligar a classe "dark" no body)
        const botaoTema = document.querySelector(".theme-btn");
        if (localStorage.getItem("tema") === "dark") {
            document.body.classList.add("dark");
        }
        botaoTema.addEventListener("click", function () {
            document.body.classList.toggle("dark");
            localStorage.setItem("tema", document.body.classList.contains("dark") ? "dark" : "light");
        });
    </script>
</body>
</html>
