<?php

require __DIR__ . '/bootstrap.php';

header('Location: ' . (is_logged_in() ? 'dashboard.php' : 'login.php'));
exit;
