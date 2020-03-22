<?php
/**
 * The template for displaying the 'Sign-Up' single page template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Post content
/** @var Timber\Post $post */
$post = new TimberPost();
$context['post'] = $post;

$exclude_id = $post->id;

# We find out what terms our Post has first
$terms = [];
$tags = [];
$categories = $post->categories();
$post_tags = $post->tags();

$context['main_category_link'] = false;

# Predefine an empty related context
$related = [];

if(!empty($categories)){

    /** @var Timber\Term $main_category */
    $main_category = $categories[0];
    $context['main_category_link'] = $main_category->link();

    foreach($categories as $category){
        if($category->id != 1){
            $terms[] = $category->id;
        }
    }
}

if(!empty($tags)){
    foreach($tags as $tag){
        if($tag->id != 1){
            $tags[] = $tag->id;
        }
    }
}

# So, let's make a query for related posts now
$args = array(
    'post_type' => 'post',
    'post__not_in' => [ $exclude_id ],
    'posts_per_page' => 8,
    'tax_query' => [
        'relation' => 'OR',
        [
            'taxonomy' => 'category',
            'field'    => 'id',
            'terms'    => $terms,
            'operator' => 'IN'
        ],[
            'taxonomy' => 'post_tag',
            'field'    => 'id',
            'terms'    => $tags,
            'operator' => 'IN'
        ]
    ]
);

# Finally, put the related articles somewhere cool
$context['related'] = Timber::get_posts( $args );
$context['terms'] = $terms;

# Final rendering
Timber::render( array( 'single-signup.twig' ), $context );