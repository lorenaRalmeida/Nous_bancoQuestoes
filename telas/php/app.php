<?php
define('RAIZ_PROJETO', dirname(__DIR__, 2));
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function login_obrigatorio(string $prefixo = ''): void {
    if (empty($_SESSION['usuario_id'])) { header('Location: '.$prefixo.'login.php'); exit; }
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function verificar_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf(), $_POST['csrf'])) { http_response_code(403); exit('Formulário expirado. Recarregue a página.'); }
}
function bancas(): array { return ['enem'=>'ENEM','fuvest'=>'Fuvest','unicamp'=>'Unicamp','unesp'=>'Unesp','quiz'=>'Quiz']; }
function catalogo(string $banca): array {
    if (!isset(bancas()[$banca])) return [];
    $pasta = $banca === 'quiz' ? RAIZ_PROJETO.'/Quiz/json' : RAIZ_PROJETO.'/'.($banca === 'enem' ? 'Enem/q_enem_json' : $banca);
    $resultado = [];
    if (!is_dir($pasta)) return [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!in_array($f->getFilename(), ['details.json','question.json'], true) && $banca !== 'quiz') continue;
        if ($f->getExtension() !== 'json') continue;
        $dados = json_decode(file_get_contents($f->getPathname()), true);
        if (!is_array($dados)) continue;
        $lista = isset($dados['alternatives']) ? [$dados] : ($dados['questions'] ?? $dados);
        foreach ($lista as $q) {
            if (!is_array($q) || empty($q['alternatives'])) continue;
            $rel = str_replace('\\','/',substr($f->getPathname(),strlen(RAIZ_PROJETO)+1));
            $q['id'] = $banca.'_'.substr(hash('sha256',$rel.'|'.($q['index'] ?? $q['id'] ?? $q['title'] ?? '')),0,32);
            $q['_dir'] = dirname($rel);
            $resultado[$q['id']] = $q;
        }
    }
    uasort($resultado, fn($a,$b)=> (($b['year'] ?? 0) <=> ($a['year'] ?? 0)) ?: (($a['index'] ?? 0) <=> ($b['index'] ?? 0)));
    return $resultado;
}
function imagem_url(string $src, array $q): ?string {
    $src = html_entity_decode(trim($src), ENT_QUOTES, 'UTF-8');
    if (preg_match('~^https?://~i',$src)) {
        // Os JSONs ENEM apontam à API original; as cópias locais têm o mesmo nome.
        $local = RAIZ_PROJETO.'/'.$q['_dir'].'/'.basename(parse_url($src,PHP_URL_PATH));
        if (is_file($local)) return '../../'.$q['_dir'].'/'.rawurlencode(basename($local));
        return $src;
    }
    if (preg_match('~^[a-z]+:|^//~i',$src)) return null;
    foreach ([$q['_dir'].'/'.$src, $src, $q['_dir'].'/'.basename($src)] as $rel) {
        $real = realpath(RAIZ_PROJETO.'/'.$rel);
        if ($real && str_starts_with($real,RAIZ_PROJETO.DIRECTORY_SEPARATOR) && is_file($real)) {
            return '../../'.implode('/',array_map('rawurlencode',explode('/',str_replace('\\','/',substr($real,strlen(RAIZ_PROJETO)+1)))));
        }
    }
    return null;
}
function texto($texto, array $q): string {
    if (!is_scalar($texto)) return '';
    $texto = (string)$texto;
    // A extração usa ^ e _ para potências e índices; preserva os símbolos no HTML.
    $texto = preg_replace('~(?<![A-Za-z0-9_/])([A-Za-z]′?|[0-9]+)\^\(([^()]+)\)~u', '\\($1^{$2}\\)', $texto);
    $texto = preg_replace('~(?<![A-Za-z0-9_/])([A-Za-z]′?|[0-9]+)_([A-Za-z0-9]+)(?!\w)~u', '\\($1_{$2}\\)', $texto);
    // Delimitadores TeX sem confundir valores em reais (R$) com matemática.
    $texto = preg_replace('/(?<![R$])\$(?!\$)([^$\n]+)\$(?!\$)/u', '\\($1\\)', $texto);
    // Resolve referências Markdown antes de renderizar as imagens.
    $refs = [];
    $texto = preg_replace_callback('/^\s*\[([^\]]+)\]:\s*(\S+).*$/m',function($m) use (&$refs){$refs[strtolower($m[1])]=$m[2];return '';},$texto);
    $texto = preg_replace_callback('/!\[([^\]]*)\]\[([^\]]*)\]/',function($m) use ($refs){return '!['.$m[1].']('.($refs[strtolower($m[2] ?: $m[1])] ?? '').')';},$texto);
    $partes = preg_split('/(!\[[^\]]*\]\([^\s)]+\))/', $texto,-1,PREG_SPLIT_DELIM_CAPTURE);
    $html = '';
    foreach ($partes as $parte) {
        if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)$/',$parte,$m)) {
            $src = imagem_url($m[2],$q);
            $html .= $src ? '<img loading="lazy" '.atributos_imagem($src).' src="'.h($src).'" alt="'.h($m[1] ?: 'Imagem da questão').'">' : '<p class="imagem-ausente">Imagem não disponível no arquivo enviado.</p>';
        } else $html .= nl2br(h($parte));
    }
    return $html;
}

function imagens_alternativa(array $q, array $alt): array {
    $files = $q['metadata']['alternativeVisualFiles'][$alt['letter']] ?? [];
    if (!is_array($files)) $files = [];
    if (!empty($alt['file'])) array_unshift($files,$alt['file']);
    return array_values(array_unique(array_filter($files,'is_string')));
}
function imagens_enunciado(array $q): array {
    $alternativas=[];
    foreach ($q['alternatives'] as $alt) $alternativas=array_merge($alternativas,imagens_alternativa($q,$alt));
    return array_values(array_filter(array_unique($q['files'] ?? []),fn($file)=>is_string($file) && !in_array($file,$alternativas,true) && !preg_match('/^(alternativa-|recorte-)/',basename($file)) && !str_contains($q['context'] ?? '',$file)));
}



// Reserva o espaço das imagens para evitar saltos durante a leitura.
function atributos_imagem(string $url): string {
    if (preg_match('~^https?://~i', $url)) return '';
    $arquivo=realpath(__DIR__.'/'.rawurldecode($url));
    if (!$arquivo || !str_starts_with($arquivo,RAIZ_PROJETO.DIRECTORY_SEPARATOR)) return '';
    $medidas=@getimagesize($arquivo);
    return $medidas ? 'width="'.(int)$medidas[0].'" height="'.(int)$medidas[1].'"' : '';
}
