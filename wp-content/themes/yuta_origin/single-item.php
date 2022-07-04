<!DOCTYPE html>
<html lang="ja">
    <head>
        <?php get_header(); ?>
    </head>
    <body>
        <!-- Navigation-->
        <?php get_template_part('includes/nav'); ?>

        <?php while(have_posts()):the_post(); ?>

            <?php
            $id = get_post_thumbnail_id();
            $img = wp_get_attachment_image_src($id);
            ?>

            <!-- Page Header-->
            <header class="masthead" style="background-image: url('<?php echo $img[0]; ?>')">
                <div class="container position-relative px-4 px-lg-5">
                    <div class="row gx-4 gx-lg-5 justify-content-center">
                        <div class="col-md-10 col-lg-8 col-xl-7">
                            <div class="post-heading">
                                <h1><?php the_title(); ?></h1>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Post Content-->
            <article class="mb-4">
                <div class="container px-4 px-lg-5">
                    <div class="row gx-4 gx-lg-5 justify-content-center">
                        <div class="col-md-10 col-lg-8 col-xl-7">
                            <?php the_content(); ?>
                            <dl>
                                <dt>カテゴリー</dt>
                                <?php
                                $terms = get_the_terms(get_the_ID(),'item_category');
                                foreach($terms as $term):
                                ?>
                                <dd><a href="<?php echo get_term_link($term->slug,'item_category') ?>"><?php echo htmlspecialchars($term->name); ?></a></dd>
                                <?php endforeach ?>
                                <dt>価格</dt>
                                <dd><?php echo number_format(get_field('価格')); ?></dd>
                                <dt>発売日</dt>
                                <dd><?php the_field('発売日'); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </article>

        <?php endwhile; ?>

        <!-- Footer-->
        <?php get_template_part('includes/footer'); ?>

        <?php get_footer(); ?>
    </body>
</html>
