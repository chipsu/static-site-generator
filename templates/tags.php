<?php

foreach($tags as $tag => $files) {
    printf('<a href="%s" class="tag tag-%d">%s</a> ', htmlspecialchars($config['tags_path'] . $tag), count($files), htmlspecialchars($tag));
}
