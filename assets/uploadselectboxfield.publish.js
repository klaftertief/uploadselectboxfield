(function($) {

	$(document).ready(function() {

		$('.field-uploadselectbox').each(function() {
			var $field = $(this),
				$storage = $field.find('select'),
				$stage = $field.find('.stage'),
				$selection = $stage.find('.selection'),
				queue_loaded = false,
				$queue = $stage.find('.queue');
			
			$stage.bind('browsestart', function(event) {
				browse();
			});
			
			$stage.bind('constructstop destructstop update', function(event) {
				sync();
			});
			
			function browse(){
				if(queue_loaded == false) {
					var $list = $queue.find('ul').addClass('loading').slideDown('fast'),
						$html = '';
					
					$.ajax({
						url: Symphony.Context.get('root') + '/symphony/extension/uploadselectboxfield/getfilelist/',
						type: 'GET',
						dataType: 'json',
						data: {
							destination: '/workspace/media/images'
						},
						complete: function(xhr, textStatus) {
							//called when complete
						},
						success: function(data, textStatus, xhr) {
							if (!data.filelist.length) {
								$list.html('<li>Nothing</li>');
							} else {
								$.each(data.filelist, function(index, val) {
									$html += ('<li data-value="' + val + '"><span>' + val + '</span></li>');
								});
								$list.html($html);
								
								$stage.trigger('update');
							};
							
							$list.removeClass('loading');
							
							// Save status
							queue_loaded = true;
						},
						error: function(xhr, textStatus, errorThrown) {
							//called when there is an error
						}
					});
				}
			}
			
			function sync(){
				var $stock = $storage.find('option').removeAttr('selected');
				
				$selection.find('li').not('.drawer').not('.new').not('.message').not('.empty').each(function(index, item) {
					var $item = $(item),
						value = $item.attr('data-value'),
						$stored = $stock.filter('[value="' + value + '"]');
						
					$stored.attr('selected', 'selected');
				});
			}
		});

	});
	
})(jQuery.noConflict());
