<?php ee()->cp->load_package_css('jquery.dynatables'); ?>
<div class="box table-list-wrap" id='embed_templates'>
    <?php $this->embed('ee:_shared/table', $table); ?>
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
										<div id="<?=$template['slug']?>-code" class='template_code'>
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

