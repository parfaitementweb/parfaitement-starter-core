<?php

namespace Parfaitement;

/**
 * Gets the path to a versioned Mix file.
 *
 * @param string $path The relative path to the file.
 *
 * @return string The file URL.
 */
function mix($path)
{
    $dist_folder = '/dist';
    $manifest_path = get_theme_file_path($dist_folder . '/mix-manifest.json');

    if (! file_exists($manifest_path)) {
        return get_theme_file_uri($path);
    }

    $manifest = json_decode(file_get_contents($manifest_path), true);

    // Make sure there’s a leading slash
    $path = '/' . ltrim($path, '/');

    if (! array_key_exists($path, $manifest)) {
        return get_theme_file_uri($path);
    }

    // Get file URL from manifest file
    $path = $manifest[$path];
    // Make sure there’s no leading slash
    $path = ltrim($path, '/');

    return get_theme_file_uri(trailingslashit($dist_folder) . $path);
}
