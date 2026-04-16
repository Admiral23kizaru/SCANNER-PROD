/**
 * Resolves asset paths taking into account subdirectory deployment.
 * 
 * Uses VITE_ASSET_PREFIX from the .env file.
 */
export function assetPath(path) {
    if (!path) return '';
    
    let base = import.meta.env.VITE_ASSET_PREFIX || '';
    if (base && base.endsWith('/')) {
        base = base.slice(0, -1);
    }
    
    const cleanPath = path.startsWith('/') ? path : '/' + path;
    return base + cleanPath;
}
