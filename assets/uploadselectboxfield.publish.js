(function($) {

	$(document).ready(function() {

		Symphony.Language.add({
			'No file found.': false,
			'Start typing to search for files. Use <code>.</code> as whildcard.': false
		});

		$('.field-uploadselectbox').each(function() {
			var $field = $(this),
				$label = $field.find('label:first'),
				$storage = $label.find('select'),
				$directories = $storage.find('optgroup'),
				$stage = $field.find('.stage'),
				$selection = $stage.find('.selection'),
				$uploader = $('<div class="uploader"></div>').insertAfter($selection),
				$directory = $('<select></select>'),
				directory = $directories.find('optgroup:first').attr('label'),
				$filter = $stage.find('.browser > input[type="text"]'),
				$counter = $stage.find('.counter'),
				$queue = $stage.find('.queue'),
				emptyMessage = '<li class="empty message"><span>' + Symphony.Language.get('Start typing to search for files. Use <code>.</code> as whildcard.') + '</span></li>';
			
			// some initial stuff
			$storage.attr('disabled', 'disabled').hide();
			
			$queue.find('ul').html(emptyMessage);
			
			$directories.each(function() {
				var label = $(this).attr('label');
				
				$directory.append('<option value="' + label + '">' + label + '</option>');
			});
			
			if($stage.is('.subdirectories')) {
				// $('<div class="directory"></div>').html($directory)[$stage.is('.searchable') ? 'prependTo' : 'appendTo']($queue);
				if ($stage.is('.searchable')) {
					$('<div class="directory"></div>').html($directory).prependTo($queue);
				} else {
					$('<div class="directory"></div>').html($directory).insertBefore($queue.find('ul'));
				};
			}
			
			$stage.bind('searchstart', function(event, strings) {
				search();
			});
			
			$stage.bind('constructstop destructstop update', function(event, item) {
				sync(item);
			});
			
			$stage.bind('constructstart', function(event, item) {
				if(!item) {
					create();
				}
			});
			
			if ($stage.is('.searchable')) {
				$directory.bind('change', function(event) {
					search();
				});

				$selection.delegate('li .file em', 'click', function(event) {
					var directory = $(this).text();
						// file = $(this).find('a').text();

					$directory.val(directory);
					 $filter.val('.');
					// $(event.target).is('a') ? $filter.val(file) : $filter.val('.');

					$stage.trigger('searchstart');
					$queue.find('ul').show();
				});
			}
			
			$('body').bind('click.uploadselectbox', function() {
				$stage.find('.uploader').html('');
			});
			
			// and now the heavy methods
			function search(){
				var $list = $queue.find('ul').addClass('loading'),
					html = '';
				
				directory = $directory.val() || directory;
				$list.html(emptyMessage);
				
				$.ajax({
					url: Symphony.Context.get('root') + '/symphony/extension/uploadselectboxfield/getfilelist/',
					type: 'GET',
					dataType: 'json',
					data: {
						destination: directory,
						filter: $filter.val()
					},
					complete: function(xhr, textStatus) {
					},
					success: function(data, textStatus, xhr) {
						if (!data) {
							$list.html('<li class="empty message"><span>' + Symphony.Language.get('No file found.') + '</span></li>');
						} else {
							$.each(data, function(index, val) {
								var path = directory + val,
									image = val.match(/\.(?:bmp|gif|jpe?g|png)$/i) ? '<img width="40" height="40" src="' + Symphony.Context.get('root') + '/image/2/40/40/5' + path + '"/>' : '';
								// TODO create function
								html += '<li class="preview" data-value="' + path + '">' + image + '<span class="file image"><em>' + directory + '</em><br />' + val + '</span><input type="hidden" disabled="disabled" value="' + path + '" name="fields[files][]"/></li>';
							});
							$list.html(html);
							
							count(data.length);
							
							$stage.trigger('update');
						};
						
						$list.removeClass('loading');
					},
					error: function(xhr, textStatus, errorThrown) {
					}
				});
			}
			
			function sync(item){
				$(item).find('input').attr('disabled', false);
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
			
			function create(item) {
				directory = $directory.val() || directory;
				
				$uploader.pluploadQueue({
					runtimes : 'html5',
					url : Symphony.Context.get('root') + '/symphony/extension/uploadselectboxfield/upload/?destination=' + directory,
					max_file_size : '10mb',
					chunk_size : '1mb',
					unique_names : true,
					filters : [
						// {title : "Image files", extensions : "jpg,gif,png"},
						// {title : "Zip files", extensions : "zip"}
					],
					preinit : {
						UploadFile: function(up, file) {
							up.settings.url = Symphony.Context.get('root') + '/symphony/extension/uploadselectboxfield/upload/?destination=' + ($directory.val() || directory);
						}
					},
					init: {
						FileUploaded: function(up, file, info) {
							$selection.append('<li data-value="' +  directory + file.name + '" class="preview"><img width="40" height="40" src="' + Symphony.Context.get('root') + '/image/2/40/40/5' +  directory + file.name + '"/><span class="file image"><em>' +  directory + '</em><br />' + file.name + '</span><input type="hidden" value="' +  directory + file.name + '" name="fields[files][]"/><a class="destructor">&#215;</a></li>');
						}
					}
				});
			};
		});

	});
	
})(jQuery.noConflict());
