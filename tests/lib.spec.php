<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../lib.php';

function expect_eq($actual, $expected) {
    assert($actual === $expected, sprintf('Expected %s was %s', json_encode($expected), json_encode($actual)));
}

describe('ini', function() {
    $data = [
        'zend.assertions' => '1',
        'assert.active' => '1',
    ];
    foreach($data as $key => $expected) {
        it($key, function() use($key, $expected) {
            $actual = ini_get($key);
            if($actual !== $expected) {
                throw new Exception("Expected ini setting $key to be $expected, was $actual");
            }
        });
    }
});

describe('lib', function() {
    beforeEach(function() {
        $this->config = [
            'source_dir' => __DIR__ . '/../example/',
            'build_dir' => sys_get_temp_dir() . '/static-site-output-' . uniqid(),
            'template_dir' => __DIR__ . '/../templates/',
            'tags_path' => '/_tags/',
            'archive_size' => 5,
        ];
        $GLOBALS['config'] = $this->config;
    });

    describe('slugify()', function() {
        it('should replace non alphanumerics with "-"', function() {
            $data = [
                ' ' => '-',
                ' _' => '-',
                '_' => '-',
                '__' => '-',
                '?' => '-',
                'abc123' => 'abc123',
            ];
            foreach($data as $input => $expected) {
                $actual = slugify($input);
                expect_eq($actual, $expected);
            }
        });
    });

    describe('markdown_metadata()', function() {
        it('should extract metadata and markdown', function() {
            $data = [
                "---\ndata: [1,2,3]\n---# Hello" => ["# Hello", ['data' => [1,2,3]]],
                "---\ndata: [1,2,3]\n---\n# Hello" => ["# Hello", ['data' => [1,2,3]]],
                "# Hello" => ["# Hello", []],
            ];
            foreach($data as $input => $expected) {
                $actual = markdown_metadata($input);
                expect_eq($actual, $expected);
            }
        });
    });

    describe('html_metadata()', function() {
        it('should extract metadata and html', function() {
            $data = [
                "<!---\ndata: [1,2,3]\n---><div>Hello</div>" => ["<div>Hello</div>", ['data' => [1,2,3]]],
                "<!---\ndata: [1,2,3]\n--->\n<div>Hello</div>" => ["<div>Hello</div>", ['data' => [1,2,3]]],
                "<div>Hello</div>" => ["<div>Hello</div>", []],
            ];
            foreach($data as $input => $expected) {
                $actual = html_metadata($input);
                expect_eq($actual, $expected);
            }
        });
    });

    describe('render_file()', function() {
        it('should render content and extract metadata', function() {
            $data = [
                __DIR__ . "/../example/index.md" => ["<h1>Hello</h1>\n", ['title' => 'Hello', 'tags' => [1,2,3]]],
                __DIR__ . "/../example/projects/software/javascript.html" => ["<div id=\"test\">nope</div>\r\n<script>\r\n    document.getElementById(\"id\").innerText = \"yep\";\r\n</script>",["title" => "Javascript","tags" => ["software","www"]]],
            ];
            foreach($data as $input => $expected) {
                $actual = render_file(new SplFileInfo($input));
                expect_eq($actual, $expected);
            }
        });
    });

    describe('build_index_file()', function() {
        it('should generate index', function() {
            build_index_file(new SplFileInfo($this->config['source_dir'] . '/projects/software/'));
            assert(is_file(($this->config['build_dir'] . '/projects/software/index.html')));
        });
    });
    
    describe('create_build()', function() {
        it('should build content', function() {
            create_build($this->config['source_dir'], '/');
            assert(is_file(($this->config['build_dir'] . '/projects/software/deluxe-paint.html')));
        });
    });

    describe('create_index()', function() {
        it('should build missing indexes', function() {
            create_index($this->config['source_dir'], '/');
            assert(is_file(($this->config['build_dir'] . '/projects/software/index.html')));
        });
    });

    describe('create_tags()', function() {
        it('should create tags', function() {
            create_tags($this->config['source_dir'], $this->config['build_dir'], $this->config['build_dir'] . $this->config['tags_path']);
            assert(is_file(($this->config['build_dir'] . '/_tags/index.html')));
        });
    });
});