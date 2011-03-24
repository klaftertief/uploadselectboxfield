(function($) {

	$(document).ready(function() {

		$('.field-uploadselectbox').each(function() {
			var $field = $(this),
				$label = $field.find('label:first'),
				$storage = $label.find('select'),
				$directories = $storage.find('optgroup'),
				$stage = $field.find('.stage'),
				$selection = $stage.find('.selection'),
				$directory = $('<select name="' + $label.attr('data-fieldname') + '[directory]"></select>'),
				$filter = $stage.find('.browser > input[type="text"]'),
				$counter = $stage.find('.counter'),
				$queue = $stage.find('.queue');
			
			$storage.attr('disabled', 'disabled');
			
			$queue.find('ul').html('<li class="empty message"><span>Start typing to search for files. Use <code>.</code> as whildcard.</span></li>'); // TODO translate
			
			$directories.each(function() {
				var label = $(this).attr('label');
				
				$directory.append('<option value="' + label + '">' + label + '</option>');
			});
			
			if($stage.is('.subdirectories')) {
				$('<div class="directory"></div>').html($directory).prependTo($queue);
			}
			
			$stage.bind('browsestart', function(event) {
				// browse();
			});
			
			$stage.bind('searchstart', function(event, strings) {
				browse();
			});
			
			$stage.bind('constructstop destructstop update', function(event, item) {
				sync(item);
			});
			
			$directory.bind('change', function(event) {
				browse();
			});
			
			function browse(){
				// var $list = $queue.find('ul').addClass('loading').hide().html(''),
				var $list = $queue.find('ul').addClass('loading'),
					directory = $directory.val(),
					html = '';
				
				$.ajax({
					url: Symphony.Context.get('root') + '/symphony/extension/uploadselectboxfield/getfilelist/',
					type: 'GET',
					dataType: 'json',
					data: {
						destination: $directory.val(),
						filter: $filter.val()
					},
					complete: function(xhr, textStatus) {
						//called when complete
					},
					success: function(data, textStatus, xhr) {
						if (!data) {
							$list.html('<li class="empty message"><span>No file found.</li>'); // TODO translate
						} else {
							$.each(data, function(index, val) {
								var path = directory + '/' + val,
									image = val.match(/\.(?:bmp|gif|jpe?g|png)$/i) ? '<img width="40" height="40" src="' + Symphony.Context.get('root') + '/image/2/40/40/5' + path + '"/>' : '';
								html += '<li class="preview" data-value="' + path + '">' + image + '<span class="file image"><em>' + directory + '/</em><br />' + val + '</span><input type="hidden" disabled="disabled" value="' + path + '" name="fields[files][]"/></li>';
							});
							console.log(html);
							$list.html(html);
							
							count(data.length);
							
							$stage.trigger('update');
						};
						
						// $list.slideDown('fast').removeClass('loading');
						$list.removeClass('loading');
					},
					error: function(xhr, textStatus, errorThrown) {
						//called when there is an error
					}
				});
			}
			
			function sync(item){
				$selection.find('input').each(function(index, item) {
					var $input = $(item);
					
					$input.attr('disabled', false);
					$queue.find('input[value="' + $input.val() + '"]').parent().addClass('selected');
				});
			}
			
			function count(size) {
				
				// No size
				if(!size && size !== 0) {
					$counter.hide();
				}
				
				// Show counter
				else {
					$counter.fadeIn('fast');
				
					// No items
					if(size == 0) {
						$counter.html(Symphony.Language.get('no results') + '<span>&#215;</span>');
					}
					
					// Single item
					else if(size == 1) {
						$counter.html(Symphony.Language.get('1 result', { count: 1 }) + '<span>&#215;</span>');
					}
					
					// Multiple items
					else{
						$counter.html(Symphony.Language.get('{$count} results', { count: size }) + '<span>&#215;</span>');
					}
				}
			};
			
		});

	});
	
})(jQuery.noConflict());
