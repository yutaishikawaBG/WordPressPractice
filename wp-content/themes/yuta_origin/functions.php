<?php
function init_func(){
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('init','init_func');
?>