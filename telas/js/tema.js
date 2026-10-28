(() => {
  const buttons = document.querySelectorAll('[data-theme-toggle]');
  function update() {
    const dark = document.body.classList.contains('dark');
    document.querySelectorAll('.tema-simbolo').forEach(symbol => { symbol.textContent = dark ? '☀' : '☾'; });
    document.querySelectorAll('.auth-theme-btn i').forEach(icon => { icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'; });
    buttons.forEach(button => { button.title = dark ? 'Ativar tema claro' : 'Ativar tema escuro'; button.setAttribute('aria-label', button.title); });
  }
  try { document.body.classList.toggle('dark', localStorage.getItem('nous-theme') === 'dark'); } catch (_) {}
  update();
  buttons.forEach(button => button.addEventListener('click', () => {
    const dark = document.body.classList.toggle('dark');
    try { localStorage.setItem('nous-theme', dark ? 'dark' : 'light'); } catch (_) {}
    update();
  }));
})();
