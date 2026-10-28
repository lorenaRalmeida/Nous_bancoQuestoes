<?php
require_once __DIR__.'/app.php';
login_obrigatorio();
if (!isset($conn)) require __DIR__.'/../banco/conexao.php';
$id_usuario = (int)$_SESSION['usuario_id'];
$s=$conn->prepare('SELECT questao_id,banca FROM favoritos WHERE usuario_id=? ORDER BY banca,questao_id');
$s->bind_param('i',$id_usuario);$s->execute();$salvas=$s->get_result();$catalogos=[];
if(!$salvas->num_rows) echo '<p class="favoritos-vazio">Você ainda não salvou questões. Clique no coração de uma questão para encontrá-la aqui.</p>';
?>
<div class="favoritos-lista"><?php foreach($salvas as $f):$b=$f['banca'];if(!isset(bancas()[$b]))continue;$catalogos[$b] ??= catalogo($b);$q=$catalogos[$b][$f['questao_id']] ?? null;if(!$q)continue;$pasta=$b==='enem'?'Enem':($b==='quiz'?'Quiz':$b);?>
<article class="stat-card"><div><h3><?=h($q['title'] ?? 'Questão')?></h3><p><?=h(bancas()[$b])?> · <?=h($q['discipline'] ?? '')?></p><p class="favorito-resumo"><?=h(mb_substr(preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $q['context'] ?? ''),0,180))?>…</p><a class="btn-primary" href="questoes.php?banca=<?=h($b)?>&amp;id=<?=h($f['questao_id'])?>">Abrir questão</a></div></article>
<?php endforeach?></div>

