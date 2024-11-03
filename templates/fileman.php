<div class="wrap">
<h1 class="wp-heading-inline"><?=$this->get_page_title();?></h1>
<a href="<?php echo esc_html( admin_url( 'admin.php?page=ezfiles&action=add' ) ); ?>" class="page-title-action">Add New Handle</a>
<hr class="wp-header-end">
<?php
// All messages here will be from EZDownloads class
$ezdownloads = new EZDownloads( '' ); // Template path not important here
$ezdownloads->display_message();
?>

<script>
	document.addEventListener("htmx:confirm", function(e) {
		if (!e.detail.target.hasAttribute('hx-confirm')) return
		e.preventDefault()
		Swal.fire({
			title: "Confirm",
			icon: "warning",
			html: `${e.detail.question}`,
			showCancelButton: true
		}).then(function(result) {
			if (result.isConfirmed) {
				e.detail.issueRequest(true);
			}
		})
	})
</script>

<div class="action-container">
	<div class="action-box">
		<h2>Transfer</h2>
		<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="ezdownloads">
			<input type="hidden" name="form_action" value="transfer">
			<?php wp_nonce_field( 'ezdownloadstransfer' ); ?>
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row"><label for="url">URL</label></th>
					<td><input name="url" id="url" type="text" class="large-text" required></td>
				</tr>
				<tr>
					<th></th>
					<td><input type="submit" class="button button-primary" value="Transfer"></td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>
	<div class="action-box">
		<h2>Upload</h2>
		<form id="form" hx-encoding="multipart/form-data" hx-post="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
			<progress id="progress" value="0" max="100"></progress>
			<input type="hidden" name="action" value="ezdownloads">
			<input type="hidden" name="form_action" value="upload">
			<?php wp_nonce_field( 'ezdownloadsupload' ); ?>
			<input type="hidden" name="MAX_FILE_SIZE" value="268435456" />
			<input type="file" name="file" required>
			<button class="button button-primary">Upload</button>
		</form>
		<script>
			htmx.on('#form', 'htmx:xhr:progress', function (evt) {
				htmx.find('#progress').setAttribute('value', evt.detail.loaded / evt.detail.total * 100)
			});
		</script>
	</div>
</div>

<h2>Downloads</h2>
<form method="post">
<?php
$list = new ezDownloads_List_Table();
$list->prepare_items();
$list->search_box( 'search', 'search_id' );
$list->display();
?>
</form>

</div>
