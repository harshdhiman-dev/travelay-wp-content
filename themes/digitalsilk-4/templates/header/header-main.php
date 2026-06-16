<?php if (
	! empty( get_field( 'header_main_content_left', 'options' )['header_main_content'] ) ||
	! empty( get_field( 'header_main_content_center', 'options' )['header_main_content'] ) ||
	! empty( get_field( 'header_main_content_right', 'options' )['header_main_content'] )
) : ?>

	<div class="site-header__main">
		<div class="site-header__row container">
			<div class="site-header__col -left">
				<?php
				while ( have_rows( 'header_main_content_left', 'options' ) ) :
					the_row();
					?>
					<?php
					while ( have_rows( 'header_main_content', 'options' ) ) :
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

			<div class="site-header__col -center">
				<?php
				while ( have_rows( 'header_main_content_center', 'options' ) ) :
					the_row();
					?>
					<?php
					while ( have_rows( 'header_main_content', 'options' ) ) :
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
				<?php
				while ( have_rows( 'header_main_content_right', 'options' ) ) :
					the_row();
					?>
					<?php
					while ( have_rows( 'header_main_content', 'options' ) ) :
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

		</div>
	</div>
<?php endif; ?>
