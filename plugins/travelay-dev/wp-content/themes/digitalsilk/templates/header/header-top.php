<?php
if (
    !empty(get_field('header_top_content_left', 'options')['header_top_content'])
    || !empty(get_field('header_top_content_right', 'options')['header_top_content'])
) :
    ?>
    <div class="site-header__top">
        <div class="site-header__row container">
            <div class="site-header__row top-content">
                <div class="site-header__col -left">
                    <?php
                    while (have_rows('header_top_content_left', 'options')) :
                        the_row();
                        ?>
                        <?php
                        while (have_rows('header_top_content', 'options')) :
                            the_row();
                            ?>
                            <?php
                            if (get_row_layout()) {
                                get_template_part('templates/header/components/' . get_row_layout());
                            }
                            ?>
                        <?php endwhile; ?>
                    <?php endwhile; ?>
                </div>

                <div class="site-header__col -right">
                    <div class="site-header__regional-settings">
                        <?php
                        echo do_shortcode(
                            '[amadex_regional_settings mode="button" variant="capsule" position="header"]'
                        );
                        ?>
                    </div>
                    <?php
                    while (have_rows('header_top_content_right', 'options')) :
                        the_row();
                        ?>
                        <?php
                        while (have_rows('header_top_content', 'options')) :
                            the_row();
                            ?>
                            <?php
                            if (get_row_layout()) {
                                get_template_part('templates/header/components/' . get_row_layout());
                            }
                            ?>
                        <?php endwhile; ?>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
