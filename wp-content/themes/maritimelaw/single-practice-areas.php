<?php
/**
 * The template for displaying the 'Post' single page template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Post content
$post = new TimberPost();
$context['post'] = $post;

$exclude_id = $post->id;

# We find out what terms our Post has first
$terms = [];
$categories = $post->categories();

# Predefine an empty related context
$related = [];

if(!empty($categories)){
    foreach($categories as $category){
        if($category->id != 1){
            $terms[] = $category->id;
        }
    }
}

# So, let's make a query for related posts now
$args = array(
    'post__not_in' => [ $exclude_id ],
    'posts_per_page' => 8,
    'tax_query' => [
        [
            'taxonomy' => 'category',
            'field'    => 'id',
            'terms'    => $terms,
            'operator' => 'IN'
        ]
    ]
);

# Finally, put the related articles somewhere cool
$context['related'] = Timber::get_posts( $args );
$context['terms'] = $terms;

# Final rendering
Timber::render( array( 'single-alt.twig' ), $context );