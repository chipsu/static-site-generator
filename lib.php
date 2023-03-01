<?php

use \Michelf\MarkdownExtra;

function source_metadata($source, $pattern) {
    $metadata = [];
    $source = trim($source);
    $lines = preg_split($pattern, $source, 3, PREG_SPLIT_NO_EMPTY);
    if(count($lines) == 2) {
        foreach(explode("\n", $lines[0]) as $line) {
            $parts = explode(':', $line, 2);
            if(count($parts) == 2) {
                $metadata[strtolower($parts[0])] = json_decode(trim($parts[1]));
            }
        }
    }
    $markdown = count($lines) === 2 ? trim($lines[1]) : $source;
    return [$markdown, $metadata];
}

function markdown_metadata($source) {
    return source_metadata($source, '/[\-]{3}/');
}

function html_metadata($source) {
    return source_metadata($source, '/(\<\!\-\-\-)|(\-\-\-\>)/');
}

function render_markdown($source) {
    list($markdown, $metadata) = markdown_metadata($source);
    return [MarkdownExtra::defaultTransform($markdown), $metadata];
}

function render_file($file) {
    switch($file->getExtension()) {
    case 'md':
        return render_markdown(file_get_contents($file->getPathname()));
    case 'html':
        return html_metadata(file_get_contents($file->getPathname()));
    }
    return ['Unsupported content type. ' . $file->getExtension(), []]; 
}

function slugify($str) {
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($str));
}

function template($name, $data) {
    global $config;
    $__template__ = $config['template_dir'] . '/' . $name . '.php';
    ob_start();
    extract($data);
    require($__template__);
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
}

function get_build_path($file) {
    global $config;
    $extensions = [
        'md' => 'html',
        'html' => 'html',
    ];
    $path = str_replace($config['source_dir'], $config['build_dir'], $file->getPathname());
    $dir = $file->isDir() ? $path : dirname($path);
    if(!is_dir($dir) && !mkdir($dir, 0777, true)) {
        throw new Exception("Could not create directory $dir");
    }
    if($file->isDir()) {
        return $path;
    }
    $ext = $file->getExtension();
    $slug = slugify($file->getBasename('.' . $ext));
    if(isset($extensions[$ext])) {
        return $dir . '/' . $slug . '.' . $extensions[$ext];
    }
    return $dir . '/' . $slug . ($ext ? '.' . $ext : '');
}

function url($file) {
    global $config;
    $path = str_replace($config['build_dir'], '/', get_build_path($file));
    $path = str_replace('/index.html', '/', $path);
    return '/' . ltrim($path, '/');
}

function title($file) {
    list($_, $metadata) = render_file($file);
    if(isset($metadata['title'])) return $metadata['title'];
    return $file->getBasename();
}

function build_file($file) {
    $out = get_build_path($file);
    echo "Build file: $file => $out ...\n";
    list($content, $metadata) = render_file($file);
    file_put_contents($out, template('layout', [
        'title' => $metadata['title'] ?? 'Page ' . url($file),
        'content' => template('page', [
            'file' => $file,
            'content' => $content,
            'metadata' => $metadata,
        ]),
    ]));
}

function build_index_file($dir) {
    echo "Create index for $dir ...\n";
    $out = get_build_path($dir) . '/index.html';
    file_put_contents($out, template('layout', [
        'title' => 'Index of ' . url($dir),
        'content' => template('archive', [
            'dir' => $dir,
            'files' => new DirectoryIterator($dir),
        ]),
    ]));
}

function create_build($root, $path) {
    $dir = $root . $path;
    echo "Build: $path ...\n";
    foreach(new DirectoryIterator($dir) as $file) {
        if($file->isDir() && strpos($file->getBasename(), '.') === false) {
            create_build($root, $path . $file->getBasename() . '/');
        }
        if($file->isFile()) {
            build_file($file);
        }
    }
}

function create_index($root, $path) {
    $dir = $root . $path;
    echo "Finding missing index in $path ...\n";
    foreach(new DirectoryIterator($dir) as $file) {
        if($file->isDir() && strpos($file->getBasename(), '.') === false) {
            create_index($root, $path . $file->getBasename() . '/');
        }
    }
    if(!file_exists($dir . '/index.md')) {
        build_index_file(new SplFileInfo($dir));
    }
}

function create_tags($source_dir, $build_dir, $tags_build_dir) {
    echo "Creating tags ...\n";
    $tags = [];
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_dir)) as $file) {
        if(!$file->isFile()) continue;
        list($_, $metadata) = render_file($file);
        if(isset($metadata['tags'])) {
            foreach($metadata['tags'] as $tag) {
                $tags[slugify($tag)][] = $file;
            }
        }
    }
    foreach($tags as $tag => $files) {
        $out = get_build_path(new SplFileInfo($tags_build_dir . '/' . $tag . '/index.md'));
        file_put_contents($out, template('layout', [
            'title' => 'Tag ' . $tag,
            'content' => template('tag', [
                'tag' => $tag,
                'files' => $files,
            ]),
        ]));
    }
    ksort($tags);
    $out = get_build_path(new SplFileInfo($tags_build_dir . '/index.md'));
    file_put_contents($out, template('layout', [
        'title' => 'Tags',
        'content' => template('tags', [
            'tags' => $tags,
        ]),
    ]));
}
