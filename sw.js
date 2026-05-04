self.addEventListener('install', (event) => {
  console.log('Service Worker installed.');
});

self.addEventListener('fetch', (event) => {
  // 今回はキャッシュ制御なしの最小構成
});