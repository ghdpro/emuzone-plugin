<h1><?=$action_display?> Download <?php echo esc_html( $item->filename ); ?></h1>
<hr class="wp-header-end">
<?php $this->display_message(); ?>

<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
	<input type="hidden" name="action" value="<?=$this->get_menu_slug()?>">
	<input type="hidden" name="form_action" value="<?=$action?>">
	<?php wp_nonce_field( $this->get_menu_slug() . $action ); ?>
	<input type="hidden" name="id" value="<?=$item->id?>">
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="version">Version</label></th>
				<td><input name="version" id="version" type="text" class="regular-text" value="<?php echo $item->version ?? ''; ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="release_date">Release date</label></th>
				<td><input name="release_date" id="release_date" type="text" class="regular-text" value="<?php echo $item->release_date ?? date( 'Y-m-d' ); ?>">
				<span class="descriptiopn" style="color: #A0A5AA"><br>The release date of the emulator download. Defaults to today's date, but please change to match actual date.</span></td>
			</tr>
			<tr id="showmore" <?php if ( empty( $item->name) ) echo ' style="display:none"'; ?>>
				<th></th>
				<td><span class="button button-secondary" onclick="document.getElementById('optional').style.display = 'block'; document.getElementById('showmore').style.display = 'none';" href="">Show more fields</span></td>
			</tr>
		</tbody>
	</table>
	<table class="form-table" id="optional"  <?php if ( ! empty( $item->name) ) echo ' style="display:none"'; ?>>
		<tbody>
			<tr>
				<th scope="row"><label for="filename">Filename</label></th>
				<td><input name="filename" id="filename" type="text" class="regular-text" required value="<?php echo $item->filename ?? ''; ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="name">Name</label></th>
				<td><input name="name" id="name" type="text" class="regular-text" required value="<?php echo $item->name ?? ''; ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="description">Description</label></th>
				<td><input name="description" id="description" type="text" class="large-text" value="<?php echo $item->description ?? ''; ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="platform">Platform</label></th>
				<td>
					<?php
					$platformtypes = array( 'N/A', 'DOS', 'Windows', 'Linux', 'Mac', 'Windows (64-bit)', 'Windows (32-bit)' );
					echo html_selection_box( 'platform', $platformtypes, $item->platform );
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="license">License</label></th>
				<td>
					<?php
					$licensetypes = array( 'N/A', 'Public Domain', 'Freeware', 'Shareware', 'Demo', 'Open-Source' );
					echo html_selection_box( 'license', $licensetypes, $item->license );
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="homepage1_url">Homepage URL</label></th>
				<td><input name="homepage1_url" id="homepage1_url" type="text" class="large-text" value="<?php echo $item->homepage1_url ?? ''; ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="source1_url">Source URL</label></th>
				<td><input name="source1_url" id="source1_url" type="text" class="large-text" value="<?php echo $item->source1_url ?? ''; ?>">
				<span class="descriptiopn" style="color: #A0A5AA"><br>This URL is used to automatically match new downloads in the future. URL should be specific to the emulator, but not specific to a particular version.</span></td>
			</tr>
		</tbody>
	</table>
	<table class="form-table">
		<tbody>
			<tr>
				<th></th>
				<td><input type="submit" class="button button-primary" value="Save"></td>
			</tr>
		</tbody>
	</table>
</form>
