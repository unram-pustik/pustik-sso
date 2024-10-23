<?php
/**
 * LRsoft Corp.
 * https://lrsoft.id
 *
 * Author : Zaf
 */

session_start();

echo '<pre>';
print_r($_SESSION['info'] ?? []);
echo '</pre>';