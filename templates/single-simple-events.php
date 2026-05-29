<?php

/**
 * Default single event template.
 *
 * Theme-overridable: copy to your theme as single-simple-events.php.
 * schema.org Event JSON-LD is emitted on wp_head (see Simple_Events_Post_Type).
 *
 * @package Simple_Events_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="simple-events-single">
    <?php
    while (have_posts()) :
        the_post();
        $sec_id = get_the_ID();
        ?>
        <article <?php post_class('simple-events-single__event'); ?>>

            <?php echo Simple_Events_Renderer::image($sec_id, array('size' => 'large', 'class' => 'simple-events-single__image')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <header class="simple-events-single__header">
                <h1 class="simple-events-single__title"><?php the_title(); ?></h1>

                <div class="simple-events-single__meta">
                    <?php
                    // Renderer output is already escaped internally.
                    echo Simple_Events_Renderer::date($sec_id, array('class' => 'simple-events-single__date')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Simple_Events_Renderer::time($sec_id, array('class' => 'simple-events-single__time')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo Simple_Events_Renderer::location($sec_id, array('icon' => true, 'class' => 'simple-events-single__location')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                </div>
            </header>

            <div class="simple-events-single__content">
                <?php the_content(); ?>
            </div>

            <?php
            $sec_cats = Simple_Events_Renderer::categories($sec_id, array('link' => true, 'class' => 'simple-events-single__categories'));
            if ('' !== $sec_cats) :
                ?>
                <footer class="simple-events-single__footer">
                    <?php echo $sec_cats; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </footer>
            <?php endif; ?>

        </article>
    <?php endwhile; ?>
</main>

<?php
get_footer();
