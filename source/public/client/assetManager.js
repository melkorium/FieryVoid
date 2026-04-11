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
                if (e.target.tagName === 'IMG' && e.target.src.indexOf('.webp') !== -1) {
                    console.warn("WebP UI Load failed, falling back to PNG:", e.target.src);
                    e.target.src = e.target.src.replace('.webp', '.png');
                }
            }, true);
            window._assetManagerInitialized = true;
        }

        return this._isWebpSupported;
    },

    /**
     * Given a PNG path, returns the WebP version if supported.
     * Only targets ship sprites to ensure safety.
     */
    getSmartImagePath: function(path) {
        if (!path || typeof path !== 'string') return path;
        
        // Only apply to ship sprites
        if (this.isWebpSupported() && path.toLowerCase().indexOf('img/ships/') !== -1) {
            return path.replace('.png', '.webp');
        }

        return path;
    }
};
