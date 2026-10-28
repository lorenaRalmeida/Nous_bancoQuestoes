<?php
require_once __DIR__.'/app.php';
login_obrigatorio();
if (!isset($conn)) require __DIR__.'/../banco/conexao.php';
$id_usuario = (int)$_SESSION['usuario_id'];
$s=$conn->prepare('SELECT banca,COUNT(*) total,SUM(acertou) acertos FROM progresso WHERE usuario_id=? AND banca <> \'quiz\' GROUP BY banca');
$s->bind_param('i',$id_usuario); $s->execute(); $dados=[];
foreach($s->get_result() as $r) $dados[$r['banca']]=$r;
?>
<p>Seu resultado considera a última resposta de cada questão. Refazer uma questão atualiza o resultado, sem duplicar a contagem.</p>
<div class="progresso-lista"><?php foreach(array_diff_key(bancas(), ['quiz'=>true]) as $b=>$nome): $r=$dados[$b] ?? ['total'=>0,'acertos'=>0];$total=(int)$r['total'];$certas=(int)$r['acertos'];$percentual=$total ? round(100*$certas/$total):0;?>
<article class="stat-card"><div><h2><?=h($nome)?></h2><p><?=$certas?> certas · <?=$total-$certas?> erradas · <?=$total?> respondidas</p><progress max="100" value="<?=$percentual?>"><?=$percentual?>%</progress><strong><?=$percentual?>% de acerto</strong></div></article>
<?php endforeach?></div>
