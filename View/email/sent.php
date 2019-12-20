<?php ee()->cp->load_package_css('jquery.dynatables'); ?>
<div class="box table-list-wrap">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		
		<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
		<?php $this->embed('ee:_shared/table', $table); ?>
		<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
				</select>
				<button class="btn submit" data-conditional-modal="confirm-trigger" ><?=lang('submit')?></button>
			</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>
<?php foreach ($emails as $email): ?>
	<?php ee('CP/Modal')->startModal('email-' . $email->cache_id); ?>
	<div class="modal-wrap modal-email-<?=$email->cache_id?> hidden">
		<div class="modal">
			<div class="col-group">
				<div class="col w-16">
					<a class="m-close" href="#"></a>
					<div class="box">
						<h1><?=$email->subject?> (~<?=$email->cache_id?>)</h1>
						<div class="txt-wrap">
							<ul class="checklist mb">
								<li>
									<b><?=lang('sent')?>:</b> 
									<?php echo ee()->localize->human_time($email->cache_date->format('U'))?> 
									<?=lang('to')?> <?=$email->total_sent?> <?=lang('recipients')?>
								</li>
								<li>
									Recipient(s):  <br><?php echo str_replace(',', '<br>', $email->recipient);?> 
								</li>
							</ul>
							<?=ee('Security/XSS')->clean($email->message)?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php ee('CP/Modal')->endModal(); ?>
<?php endforeach; ?>

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
