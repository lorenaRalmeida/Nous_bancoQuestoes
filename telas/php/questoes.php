<?php
require_once __DIR__.'/app.php';
login_obrigatorio();
require __DIR__.'/../banco/conexao.php';
$banca = is_string($_GET['banca'] ?? null) ? $_GET['banca'] : '';
if (!isset(bancas()[$banca])) { header('Location: index.php'); exit; }
$questoes = catalogo($banca);
$uid = (int)$_SESSION['usuario_id'];
$ordemQuiz = array_keys($questoes);
$posicaoQuiz = (int)($_SESSION['quiz_posicao'][$uid] ?? 0);
$posicaoQuiz = max(0, min(count($ordemQuiz), $posicaoQuiz));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $acao = $_POST['acao'] ?? '';
    if ($banca === 'quiz' && $acao === 'recomecar_quiz') {
        unset($_SESSION['quiz_respostas'][$uid], $_SESSION['quiz_posicao'][$uid], $_SESSION['quiz_feedback'][$uid]);
        header('Location: questoes.php?banca=quiz', true, 303); exit;
    }
    $id = is_string($_POST['id'] ?? null) ? $_POST['id'] : '';
    $q = $questoes[$id] ?? null;
    if (!$q) { http_response_code(400); exit('Questão inválida.'); }
    $destino = $id;
    if ($acao === 'favoritar') {
        $s=$conn->prepare('SELECT questao_id FROM favoritos WHERE usuario_id=? AND questao_id=?');
        $s->bind_param('is',$uid,$id); $s->execute();
        if ($s->get_result()->num_rows === 0) {
            $s=$conn->prepare('INSERT INTO favoritos (usuario_id,questao_id,banca) VALUES (?,?,?)');
            $s->bind_param('iss',$uid,$id,$banca); $s->execute();
        }
    } elseif ($acao === 'remover') {
        $s=$conn->prepare('DELETE FROM favoritos WHERE usuario_id=? AND questao_id=?');
        $s->bind_param('is',$uid,$id); $s->execute();
    } elseif ($acao === 'responder') {
        $letra=$_POST['letra'] ?? '';
        $validas=array_column($q['alternatives'],'letter');
        $correta=$q['correctAlternative'] ?? null;
        if (!empty($q['metadata']['cancelled'])) { http_response_code(400); exit('Questão anulada.'); }
        if (!is_string($letra) || !in_array($letra,$validas,true) || !in_array($correta,$validas,true)) { http_response_code(400); exit('Alternativa ou gabarito inválido.'); }
        $acertou=(int)($letra===$correta);
        if ($banca === 'quiz') {
            if (($ordemQuiz[$posicaoQuiz] ?? null) !== $id) { http_response_code(400); exit('Responda à pergunta atual para liberar a próxima.'); }
            $_SESSION['quiz_respostas'][$uid][$id] = $letra;
            $_SESSION['quiz_feedback'][$uid] = ['numero'=>$posicaoQuiz+1, 'acertou'=>$acertou, 'correta'=>$correta, 'escolhida'=>$letra];
            $_SESSION['quiz_posicao'][$uid] = ++$posicaoQuiz;
            $destino = $ordemQuiz[$posicaoQuiz] ?? 'quiz-concluido';
        } else {
            $s=$conn->prepare('SELECT questao_id FROM progresso WHERE usuario_id=? AND questao_id=?');
            $s->bind_param('is',$uid,$id); $s->execute();
            if ($s->get_result()->num_rows > 0) {
                $s=$conn->prepare('UPDATE progresso SET resposta=?, acertou=?, atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND questao_id=?');
                $s->bind_param('siis',$letra,$acertou,$uid,$id);
            } else {
                $s=$conn->prepare('INSERT INTO progresso (usuario_id,questao_id,banca,resposta,acertou) VALUES (?,?,?,?,?)');
                $s->bind_param('isssi',$uid,$id,$banca,$letra,$acertou);
            }
            $s->execute();
        }
    } else { http_response_code(400); exit('Ação inválida.'); }
    $parametros = ['banca'=>$banca];
    if ($banca !== 'quiz') $parametros += array_intersect_key($_GET,array_flip(['buscar','ano','materia','pagina','id']));
    header('Location: questoes.php?'.http_build_query($parametros).'#'.$destino, true,303); exit;
}
$favoritos=[]; $respostas=[];
$s=$conn->prepare('SELECT questao_id FROM favoritos WHERE usuario_id=? AND banca=?'); $s->bind_param('is',$uid,$banca); $s->execute();
foreach($s->get_result() as $r) $favoritos[$r['questao_id']]=true;
if ($banca !== 'quiz') {
    $s=$conn->prepare('SELECT questao_id,resposta FROM progresso WHERE usuario_id=? AND banca=?'); $s->bind_param('is',$uid,$banca); $s->execute();
    foreach($s->get_result() as $r) $respostas[$r['questao_id']]=$r['resposta'];
}
$materia=is_string($_GET['materia'] ?? null) ? $_GET['materia'] : '';
$ano=is_string($_GET['ano'] ?? null) ? $_GET['ano'] : '';
$alvo=is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$materias=array_unique(array_column($questoes,'discipline')); sort($materias);
$anos=array_unique(array_column($questoes,'year')); rsort($anos);
$busca = isset($_GET['buscar']) || $materia !== '' || $ano !== '' || $alvo !== '';
$porPagina=5;
$quizConcluido=false;
$feedbackQuiz=null;
if ($banca === 'quiz') {
    $busca=true;
    $total=count($questoes); $paginas=1; $pagina=1;
    $quizConcluido=$posicaoQuiz >= $total;
    $idAtual=$ordemQuiz[$posicaoQuiz] ?? null;
    $filtradas=$idAtual ? [$idAtual=>$questoes[$idAtual]] : [];
    $feedbackQuiz=$_SESSION['quiz_feedback'][$uid] ?? null;
} else {
    $filtradas=$busca ? array_filter($questoes,fn($q)=> (!$materia || ($q['discipline'] ?? '')===$materia) && (!$ano || (string)($q['year'] ?? '')===$ano) && (!$alvo || $q['id']===$alvo)) : [];
    $total=count($filtradas); $paginas=max(1,(int)ceil($total/$porPagina)); $pagina=max(1,min($paginas,(int)($_GET['pagina'] ?? 1)));
    $filtradas=array_slice($filtradas,($pagina-1)*$porPagina,$porPagina,true);
}
$titulo=bancas()[$banca];
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nous · <?=h($titulo)?></title><link rel="stylesheet" href="../css/questoes.css"><?php if ($banca === 'quiz'): ?><link rel="stylesheet" href="../css/quiz.css"><?php endif ?><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="../js/matematica.js"></script>
<script async src="https://cdn.jsdelivr.net/npm/mathjax@3.2.2/es5/tex-mml-chtml.js"></script><script src="../js/tema.js" defer></script><script src="../js/questoes.js" defer></script></head><body class="banco <?= $banca === 'quiz' ? 'pagina-quiz' : '' ?>">
<?php $base = ''; require __DIR__.'/cabecalho.php'; ?>
<main class="banco-conteudo"><header><h1><?=h($titulo)?></h1><p><?=$banca==='quiz' ? '10 perguntas de diversas matérias · nível fácil e médio' : 'Banco de questões'?></p></header>
<?php if ($banca !== 'quiz'): ?>
<form method="get" class="filtros"><input type="hidden" name="banca" value="<?=h($banca)?>"><input type="hidden" name="buscar" value="1"><label>Matéria<select name="materia"><option value="">Todas</option><?php foreach($materias as $m):?><option <?= $m===$materia?'selected':'' ?> value="<?=h($m)?>"><?=h($m)?></option><?php endforeach?></select></label><label>Ano<select name="ano"><option value="">Todos</option><?php foreach($anos as $a):?><option <?= (string)$a===$ano?'selected':'' ?> value="<?=h($a)?>"><?=h($a)?></option><?php endforeach?></select></label><button>Buscar questões</button><a href="questoes.php?banca=<?=h($banca)?>">Limpar</a></form>
<?php if($busca):?><p class="contagem"><?=$total?> questões encontradas · 5 por página</p><?php endif?>
<?php endif ?>
<?php if (!$busca):?><div class="estado-filtros"><h2>Escolha por onde começar</h2><p>Selecione os filtros e clique em Buscar questões.</p></div><?php elseif (!$total):?><div class="estado-filtros"><h2>Nenhuma questão encontrada</h2><p>Tente outra combinação de filtros.</p></div><?php endif?>
<?php if($banca === 'quiz'):?>
<?php if($feedbackQuiz):?><div class="resultado quiz-feedback" role="status">Pergunta <?=h($feedbackQuiz['numero'])?>: <?=$feedbackQuiz['acertou']?'resposta correta!':'resposta incorreta.'?> Gabarito: <?=h($feedbackQuiz['correta'])?>. Sua resposta: <?=h($feedbackQuiz['escolhida'])?>.</div><?php endif?>
<?php if($quizConcluido):?><section class="quiz-concluido" id="quiz-concluido"><h2>Quiz concluído!</h2><p>Você respondeu às <?=$total?> perguntas.</p><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><button class="btn-primary" name="acao" value="recomecar_quiz">Recomeçar quiz</button></form></section><?php else:?><p class="quiz-etapa">Pergunta <?=$posicaoQuiz+1?> de <?=$total?></p><?php endif?>
<?php endif?>
<?php foreach($filtradas as $id=>$q): $escolhida=$respostas[$id] ?? ''; $correta=$q['correctAlternative'] ?? ''; $salva=isset($favoritos[$id]); $temOriginal=!empty($q['originalFiles']); $podeResponder=empty($q['metadata']['cancelled']) && in_array($correta,array_column($q['alternatives'],'letter'),true); ?>
<article class="cartao" id="<?=h($id)?>"><header class="q-topo"><div><h2><?=h($q['title'] ?? 'Questão')?></h2><p><?=h($q['discipline'] ?? '')?> · <?=h($q['year'] ?? '')?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="id" value="<?=h($id)?>"><button class="favorito" name="acao" value="<?=$salva?'remover':'favoritar'?>" aria-label="<?=$salva?'Remover dos favoritos':'Favoritar questão'?>" aria-pressed="<?=$salva?'true':'false'?>"><img src="../../imagens/<?=$salva?'coracao-cheio':'coracao'?>.png" alt="" width="28" height="28"></button></form></header>
<?php if($temOriginal):?><div class="fonte-original"><?php foreach($q['originalFiles'] as $arquivo): if($src=imagem_url($arquivo,$q)):?><img loading="lazy" <?=atributos_imagem($src)?> src="<?=h($src)?>" alt="<?=h($q['title'] ?? 'Questão')?>: enunciado, referências, figuras e alternativas originais da prova."><?php endif; endforeach?></div><details class="transcricao"><summary>Ver transcrição em texto</summary><?php endif?>
<div class="enunciado"><?=texto($q['context'] ?? '',$q)?></div>
<?php foreach(imagens_enunciado($q) as $file): $src=imagem_url($file,$q); if($src):?><img loading="lazy" <?=atributos_imagem($src)?> src="<?=h($src)?>" alt="Figura do enunciado da questão <?=h($q['index'] ?? '')?>"><?php endif; endforeach?>
<p class="intro"><?=texto($q['alternativesIntroduction'] ?? '',$q)?></p>
<?php if($temOriginal):?><ol class="transcricao-alternativas"><?php foreach($q['alternatives'] as $alt):?><li><strong><?=h($alt['letter'])?>.</strong> <?=texto($alt['text'] ?? '',$q)?><?php foreach(imagens_alternativa($q,$alt) as $arquivo):if($src=imagem_url($arquivo,$q)):?><img loading="lazy" <?=atributos_imagem($src)?> src="<?=h($src)?>" alt="Representação da alternativa <?=h($alt['letter'])?>"><?php endif;endforeach?></li><?php endforeach?></ol></details><p class="intro">Escolha a alternativa:</p><?php endif?>
<form method="post" class="alternativas <?= $temOriginal?'opcoes-originais':'' ?>"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="id" value="<?=h($id)?>"><input type="hidden" name="acao" value="responder">
<?php foreach($q['alternatives'] as $alt): $l=$alt['letter']; $classe=$escolhida ? ($l===$correta?'certa':($l===$escolhida?'errada':'')):'';?><button class="alternativa <?=h($classe)?>" <?=!$podeResponder?'disabled':''?> name="letra" value="<?=h($l)?>"><strong><?=h($l)?></strong><span><?php if($temOriginal):?>Responder <?=h($l)?><?php else:?><?=texto($alt['text'] ?? '',$q)?><?php foreach(imagens_alternativa($q,$alt) as $arquivo): if($src=imagem_url($arquivo,$q)):?><img loading="lazy" <?=atributos_imagem($src)?> src="<?=h($src)?>" alt="Representação da alternativa <?=h($l)?>"><?php endif; endforeach?><?php endif?></span></button><?php endforeach?></form>
<?php if(!$podeResponder):?><p class="resultado"><?=!empty($q['metadata']['cancelled'])?'Questão anulada: não entra no progresso.':'Gabarito indisponível: não entra no progresso.'?></p><?php endif?>
<?php if($escolhida):?><p class="resultado"><?=$escolhida===$correta?'Resposta correta!':'Resposta incorreta.'?> Gabarito: <?=h($correta)?>. Sua resposta: <?=h($escolhida)?>.</p><?php endif?></article><?php endforeach?>
<?php if ($banca !== 'quiz' && $paginas > 1): ?><nav class="paginacao" aria-label="Paginação"><?php for($p=max(1,$pagina-2);$p<=min($paginas,$pagina+2);$p++): $params=['banca'=>$banca,'buscar'=>1,'pagina'=>$p,'materia'=>$materia,'ano'=>$ano,'id'=>$alvo];?><a <?= $p===$pagina?'aria-current="page"':'' ?> href="?<?=h(http_build_query($params))?>"><?=$p?></a><?php endfor?></nav><?php endif ?></main></body></html>



