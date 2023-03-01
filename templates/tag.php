<h2>The tag <?php echo $tag; ?> is referenced from the following pages.</h2>
<?php

foreach($files as $file) {
    printf('<a href="%s">%s</a><br />', url($file), title($file));    
}
