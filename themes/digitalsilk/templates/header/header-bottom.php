<?php if ( ! empty( get_field( 'header_bottom_content_center', 'options' )['header_bottom_content'] ) ) : ?>

	<div class="site-header__bottom">

		<div class="site-header__row container">

			<div class="site-header__col -left">

			</div>

			<div class="site-header__col -center">

				<?php
				while ( have_rows( 'header_bottom_content_center', 'options' ) ) :
					the_row();
					?>

					<?php
					while ( have_rows( 'header_bottom_content', 'options' ) ) :
						the_row();
						?>

						<?php
						if ( get_row_layout() ) {
							get_template_part( 'templates/header/components/' . get_row_layout() );
						}
						?>

					<?php endwhile; ?>

				<?php endwhile; ?>
			</div>

			<div class="site-header__col -right">

			</div>

		</div>

	</div>

<?php endif; ?>
