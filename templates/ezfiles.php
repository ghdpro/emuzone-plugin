<div class="wrap">
<h1 class="wp-heading-inline"><?=$this->get_page_title();?></h1>
<a href="<?php echo esc_html( admin_url( 'admin.php?page=' . esc_attr( $this->get_menu_slug() ) . '&action=add' ) ); ?>" class="page-title-action">Add New Handle</a>
<a href="<?php echo esc_html( admin_url( 'admin.php?page=fileman' ) ); ?>" class="page-title-action button-primary">File Manager</a>
<hr class="wp-header-end">
<?php $this->display_message(); ?>

<form method="post">
<?php
$list = new ezFiles_List_Table();
$list->prepare_items();
$list->search_box( 'search', 'search_id' );
$list->display();
?>
</form>
</div>
