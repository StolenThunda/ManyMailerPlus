<?php ee()->cp->load_package_css('jquery.dynatables'); ?>
<div class="box table-list-wrap">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
			<input class="btn submit" type="submit" value="<?=lang('search_templates_button')?>">
		</fieldset>
		<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php $this->embed('ee:_shared/table', $table); ?>

		<?=$pagination?>

		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<fieldset class="tbl-bulk-act hidden">
				
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<?php if ($this->enabled('remove')): ?>
						<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
					<? else: ?>
						<option value="use" data-confirm-trigger="selected" rel="modal-confirm-use"><?=lang('use')?></option>
					<?php endif; ?>
				</select>
				<button class="btn submit" data-conditional-modal="confirm-trigger" ><?=lang('submit')?></button>
			</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>
<?php if ( ! empty($templates)): ?>
	<?php foreach($templates as $template):
		$template = json_decode(json_encode($template), TRUE);
		?>
		<?php ee('CP/Modal')->startModal('template-' . $template['slug']); ?>
		<div class="modal-wrap modal-template-<?=$template['slug']?> hidden">
			<div class="modal">
				<div class="col-group">
					<div class="col w-16">
						<a class="m-close" href="#"></a>
						<div class="box">
							<h1><?=$template['name']?></h1>
							<div class="txt-wrap">
								<ul class="checklist mb">
									<li>
										<b><?=$template['subject']?>:</b><br />
										<?=lang('updated')?> :<?=$template['updated_at']?> <br><br>
										<?=lang('from')?> <?=$template['from_email']?> <?=$template['from_email']?><br><br>
										<div>
										
										<?=$template['code']?> 
										</div>
										<br><br>
										<pre>
										<?=$template['text']?> 
										</pre>
									</li>
								</ul>
								
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php ee('CP/Modal')->endModal(); ?>
<?php endforeach; ?>
<?php endif; ?>
<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> $table['base_url'],
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
