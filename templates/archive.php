# archive
<?php

$limit = 10;

foreach($files as $file) {
    if($dir->getBasename() === 'blog' && --$limit > 0) {
        if($file->isFile() && in_array($file->getExtension(), ['md', 'html'])) {
            list($content, $metadata) = render_file($file);
            echo template('content', [
                'file' => $file,
                'content' => $content,
                'metadata' => $metadata,
            ]);
        }
    } else {
        foreach($files as $file) {
            if($file->isFile() && in_array($file->getExtension(), ['md', 'html'])) {
                printf('<a href="%s">%s</a><br />', url($file), title($file));
            }
        }
    }
}
