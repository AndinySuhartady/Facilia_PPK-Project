<?php

/**
 * Entry point tunggal aplikasi. Semua request diarahkan ke sini
 * lewat .htaccess (mod_rewrite), lalu di-dispatch oleh routes/web.php.
 */

require_once __DIR__ . '/../routes/web.php';
