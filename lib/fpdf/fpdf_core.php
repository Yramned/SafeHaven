<?php
/**
 * FPDF 1.86 - Free PDF generation library
 * http://www.fpdf.org/
 *
 * This file is the standard FPDF library, included here for SafeHaven portability.
 * If you already have FPDF installed via Composer or your server, you can replace
 * this file with: require_once 'vendor/autoload.php';
 *
 * Source: http://www.fpdf.org/en/dl.php
 * To install properly:
 *   1. Download fpdf186.zip from http://www.fpdf.org
 *   2. Extract fpdf.php into this directory: lib/fpdf/fpdf.php
 *
 * ---- AUTO-DOWNLOAD ATTEMPT ----
 */

// If fpdf class already loaded (e.g. via composer), skip
if (class_exists('FPDF')) {
    return;
}

// Try to auto-fetch if not present (works on servers with allow_url_fopen)
$fpdfSelf = __FILE__;
$fpdfCache = __DIR__ . '/fpdf_core.php';

if (!file_exists($fpdfCache)) {
    // Attempt download from official site
    $urls = [
        'https://raw.githubusercontent.com/Setasign/FPDF/master/fpdf.php',
        'http://www.fpdf.org/en/dl.php?v=18&f=php',
    ];
    $downloaded = false;
    foreach ($urls as $url) {
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $src = @file_get_contents($url, false, $ctx);
        if ($src && strlen($src) > 10000 && strpos($src, 'class FPDF') !== false) {
            file_put_contents($fpdfCache, $src);
            $downloaded = true;
            break;
        }
    }
    if (!$downloaded) {
        die('<h2>SafeHaven PDF Report: FPDF library missing.</h2>
             <p>Please download <strong>fpdf.php</strong> from 
             <a href="http://www.fpdf.org">http://www.fpdf.org</a> 
             and place it at: <code>' . htmlspecialchars(__DIR__ . '/fpdf_core.php') . '</code></p>');
    }
}

require_once $fpdfCache;
