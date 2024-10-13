<div class="wrap">
	<h1 class="wp-heading-inline"><?=$this->get_page_title();?></h1>
	<hr class="wp-header-end">
	<?php $this->display_message(); ?>

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

	<?php
	$list = new ezDownloads_List_Table();
	$list->prepare_items();
	$list->display();
	?>
</div>
