<?php

$config = \Tygh\Registry::get('config');

@list($host, $port) = explode(':', $config['db_host']);
if (empty($port)) {
    $port = 3306;
}
if ($config['database_backend'] == 'pdo' && class_exists('PDO') && in_array('mysql', \PDO::getAvailableDrivers(), true)) {
    $adapter = 'mysql';
} else {
    $adapter = 'mysqli';
}

return array(
    'paths' => array(
        'migrations' => $config['dir']['migrations'],
    ),
    'environments' => array(
        'default_migration_table' => 'phinxlog' . TIME,
        'default_database' => 'development',
        'development' => array(
            'dir_root' => DIR_ROOT,
            'adapter' => $adapter,
            'host' => $host,
            'name' => $config['db_name'],
            'user' => $config['db_user'],
            'pass' => $config['db_password'],
            'port' => $port,
            'charset' => 'utf8',
            'prefix' => $config['table_prefix'],
        ),
    ),
);
