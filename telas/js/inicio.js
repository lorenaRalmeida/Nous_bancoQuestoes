document.querySelectorAll('.nav-item[data-target="home"]').forEach(link => link.classList.add('active'));
    document.querySelectorAll('.nav-item[data-target]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const target = link.getAttribute('data-target');
        document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.page-section').forEach(sec => sec.classList.remove('active'));
        document.querySelectorAll(`.nav-item[data-target="${target}"]`).forEach(l => l.classList.add('active'));
        const section = document.getElementById('section-' + target);
        if (section) {
          section.classList.add('active');
        }
      });
    });
  

const secao = new URLSearchParams(location.search).get('secao');
if (secao && /^[a-z]+$/.test(secao)) document.querySelector('.nav-item[data-target="'+secao+'"]')?.click();
