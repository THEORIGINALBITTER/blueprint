/*
  BUREAU J&D — shared layout master
  This is the single source for the header and footer across all Blueprint pages.
*/
const defaults = { brand: 'Bureau J&D', meta: 'The 0-to-1 Blueprint', year: '2026', status: 'Bureau J&D — Draft v1.0', footerNote: 'Confirmed for Execution.' };

async function siteContent() {
  try {
    // Node development server: use its JSON endpoint first. The PHP endpoint
    // remains the fallback for classic PHP hosting.
    const response = await fetch('/api/content', { cache: 'no-store' });
    if (response.ok) return { ...defaults, ...await response.json() };
    const fallback = await fetch(`./content.php?ts=${Date.now()}`, { cache: 'no-store' });
    return fallback.ok ? { ...defaults, ...await fallback.json() } : defaults;
  } catch { return defaults; }
}

const escape = value => String(value).replace(/[&<>"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;' })[char]);

siteContent().then(content => {
  document.querySelectorAll('[data-site-header]').forEach(mount => {
    const title = mount.dataset.pageTitle || content.meta;
    const isAdmin = mount.dataset.admin === 'true';
    mount.innerHTML = `<header class="site-header"><a class="wordmark" href="${isAdmin ? '../' : './'}"><span>${escape(content.brand)}</span></a><div class="site-header-center">${escape(title)}</div><div class="site-actions">${isAdmin ? '<a href="../" target="_blank">Seite ansehen ↗</a><button class="quiet" id="logout" type="button">Abmelden</button><button id="publish" type="button">Änderungen veröffentlichen</button>' : `<span>${escape(content.meta)} <i>·</i> ${escape(content.year)}</span>`}</div></header><div class="orbit-backdrop" hidden></div><div class="orbit-menu is-collapsed" id="site-menu"><button class="orbit-toggle" type="button" aria-expanded="false" aria-controls="orbit-links"><img src="./assets/zenorbit-logo.svg" alt="ZenOrbit Menü" /></button><nav class="orbit-links" id="orbit-links" aria-label="ZenOrbit Navigation"><a href="./">Blueprint</a><a href="./memo.html?topic=model">01 Modell</a><a href="./memo.html?topic=skalierung">02 Skalierung</a><a href="./memo.html?topic=nische">03 Nische</a><a href="./memo.html?topic=demo">04 Demo</a><a href="https://zenorbit.denisbitter.de/" target="_blank" rel="noreferrer">ZenOrbit ↗</a></nav></div>`;
  });
  document.querySelectorAll('[data-site-footer]').forEach(mount => {
    mount.innerHTML = `<footer><span>${escape(content.status)}</span><span>${escape(content.footerNote)}</span><span>© ${escape(content.year)}</span></footer>`;
  });
  document.querySelectorAll('.orbit-toggle').forEach(button => button.addEventListener('click', () => {
    const nextOpen = button.getAttribute('aria-expanded') !== 'true';
    button.setAttribute('aria-expanded', String(nextOpen));
    button.closest('.orbit-menu').classList.toggle('is-collapsed', !nextOpen);
    const backdrop = button.closest('[data-site-header]')?.parentElement?.querySelector('.orbit-backdrop');
    if (backdrop) backdrop.hidden = !nextOpen;
    document.body.classList.toggle('orbit-open', nextOpen);
  }));
  document.querySelectorAll('.orbit-backdrop').forEach(backdrop => backdrop.addEventListener('click', () => {
    backdrop.hidden = true;
    const menu = backdrop.parentElement.querySelector('.orbit-menu');
    const button = menu?.querySelector('.orbit-toggle');
    menu?.classList.add('is-collapsed');
    button?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('orbit-open');
  }));
  const orbit = document.querySelector('.orbit-menu');
  if (orbit) {
    const links = [...orbit.querySelectorAll('.orbit-links a')];
    const layoutOrbit = () => {
      const radius = window.innerWidth < 701 ? 84 : 112;
      links.forEach((link, index) => {
        const angle = links.length === 1 ? -90 : -(180 / (links.length - 1)) * index;
        const radians = (angle - 90) * Math.PI / 180;
        const x = Math.cos(radians) * radius;
        const y = Math.sin(radians) * radius;
        link.style.transform = `translate(${x}px, ${y}px)`;
      });
    };
    layoutOrbit();
    window.addEventListener('resize', layoutOrbit);
    window.addEventListener('scroll', () => { orbit.style.transform = `translateY(${Math.min(window.scrollY, Math.max(0, window.innerHeight - 310))}px)`; }, { passive: true });
  }
});
