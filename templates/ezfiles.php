<div class="wrap">
<h1 class="wp-heading-inline"><?=$this->get_page_title();?></h1>
<a href="#" class="page-title-action">Add New Handle</a>
<hr class="wp-header-end">

<?php
$list = new ezFiles_List_Table();
$list->prepare_items();
$list->display();
?>
</div>
