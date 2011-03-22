(function($) {

	$(document).ready(function() {

		$('.field-uploadselectbox').each(function() {
			var $field = $(this),
				$storage = $field.find('label:first select'),
				$stage = $field.find('.stage'),
				$selection = $stage.find('.selection'),
				$subdirectory = $field.find('label.subdirectory'),
				$queue = $stage.find('.queue');
			
			if($stage.is('.subdirectory')) {
				// $subdirectory.prependTo($queue);
				$queue.prepend($subdirectory);
			}
			
			$stage.bind('browsestart', function(event) {
				browse();
			});
			
			$stage.bind('constructstop destructstop update', function(event) {
				sync();
			});
			
			function browse(){
				var $list = $queue.find('ul').addClass('loading').hide().html(''),
					$html = '';
				
				$.ajax({
					url: Symphony.Context.get('root') + '/symphony/extension/uploadselectboxfield/getfilelist/',
					type: 'GET',
					dataType: 'json',
					data: {
						destination: $subdirectory.find('select').val()
					},
					complete: function(xhr, textStatus) {
						//called when complete
					},
					success: function(data, textStatus, xhr) {
						if (!data.filelist.length) {
							$list.html('<li>Nothing</li>');
						} else {
							$.each(data.filelist, function(index, val) {
								$html += ('<li data-value="' + $subdirectory.find('select').val() + '/' + val + '"><span><em>' + $subdirectory.find('select').val() + '/</em><br />' + val + '</span></li>');
							});
							$list.html($html);
							
							$stage.trigger('update');
						};
						
						$list.slideDown('fast').removeClass('loading');
					},
					error: function(xhr, textStatus, errorThrown) {
						//called when there is an error
					}
				});
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
