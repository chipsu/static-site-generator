<?php

set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, $severity, $severity, $file, $line);
});

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/lib.php';

$config = array_merge([
    'source_dir' => $argv[1] ?? __DIR__ . '/pages/',
    'build_dir' => $argv[2] ?? __DIR__ . '/build/',
    'template_dir' => __DIR__ . '/templates/',
    'tags_path' => '/_tags/',
    'archive_size' => 20,
], file_exists('config.php') ? require 'config.php' : []);

create_build($config['source_dir'], '/');
create_index($config['source_dir'], '/');
create_tags($config['source_dir'], $config['build_dir'], $config['build_dir'] . $config['tags_path']);
