(() => {
  const login = document.getElementById('login-box');
  const cadastro = document.getElementById('register-box');
  if (!login || !cadastro) return;
  function mostrarCadastro(abrir) {
    login.style.display = abrir ? 'none' : 'block';
    cadastro.style.display = abrir ? 'block' : 'none';
  }
  if (location.hash === '#cadastro') mostrarCadastro(true);
  document.querySelectorAll('[data-auth-toggle]').forEach(link => link.addEventListener('click', event => {
    event.preventDefault();
    const abrir = login.style.display !== 'none';
    mostrarCadastro(abrir);
    history.replaceState(null, '', location.pathname + (abrir ? '#cadastro' : ''));
  }));
})();
