<h1><?=$action_display?> Handle</h1>
<hr class="wp-header-end">
<?php $this->display_message(); ?>

<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<input type="hidden" name="action" value="<?=$this->get_menu_slug()?>">
	<input type="hidden" name="form_action" value="<?=$action?>">
	<?php wp_nonce_field( $this->get_menu_slug() . $action ); ?>
	<?php
	if ( $item->id ?? false )
		echo '<input type="hidden" name="id" value="' . $item->id . '">';
	?>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="emulator_id">Handle</label></th>
				<td><input name="emulator_id" id="emulator_id" type="text" class="regular-text" required value="<?php echo $item->emulator_id ?? ''; ?>"></td>
			</tr>
			<tr>
				<th></th>
				<td><input type="submit" class="button button-primary" value="Save"></td>
			</tr>
		</tbody>
	</table>
</form>
