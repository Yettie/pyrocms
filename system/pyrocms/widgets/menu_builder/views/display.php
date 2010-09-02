<?php if(is_array($links)): ?>
	<ul class="easy-menu">
		<?php foreach($links as $link): ?>
			<li><?php echo $link; ?></li>
		<?php endforeach; ?>
	</ul>	
<?php endif; ?>

