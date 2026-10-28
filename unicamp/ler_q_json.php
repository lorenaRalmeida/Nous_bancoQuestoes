<?php
/** Leitor UNICAMP baseado no fluxo de Enem/ler_json_questoes.php. PHP 7.4+.
 * Dados: __DIR__/ANO/questions/questao-X/question.json. Não altera os JSONs.
 * Respostas são guardadas na sessão do navegador; não requer banco de dados.
 */
declare(strict_types=1);
session_start();
header('Content-Type: text/html; charset=UTF-8');

function nc_h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nc_input(array $values, string $key): string {
    return isset($values[$key]) && is_string($values[$key]) ? trim($values[$key]) : '';
}
function nc_text($value): string { return is_string($value) ? $value : ''; }
function nc_url(array $filters, int $page = 1, string $anchor = ''): string {
    $query = array_filter($filters, static function ($v) { return $v !== ''; });
    $query['pagina'] = max(1, $page);
    return 'ler_q_json.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
        . ($anchor !== '' ? '#' . rawurlencode($anchor) : '');
}
/** Só publica imagens existentes dentro da pasta local desta questão. */
function nc_image(string $folder, $name): ?string {
    if (!is_string($name) || $name === '' || strpos($name, "\0") !== false) { return null; }
    $name = str_replace('\\', '/', $name);
    if (preg_match('~^(?:[a-z][a-z0-9+.-]*:|/)|(?:^|/)\.\.(?:/|$)~i', $name)) { return null; }
    $base = realpath($folder);
    $root = realpath(__DIR__);
    $file = realpath($folder . DIRECTORY_SEPARATOR . $name);
    if ($base === false || $root === false || $file === false || !is_file($file)) { return null; }
    $base = str_replace('\\', '/', $base);
    $root = str_replace('\\', '/', $root);
    $file = str_replace('\\', '/', $file);
    if (strpos($file, $base . '/') !== 0 || strpos($file, $root . '/') !== 0) { return null; }
    if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) { return null; }
    return implode('/', array_map('rawurlencode', explode('/', substr($file, strlen($root) + 1))));
}
function nc_alt_files(array $question, array $alt): array {
    $letter = nc_text($alt['letter'] ?? '');
    $files = $question['metadata']['alternativeVisualFiles'][$letter] ?? [];
    if (!is_array($files)) { $files = []; }
    if (is_string($alt['file'] ?? null)) { array_unshift($files, $alt['file']); }
    return array_values(array_unique(array_filter($files, 'is_string')));
}

$questions = [];
$invalidFiles = 0;
foreach (glob(__DIR__ . '/*/questions/*/question.json') ?: [] as $file) {
    $raw = @file_get_contents($file);
    $q = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($q) || !is_numeric($q['year'] ?? null) || !is_numeric($q['index'] ?? null)
        || !is_array($q['alternatives'] ?? null) || !is_string($q['context'] ?? null)) {
        $invalidFiles++;
        continue;
    }
    $q['year'] = (int) $q['year'];
    $q['index'] = (int) $q['index'];
    $q['metadata'] = is_array($q['metadata'] ?? null) ? $q['metadata'] : [];
    $q['discipline'] = nc_text($q['discipline'] ?? null);
    $version = nc_text($q['metadata']['version'] ?? '');
    $id = 'unicamp_' . $q['year'] . '_' . $q['index'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $version);
    $q['_id'] = $id;
    $q['_folder'] = dirname($file);
    $questions[$id] = $q;
}
uasort($questions, static function ($a, $b) {
    return ($b['year'] <=> $a['year']) ?: ($a['index'] <=> $b['index']) ?: strcmp($a['_id'], $b['_id']);
});
$years = [];
$subjects = [];
foreach ($questions as $q) {
    $years[$q['year']] = $q['year'];
    $subjects[$q['discipline'] !== '' ? $q['discipline'] : 'sem-classificacao'] =
        $q['discipline'] !== '' ? $q['discipline'] : 'Sem classificação';
}
krsort($years);
asort($subjects);
$filters = [
    'ano' => nc_input($_GET, 'ano'), 'disciplina' => nc_input($_GET, 'disciplina'),
];
$busca = nc_input($_GET, 'buscar') === '1' || $filters['ano'] !== '' || $filters['disciplina'] !== '';
if ($busca) { $filters['buscar'] = '1'; }
$page = max(1, (int) nc_input($_GET, 'pagina'));
$error = '';
if (!isset($_SESSION['nous_unicamp_csrf']) || !is_string($_SESSION['nous_unicamp_csrf'])) {
    $_SESSION['nous_unicamp_csrf'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['nous_unicamp_answers']) || !is_array($_SESSION['nous_unicamp_answers'])) {
    $_SESSION['nous_unicamp_answers'] = [];
}
$csrf = $_SESSION['nous_unicamp_csrf'];
$async = nc_input($_POST, 'ajax') === '1';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $id = nc_input($_POST, 'id');
    $letter = nc_input($_POST, 'letra');
    $action = nc_input($_POST, 'acao');
    $validLetters = isset($questions[$id]) ? array_column($questions[$id]['alternatives'], 'letter') : [];
    if (!hash_equals($csrf, nc_input($_POST, 'csrf'))) {
        $error = 'A sessão do formulário expirou. Recarregue a página e tente novamente.';
        http_response_code(400);
    } elseif (!isset($questions[$id])) {
        $error = 'Questão não encontrada.';
        http_response_code(400);
    } elseif (isset($_SESSION['nous_unicamp_answers'][$id])) {
        $error = 'Esta questão já foi respondida. A tentativa é única nesta sessão.';
        http_response_code(409);
    } elseif ($action === 'responder' && in_array($letter, $validLetters, true)
        && preg_match('/^[A-E]$/', $letter)
        && in_array($questions[$id]['correctAlternative'] ?? null, $validLetters, true)) {
        $_SESSION['nous_unicamp_answers'][$id] = $letter;
        if (!$async) {
            header('Location: ' . nc_url($filters, $page), true, 303);
            exit;
        }
    } else {
        $error = 'Não foi possível registrar essa resposta.';
        http_response_code(400);
    }
}

$filtered = $busca ? array_values(array_filter($questions, static function ($q) use ($filters) {
    if ($filters['ano'] !== '' && (string) $q['year'] !== $filters['ano']) { return false; }
    $subject = $q['discipline'] !== '' ? $q['discipline'] : 'sem-classificacao';
    if ($filters['disciplina'] !== '' && $subject !== $filters['disciplina']) { return false; }
    return true;
})) : [];
$perPage = 5;
$total = count($filtered);
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$visible = array_slice($filtered, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nous | Questões UNICAMP</title>
    <link rel="stylesheet" href="style_unicamp.css">
</head>
<body>
<a class="pular" href="#conteudo">Ir para as questões</a>
<header class="navbar">
    <a class="marca" href="../index.php"><span class="marca-icone" aria-hidden="true">N</span><span><strong>Nous</strong><small>portal de estudos</small></span></a>
    <nav aria-label="Navegação principal"><a href="../index.php">Início</a><a href="../Enem/ler_json_questoes.php">ENEM</a><a href="ler_q_json.php" aria-current="page">UNICAMP</a></nav>
    <button id="tema" type="button" class="botao-tema" aria-pressed="false">Tema escuro</button>
</header>
<div class="layout">
    <aside class="sidebar">
        <p class="sobretitulo">SEU ESPAÇO DE ESTUDO</p>
        <h2>Um passo de<br>cada vez.</h2>
        <p>Pratique com questões da primeira fase da UNICAMP.</p>
        <nav aria-label="Bancos de questões"><a href="../Enem/ler_json_questoes.php">Questões ENEM</a><a href="ler_q_json.php" aria-current="page">Questões UNICAMP <span>→</span></a></nav>
        <small>Uma tentativa por questão nesta sessão do navegador.</small>
    </aside>
    <main id="conteudo" class="content">
        <div class="cabecalho"><p class="sobretitulo">BANCO DE QUESTÕES · 1ª FASE</p><h1>Seu próximo acerto<br>começa aqui.</h1><p>Explore as provas da UNICAMP e confira sua resposta a cada questão.</p></div>
        <form class="filtros" method="get" action="ler_q_json.php">
            <input type="hidden" name="buscar" value="1">
            <div class="secao-titulo"><h2>Encontre uma questão</h2><span><?= count($questions) ?> disponíveis</span></div>
            <div class="filtros-grid">
                <label>Ano da prova<select name="ano"><option value="">Todos os anos</option><?php foreach ($years as $year): ?><option value="<?= $year ?>" <?= $filters['ano'] === (string) $year ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?></select></label>
                <label>Disciplina<select name="disciplina"><option value="">Todas as disciplinas</option><?php foreach ($subjects as $value => $label): ?><option value="<?= nc_h($value) ?>" <?= $filters['disciplina'] === (string) $value ? 'selected' : '' ?>><?= nc_h($label) ?></option><?php endforeach; ?></select></label>
            </div>
            <div class="acoes"><button class="btn primario" type="submit">Buscar questões <span aria-hidden="true">→</span></button><a class="btn secundario" href="ler_q_json.php">Limpar filtros</a></div>
            <p class="ajuda">Cinco questões por página. Cada questão pode ser respondida uma única vez nesta sessão.</p>
        </form>
        <?php if ($error !== ''): ?><div class="aviso" role="alert"><?= nc_h($error) ?></div><?php endif; ?>
        <?php if ($invalidFiles > 0): ?><div class="aviso" role="status"><?= $invalidFiles ?> arquivo(s) com dados inválidos não foram carregados.</div><?php endif; ?>
        <?php if (!$busca): ?>
            <div class="vazio empty-state"><h3>Pronto para estudar?</h3><p>Selecione o ano e a disciplina e clique em “Buscar questões” para começar.</p></div>
        <?php else: ?>
        <div class="contagem"><h2>Questões UNICAMP</h2><span><?= $total ?> resultado<?= $total === 1 ? '' : 's' ?> · página <?= $page ?> de <?= $pages ?></span></div>
        <?php if ($total === 0): ?>
            <div class="vazio"><h3><?= count($questions) === 0 ? 'Os arquivos ainda não estão aqui' : 'Nenhuma questão encontrada' ?></h3><p><?= count($questions) === 0 ? 'Coloque as pastas 2023, 2024, 2025 e 2026 dentro da pasta unicamp, ao lado deste arquivo.' : 'Tente outro ano ou disciplina.' ?></p></div>
        <?php endif; ?>
        <?php foreach ($visible as $position => $q):
            $displayNumber = ($page - 1) * $perPage + $position + 1;
            $id = $q['_id']; $chosen = $_SESSION['nous_unicamp_answers'][$id] ?? '';
            $right = nc_text($q['correctAlternative'] ?? null);
            $cancelled = !empty($q['metadata']['cancelled']);
            $canAnswer = !$cancelled && in_array($right, array_column($q['alternatives'], 'letter'), true);
            $alternativeFiles = [];
            foreach ($q['alternatives'] as $alt) { $alternativeFiles = array_merge($alternativeFiles, nc_alt_files($q, $alt)); }
            $statementImages = []; $missingImages = false;
            foreach (is_array($q['files'] ?? null) ? $q['files'] : [] as $file) {
                if (!is_string($file) || in_array($file, $alternativeFiles, true) || preg_match('/^(?:alternativa-|recorte-)/', basename($file))) { continue; }
                $src = nc_image($q['_folder'], $file);
                if ($src !== null) { $statementImages[$src] = $src; } else { $missingImages = true; }
            }
        ?>
        <article class="cartao <?= $q['discipline'] === 'Matemática' ? 'matematica' : '' ?>" id="<?= nc_h($id) ?>">
            <header class="q-topo"><span class="q-num"><?= $displayNumber ?></span><div class="q-titulo"><p class="sobretitulo">UNICAMP · <?= $q['year'] ?></p><h3>Questão <?= $displayNumber ?></h3><div class="chips"><span>1ª fase</span><span>Caderno <?= nc_h($q['metadata']['version'] ?? '—') ?></span><span><?= nc_h($q['discipline'] !== '' ? $q['discipline'] : 'Sem classificação') ?></span></div></div></header>
            <div class="enunciado"><?= nc_h($q['context']) ?></div>
            <?php if ($statementImages): ?><div class="figuras" aria-label="Figuras do enunciado"><?php foreach ($statementImages as $src): ?><figure><img src="<?= nc_h($src) ?>" alt="Figura do enunciado da questão <?= $displayNumber ?>" loading="lazy" decoding="async"></figure><?php endforeach; ?></div><?php endif; ?>
            <?php if ($missingImages): ?><p class="aviso">Uma figura do enunciado não foi encontrada na pasta da questão.</p><?php endif; ?>
            <?php if (nc_text($q['alternativesIntroduction'] ?? null) !== ''): ?><p class="intro"><?= nc_h($q['alternativesIntroduction']) ?></p><?php endif; ?>
            <form method="post" action="<?= nc_h(nc_url($filters, $page)) ?>" class="alternativas <?= $chosen !== '' ? 'respondida' : '' ?>">
                <input type="hidden" name="id" value="<?= nc_h($id) ?>"><input type="hidden" name="csrf" value="<?= nc_h($csrf) ?>"><input type="hidden" name="acao" value="responder">
                <?php foreach ($q['alternatives'] as $alt):
                    $letter = nc_text($alt['letter'] ?? '');
                    $state = $chosen !== '' ? ($letter === $right ? 'certa' : ($letter === $chosen ? 'errada' : '')) : '';
                    $images = []; $altMissing = false;
                    foreach (nc_alt_files($q, $alt) as $file) { $src = nc_image($q['_folder'], $file); if ($src !== null) { $images[$src] = $src; } else { $altMissing = true; } }
                ?>
                <button type="submit" name="letra" value="<?= nc_h($letter) ?>" class="alternativa <?= $state ?>" <?= $chosen !== '' || !$canAnswer ? 'disabled' : '' ?>>
                    <span class="letra"><?= nc_h($letter) ?></span><span class="alternativa-conteudo">
                        <?php if (nc_text($alt['text'] ?? null) !== ''): ?><span class="alternativa-texto"><?= nc_h($alt['text']) ?></span><?php endif; ?>
                        <?php foreach ($images as $src): ?><img src="<?= nc_h($src) ?>" alt="Representação gráfica da alternativa <?= nc_h($letter) ?>" loading="lazy" decoding="async"><?php endforeach; ?>
                        <?php if ($altMissing || (!$images && nc_text($alt['text'] ?? null) === '')): ?><span class="ajuda">Conteúdo visual indisponível nesta pasta.</span><?php endif; ?>
                        <?php if ($chosen !== '' && $letter === $right): ?><span class="estado">✓ Alternativa correta</span><?php elseif ($chosen !== '' && $letter === $chosen): ?><span class="estado">× Sua resposta</span><?php endif; ?>
                    </span>
                </button>
                <?php endforeach; ?>
            </form>
            <div class="feedback">
            <?php if (!$canAnswer): ?><p class="aviso"><?= $cancelled ? 'Questão anulada no gabarito. Não há alternativa a corrigir.' : 'Gabarito não disponível para correção.' ?></p><?php endif; ?>
            <?php if ($chosen !== ''): ?><div class="resultado <?= $chosen === $right ? 'ok' : 'erro' ?>" role="status"><?= $chosen === $right ? 'Resposta correta!' : 'Resposta incorreta.' ?> A alternativa correta é <?= nc_h($right) ?>.</div>
                <?php if (nc_text($q['metadata']['answerNote'] ?? null) !== ''): ?><p class="ajuda"><?= nc_h($q['metadata']['answerNote']) ?></p><?php endif; ?>
            <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
        <?php if ($pages > 1): ?><nav class="paginacao" aria-label="Páginas de questões"><?php if ($page > 1): ?><a class="btn secundario" href="<?= nc_h(nc_url($filters, $page - 1)) ?>">← Anterior</a><?php endif; ?><span><?= $page ?> / <?= $pages ?></span><?php if ($page < $pages): ?><a class="btn primario" href="<?= nc_h(nc_url($filters, $page + 1)) ?>">Próxima →</a><?php endif; ?></nav><?php endif; ?>
        <?php endif; ?>
        <footer>Nous · seu espaço para estudar <span>UNICAMP · arquivos locais</span></footer>
    </main>
</div>
<script>
(() => {
    const button = document.getElementById('tema');
    const apply = (dark) => { document.body.classList.toggle('dark', dark); button.setAttribute('aria-pressed', String(dark)); button.textContent = dark ? 'Tema claro' : 'Tema escuro'; };
    try { apply(localStorage.getItem('nous_unicamp_tema') === 'dark'); } catch (_) { apply(false); }
    button.addEventListener('click', () => { const dark = !document.body.classList.contains('dark'); apply(dark); try { localStorage.setItem('nous_unicamp_tema', dark ? 'dark' : 'light'); } catch (_) {} });

    // Corrige somente o cartão respondido, sem navegar nem recarregar a página.
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('.alternativas')) return;
        event.preventDefault();
        const article = form.closest('.cartao');
        if (!article || article.getAttribute('aria-busy') === 'true') return;
        const data = new FormData(form);
        if (event.submitter && event.submitter.name) data.set(event.submitter.name, event.submitter.value);
        data.set('ajax', '1');
        const controls = [...article.querySelectorAll('button')].map(control => [control, control.disabled]);
        article.querySelector('.erro-requisicao')?.remove();
        article.setAttribute('aria-busy', 'true');
        controls.forEach(([control]) => { control.disabled = true; });
        let replaced = false;
        try {
            const response = await fetch(form.action, {method: 'POST', body: data, credentials: 'same-origin'});
            const html = await response.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            if (!response.ok) throw new Error(parsed.querySelector('[role="alert"]')?.textContent || 'Não foi possível corrigir a resposta. Tente novamente.');
            const replacement = parsed.getElementById(article.id);
            if (!replacement) throw new Error('A questão não foi encontrada na resposta. Recarregue a página para continuar.');
            // Captura a posição no momento da troca, respeitando qualquer rolagem feita durante a requisição.
            const x = window.scrollX, y = window.scrollY;
            const scrolling = document.documentElement.style.scrollBehavior;
            document.documentElement.style.scrollBehavior = 'auto';
            // Mantém texto e imagens já carregadas: somente estados e feedback mudam.
            const currentForm = article.querySelector('.alternativas');
            const nextForm = replacement.querySelector('.alternativas');
            if (!currentForm || !nextForm) throw new Error('Não foi possível atualizar as alternativas.');
            currentForm.className = nextForm.className;
            currentForm.querySelectorAll('button[name="letra"]').forEach(control => {
                const nextControl = [...nextForm.querySelectorAll('button[name="letra"]')].find(item => item.value === control.value);
                if (!nextControl) return;
                control.className = nextControl.className;
                control.disabled = nextControl.disabled;
                control.querySelector('.estado')?.remove();
                const state = nextControl.querySelector('.estado');
                if (state) control.querySelector('.alternativa-conteudo').append(state);
            });
            const feedback = replacement.querySelector('.feedback');
            if (feedback) article.querySelector('.feedback').replaceWith(feedback);
            window.scrollTo({left: x, top: y, behavior: 'instant'});
            document.documentElement.style.scrollBehavior = scrolling;
            replaced = true;
        } catch (error) {
            const notice = document.createElement('p');
            notice.className = 'aviso erro-requisicao';
            notice.setAttribute('role', 'alert');
            notice.textContent = error.message || 'Não foi possível registrar sua resposta. Tente novamente.';
            article.append(notice);
        } finally {
            article.removeAttribute('aria-busy');
            if (!replaced) {
                controls.forEach(([control, disabled]) => { control.disabled = disabled; });
            }
        }
    });
})();
</script>
</body>
</html>
