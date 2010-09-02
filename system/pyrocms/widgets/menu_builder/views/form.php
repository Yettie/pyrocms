<div class="tabs">
		
			<ul class="tab-menu">
				<li><a href="#ez-modules"><span>Module Links</span></a></li>
				<li><a href="#ez-pages"><span>Page Links</span></a></li>
				<li><a href="#ez-create"><span>Create Links</span></a></li>
			</ul>
			
		
			<div id="ez-modules">
				<ul class="dragabble-items">
				<?php if(!empty($modules)): ?>
					<?php foreach($modules as $module): ?>
						<li><?php echo $module; ?></li>
					<?php endforeach; ?>
				<?php endif; ?>
				</ul>
				
			</div>
			
			<div id="ez-pages">
				<ul class="dragabble-items">
				<?php if(!empty($pages)): ?>
					<?php foreach($pages as $page): ?>
						<li><?php echo $page; ?></li>
					<?php endforeach; ?>
				<?php endif; ?>
				</ul>
			</div>
			
			<div id="ez-create">
				<ol>
					<li class="even">
						<label>Url</label>
						<?php echo form_input('url', 'http://'); ?>
		
					</li>
					<li class="even">
						<label>Display Text</label>
						<?php echo form_input('display', ''); ?>
					</li>
					<li>
						<input type="submit" name="add-link" id="add-created" value="Add" />
					</li>
	
				</ol>
			</div>
</div>


</div>
<?php echo form_hidden('link_list', $options['link_list'], 'id="link-drop"'); ?>
<h3>Current Selected Items</h3>
<em>click to remove from list</em>
<ul id="ez-list">
	<?php if(!empty($links_array)): ?>
		<?php foreach($links_array as $link): ?>
			<li><?php echo $link; ?></li>
		<?php endforeach; ?>
	<?php endif; ?>
</ul>

<script type="text/javascript">
jQuery(document).ready(function() {

	
	jQuery('.tabs').tabs();
	
	jQuery('#add-created').click(function(e) {
		e.preventDefault();
		var href = jQuery('input[name=url]').val();
		var text = jQuery('input[name=display]').val();
		var current_val = jQuery('input[name=link_list]').val();
		var seperator = '';
		if(current_val.length > 0) {
			var seperator = '|';
		}
		var link = '<a href="'+href+'" title="'+text+'">'+text+'</a>';
		jQuery('input[name=link_list]').val(current_val + seperator + link);
		jQuery('#ez-list').append('<li>' + link + '</li>');
	});
	
	jQuery('.dragabble-items li').click(function(e) {
		e.preventDefault();
		var sObj = jQuery(this);
		var clicked_item = jQuery(this).html();
		var current_val = jQuery('input[name=link_list]').val();
		var seperator = '';
		if(current_val.length > 0) {
			var seperator = '|';
		}
		jQuery('input[name=link_list]').val(current_val + seperator + clicked_item);
		jQuery('#ez-list').append('<li>' + clicked_item + '</li>');
		sObj.remove();
		
	});
	
	
	
	jQuery('#ez-list li').click(function(e) {
		e.preventDefault();
		var dObj = jQuery(this);
		var link = jQuery(this).html();
		var current_list = jQuery('input[name=link_list]').val();
		var list_array = new Array();
		list_array = current_list.split('|');
		list_array = jQuery.grep(list_array, function(value) {
    					return value != link;
				});

		var new_val = list_array.join('|');
		jQuery('input[name=link_list]').val(new_val);
		dObj.remove();
	});
	
	
});
</script>


