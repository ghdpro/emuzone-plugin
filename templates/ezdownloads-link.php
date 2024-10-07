<div class="wrap">
	<h1 class="wp-heading-inline"><?=$this->get_page_title();?></h1>
	<span class="htmx-indicator page-title-action">
		<img src="<?php echo esc_url( includes_url() . 'js/tinymce/skins/lightgray/img//loader.gif' ); ?>" />
	</span>
	<hr class="wp-header-end">
	<?php $this->display_message(); ?>

	<h2 class="wp-heading-inline">Link <?php echo esc_html( $item->filename ); ?></h2>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><label for="q">Handle</label></th>
			<td>
				<input class="regular-text" type="search"
			            name="q" placeholder="Search handle..."
			            hx-post="<?php echo esc_html( admin_url( 'admin-post.php' ) . '?action=ezdownloads&form_action=search&id=' . esc_html( $item->id ) . '&_wpnonce=' . wp_create_nonce( 'ezdownloadssearch' ) ); ?>"
			            hx-trigger="input changed delay:500ms, search"
			            hx-target="#results"
			            hx-indicator=".htmx-indicator">
			</td>
		</tr>
		</tbody>
	</table>
	<table class="wp-list-table widefat fixed striped table-view-list search">
		<thead>
		<tr>
			<th>Handle</th>
			<th>Previous file</th>
			<th></th>
		</tr>
		</thead>
		<tbody id="results">
		</tbody>
	</table>
</div>
