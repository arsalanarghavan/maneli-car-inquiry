/**
 * TEST LOADER - این فایل باید در header load شود تا ببینیم آیا scripts لود می‌شوند یا نه
 */
console.log('✅ autopuzzle-test-loader.js LOADED - Scripts are being enqueued!');
console.log('Window location:', window.location.href);
console.log('Page URL:', document.URL);

// Add to window for debugging
window.maneliTestLoaderLoaded = true;

