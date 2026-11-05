/*
 * Service Worker da Procuradoria da Mulher.
 *
 * Escopo inicial: cacheados apenas recursos ESTÁTICOS do próprio domínio
 * (CSS/imagens sob /assets/ e o manifest). Nenhuma página HTML, resposta de
 * formulário, dado de módulo (atendimento, SOS, etc.) ou requisição de outro
 * domínio é armazenada. Não há suporte offline completo nesta versão.
 */

'use strict';

// Versão do cache. Ao alterar os recursos cacheados, incremente este valor
// para que o activate descarte caches antigos.
const CACHE = 'pm-static-v1';

// Base do site derivada da localização deste script (funciona em subpasta).
const BASE = new URL('./', self.location).pathname;

// Recursos estáveis pré-carregados na instalação (URLs sem variação).
const PRECACHE = [
  BASE + 'manifest.json',
  BASE + 'assets/pwa/icon-192.png',
  BASE + 'assets/pwa/icon-512.png',
  BASE + 'assets/pwa/icon-maskable-512.png',
  BASE + 'assets/pwa/apple-touch-icon.png',
];

// Somente estes caminhos podem ser cacheados.
function isCacheable(url) {
  return (
    url.origin === self.location.origin &&
    (url.pathname.startsWith(BASE + 'assets/') || url.pathname === BASE + 'manifest.json')
  );
}

// Cache-first com atualização em segundo plano apenas para estáticos.
async function cacheFirst(request) {
  const cache = await caches.open(CACHE);
  const cached = await cache.match(request);

  if (cached) {
    fetch(request)
      .then((response) => {
        if (response && response.ok && response.type !== 'opaque') {
          cache.put(request, response.clone());
        }
      })
      .catch(() => {});
    return cached;
  }

  const response = await fetch(request);
  if (response && response.ok && response.type !== 'opaque') {
    cache.put(request, response.clone());
  }
  return response;
}

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) =>
      Promise.all(
        PRECACHE.map((url) => cache.add(url).catch(() => {}))
      )
    ).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  // Ignora métodos não-GET (login, formulários, SOS, etc.).
  if (request.method !== 'GET') {
    return;
  }

  let url;
  try {
    url = new URL(request.url);
  } catch (error) {
    return;
  }

  // Tudo que não for estático cacheável segue direto para a rede, sem cache.
  if (!isCacheable(url)) {
    return;
  }

  event.respondWith(cacheFirst(request));
});
