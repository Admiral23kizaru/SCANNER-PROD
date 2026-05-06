/**
 * Resolves URLs for files in Laravel `public/` (logo, `/images`, `/storage`).
 *
 * `window.__SCANNER_ASSET_ROOT__` is set in app.blade.php from `request()->root()`
 * so the same build works with php artisan serve, XAMPP subfolders, or a different host/port.
 */

function viteBasePrefix() {
    const raw = import.meta.env.BASE_URL || '/';
    return raw === '/' ? '' : raw.replace(/\/+$/, '');
}

function viteEnvAssetPrefix() {
    const v = import.meta.env.VITE_ASSET_PREFIX || '';
    if (!v || v.includes('${')) return '';
    return String(v).replace(/\/+$/, '');
}

/** @returns {string} URL root with no trailing slash, or ''. */
export function getAssetRoot() {
    if (
        typeof window !== 'undefined' &&
        typeof window.__SCANNER_ASSET_ROOT__ === 'string' &&
        window.__SCANNER_ASSET_ROOT__.trim() !== ''
    ) {
        return window.__SCANNER_ASSET_ROOT__.trim().replace(/\/+$/, '');
    }
    const fromViteBase = viteBasePrefix();
    if (fromViteBase) return fromViteBase;
    return viteEnvAssetPrefix();
}

export function assetPath(path) {
    if (!path) return '';
    const cleanPath = path.startsWith('/') ? path : `/${path}`;
    const root = getAssetRoot();
    return root ? `${root}${cleanPath}` : cleanPath;
}
