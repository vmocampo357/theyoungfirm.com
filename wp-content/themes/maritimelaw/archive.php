<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

$templates = array( 'archives/posts.twig', 'index.twig' );

$context = Timber::get_context();

# says yes, this is an archive (might be redundant)
$context['is_archive'] = true;

$context['title'] = 'Archive';
if ( is_tag() ) {
    $context['title'] = "Posts tagged: " . single_tag_title( '', false );
} else if ( is_category() ) {
    $context['title'] = single_cat_title( '', false ) . " Posts";
    $context['category'] = get_the_category();
}

/** @var Timber\PostQuery $postQuery */
$postQuery = new Timber\PostQuery();

$context['posts'] = $postQuery;

$postTypeArchiveTitle = post_type_archive_title('',false);
$context['canonical'] = false;
$context['noindex'] = false;

switch ($postTypeArchiveTitle) {
    /*
     * Staff Members Archive
     *
     */
    case('Staff Members'):
        $canonicalUrlResult = ot_get_option('canonical__staff_archive');
        if ($canonicalUrlResult) {
            $context['canonical'] = $canonicalUrlResult;
        }
        $context['noindex'] = true;
        break;
    case('Archive'):
    case('Content Snippets'):
    case('FAQ Types'):
    case('Claim Types'):
    case('Case Result Tags'):
        $context['noindex'] = true;
        break;
}

Timber::render( $templates, $context );
