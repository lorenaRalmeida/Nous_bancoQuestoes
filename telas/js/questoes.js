// Somente apresentação: todos os dados das questões continuam enviados por formulário PHP.
(() => {
  const quiz = document.body.classList.contains('pagina-quiz');
  const parametros = new URLSearchParams(location.search);
  parametros.sort();
  const chave = quiz ? 'nous-posicao-quiz' : 'nous-posicao:' + location.pathname + '?' + parametros.toString();
  document.querySelectorAll('.cartao form[method="post"]').forEach(form => {
    form.addEventListener('submit', () => {
      try { sessionStorage.setItem(chave, JSON.stringify({ y: window.scrollY })); } catch (_) {}
    });
  });
  let posicao = null;
  try {
    const salvo = sessionStorage.getItem(chave);
    if (salvo) { posicao = JSON.parse(salvo); sessionStorage.removeItem(chave); }
  } catch (_) {}
  if (!posicao) return;
  if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
  function restaurar() {
    if (quiz) {
      const alvo = document.querySelector('.quiz-feedback, .quiz-concluido, .cartao');
      if (alvo) alvo.scrollIntoView({ block: 'start', behavior: 'instant' });
    } else {
      window.scrollTo({ top: posicao.y, behavior: 'instant' });
    }
  }
  requestAnimationFrame(restaurar);
  window.addEventListener('load', restaurar, { once: true });
  if (document.fonts) document.fonts.ready.then(restaurar);
})();
