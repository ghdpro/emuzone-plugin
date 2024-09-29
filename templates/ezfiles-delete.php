<h1><?=$action_display?> Handle</h1>
<hr class="wp-header-end">
<?php $this->display_message(); ?>

<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<input type="hidden" name="action" value="<?=$this->get_menu_slug()?>">
	<input type="hidden" name="form_action" value="<?=$action?>">
	<?php wp_nonce_field( $this->get_menu_slug() . $action ); ?>
	<input type="hidden" name="id" value="<?=$item->id?>'">
	<?php
		$can_delete = true;
		if ( $item->active_file > 0 )
			$can_delete = false;
	?>
	<?php if ( $can_delete ): ?>
		Are you sure you want to delete handle <b><?=$item->emulator_id?></b>?
	<?php else: ?>
		Handle <b><?=$item->emulator_id?></b> has associated downloads and cannot be deleted.
	<?php endif; ?>
	<table class="form-table">
		<tbody>
		<tr>
			<th></th>
			<td>
				<?php if ( $can_delete ): ?>
					<input type="submit" class="button button-primary" value="Delete">
				<?php else: ?>
					<a href="<?php echo esc_html( admin_url( 'admin.php?page=' . esc_attr( $this->get_menu_slug() ) ) ); ?>" class="button button-secondary">Return</a>
				<?php endif; ?>
			</td>
		</tr>
		</tbody>
	</table>
</form>
