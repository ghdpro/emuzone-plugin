<div class="wrap">
	<h1 class="wp-heading-inline"><?=$this->get_page_title();?></h1>
	<hr class="wp-header-end">
	<?php $this->display_message(); ?>

	<?php
	$list = new ezDownloads_List_Table();
	$list->prepare_items();
	$list->display();
	?>
</div>
