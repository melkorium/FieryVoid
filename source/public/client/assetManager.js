"use strict";

window.AssetManager = {
    _isWebpSupported: null,

    /**
     * Checks if the browser supports WebP.
     * Caches the result after the first run.
     */
    isWebpSupported: function() {
        if (this._isWebpSupported !== null) return this._isWebpSupported;

        try {
            var elem = document.createElement('canvas');
            if (!!(elem.getContext && elem.getContext('2d'))) {
                this._isWebpSupported = elem.toDataURL('image/webp').indexOf('data:image/webp') === 0;
            } else {
                this._isWebpSupported = false;
            }
        } catch (e) {
            this._isWebpSupported = false;
        }

        // Initialize global error listener once support is known
        if (this._isWebpSupported && !window._assetManagerInitialized) {
            window.addEventListener('error', function(e) {
                // Global fallback for any image in the img/ directory
                if (e.target.tagName === 'IMG' && e.target.src.indexOf('.webp') !== -1) {
                    console.warn("WebP Load failed, reverting to original:", e.target.src);
                    
                    // Revert based on potential original extensions
                    // NOTE: This assumes the original was either .png or .jpg
                    // Since we can't be 100% sure, we try the most common (PNG) first
                    // The error listener will trigger again if the PNG also fails.
                    e.target.src = e.target.src.replace('.webp', '.png');
                }
            }, true);
            window._assetManagerInitialized = true;
        }

        return this._isWebpSupported;
    },

    /**
     * Given an image path, returns the WebP version if supported.
     * Works for all PNG and JPG/JPEG files in the img/ folder.
     */
    getSmartImagePath: function(path) {
        if (!path || typeof path !== 'string') return path;
        
        // Remove 'v=X' versioning if present to check extension accurately
        var cleanPath = path.split('?')[0];

        if (this.isWebpSupported()) {
            if (cleanPath.toLowerCase().endsWith('.png')) {
                return path.replace('.png', '.webp');
            } else if (cleanPath.toLowerCase().endsWith('.jpg')) {
                return path.replace('.jpg', '.webp');
            } else if (cleanPath.toLowerCase().endsWith('.jpeg')) {
                return path.replace('.jpeg', '.webp');
            }
        }

        return path;
    }
};
