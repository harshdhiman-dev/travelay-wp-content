<?php
/**
 * The template for displaying Comments.
 *
 * The area of the page that contains both current comments
 * and the comment form.  The actual display of comments is
 * handled by a callback to wdc_comments which is
 * located in the functions.php file.
 *
 * @package WordPress
 * @subpackage WDC
 * @since WDC 1.0
 */

if ( post_password_required() ) {
	return;
}?>
<?php
comment_form(
	array(
		'title_reply'          => __( 'Post a Comment', 'dstheme' ),
		'title_reply_before'   => '<h3 id="reply-title" class="comment-reply-title">',
		'title_reply_after'    => '</h3><p class="comment-note">' . __( 'Your email address will not be published. Required fields are marked with *', 'dstheme' ) . '</p>',
		'comment_notes_before' => '',
		'class_form'           => 'row',
		'logged_in_as'         => '',
		'label_submit'         => __( 'Submit Comment', 'dstheme' ),
		'class_submit'         => 'eso-btn color-primary type-solid shape-rounded size-small ntt nls',
		'submit_field'         => '<div class="form-submit col-12">%1$s %2$s</div>',
	)
);
?>
<div id="comments" class="comments-area">
	<?php if ( have_comments() ) { ?>
		<h4 class="comments-title"><?php comments_number( __( 'No Comments', 'dstheme' ), __( '1 Comment', 'dstheme' ), '% ' . __( 'Comments', 'dstheme' ) ); ?></h4>
		<span class="title-line"></span>
		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'avatar_size' => 70,
					'style'       => 'ul',
					'callback'    => 'wdc_comments',
					'type'        => 'all',
				)
			);
			?>
		</ol>
		<?php
		the_comments_pagination(
			array(
				'prev_text' => '<i class="eso eso-arrow-left" aria-hidden="true"></i> <span class="screen-reader-text">' . __( 'Previous', 'dstheme' ) . '</span>',
				'next_text' => '<span class="screen-reader-text">' . __(
					'Next',
					'dstheme'
				) . '</span> <i class="eso eso-arrow-right" aria-hidden="true"></i>',
			)
		);
		?>
	<?php } ?>
	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) { ?>
		<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'dstheme' ); ?></p>
	<?php } ?>
	<?php //phpcs:ignore comment_form();  ?>
</div>
