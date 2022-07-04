<!DOCTYPE html>
<html lang="ja">
    <head>
        <?php get_header(); ?>
    </head>
    <body>
        <!-- Navigation-->
        <?php get_template_part('includes/nav'); ?>

        <!-- Page Header-->
        <header class="masthead" style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/img/home-bg.jpg')">
            <div class="container position-relative px-4 px-lg-5">
                <div class="row gx-4 gx-lg-5 justify-content-center">
                    <div class="col-md-10 col-lg-8 col-xl-7">
                        <div class="site-heading">
                            <h1>商品一覧</h1>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Main Content-->
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-md-10 col-lg-8 col-xl-7">

                    
                    <?php while(have_posts()):the_post(); ?>
                        <!-- Post preview-->
                        <div class="post-preview">
                            <a href="<?php the_permalink(); ?>">
                                <h2 class="post-title">
                                    <?php the_title(); ?>
                                </h2>
                            </a>
                            <p>価格：<?php echo number_format(get_field('価格')); ?></p>
                        </div>
                        <!-- Divider-->
                        <hr class="my-4" />
                    <?php endwhile; ?>


                   
                    <!-- Pager-->
                    <?php echo paginate_links(); ?>
                    <br/>
                    <?php previous_posts_link(); ?>
                    <?php next_posts_link(); ?>
                </div>
            </div>
        </div>
        <!-- Footer-->
        <?php get_template_part('includes/footer'); ?>

        <?php get_footer(); ?>
    </body>
</html>
