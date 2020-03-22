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

# the templates to follow
$templates = array( 'archives/testimonials.twig', 'index.twig' );

# goes into twig
$context = Timber::get_context();

# says yes, this is an archive (might be redundant)
$context['is_archive'] = true;

$context['posts'] = new Timber\PostQuery();

$canonicalUrlResult = ot_get_option('canonical__testimonials_archive');
$context['canonical'] = false;
if ($canonicalUrlResult) {
    $context['canonical'] = $canonicalUrlResult;
}

Timber::render( $templates, $context );
