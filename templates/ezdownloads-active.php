<h1><?=$action_display?> Download</h1>
<hr class="wp-header-end">
<?php $this->display_message(); ?>

<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<input type="hidden" name="action" value="<?=$this->get_menu_slug()?>">
	<input type="hidden" name="form_action" value="<?=$action?>">
	<?php wp_nonce_field( $this->get_menu_slug() . $action ); ?>
	<input type="hidden" name="id" value="<?=$item->id?>'">
	Are you sure you want to set the active download for handle <b><?=$item->handle?></b> to <b><?=$item->filename?></b>?
	<table class="form-table">
		<tbody>
		<tr>
			<th></th>
			<td>
				<input type="submit" class="button button-primary" value="Set Active">
			</td>
		</tr>
		</tbody>
	</table>
</form>
