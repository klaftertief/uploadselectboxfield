
(function($) {

	/**
	 * This plugin add an interface for subsection management.
	 *
	 * @author: Nils Hörrmann, post@nilshoerrmann.de
	 * @source: http://github.com/nilshoerrmann/subsectionmanager
	 */
	$(document).ready(function() {

		// Language strings
		Symphony.Language.add({
			'There are no selected items': false,
			'Are you sure you want to delete this item? It will be remove from all entries. This step cannot be undone.': false,
			'There are currently no items available. Perhaps you want create one first?': false
		});

		// Initialize Subsection Manager
		$('div.field-uploadselectbox').each(function() {
			var manager = $(this),
				select = manager.find('select'),
				stage = manager.find('div.stage'),
				selection = stage.find('ul.selection'),
				queue = stage.find('div.queue'),
				queue_loaded = false;

		/*-----------------------------------------------------------------------*/

			// Constructing
			stage.bind('constructstop', function(event, item) {
				select.find('option[value='+$(item).attr('data-value')+']').attr('selected', 'selected');
			});

			// Destructing
			stage.bind('destructstart', function(event, item) {
				select.find('option[value='+$(item).attr('data-value')+']').removeAttr('selected');
			});

			// Prevent default events
			// Searching
			stage.bind('browsestart', function(event) {
				browse();
			});

			queue.delegate('li', 'click', function(event) {
				event.preventDefault();
			});

		/*-----------------------------------------------------------------------*/

			// Browse queue
			var browse = function() {

				// Append queue if it's not present yet
				if(!queue_loaded) {
					var list = queue.find('ul').addClass('loading').slideDown('fast'),
						destination = manager.find('input[name*=destination]').val();

					// Get queue items
					$.ajax({
						async: false,
						type: 'GET',
						dataType: 'html',
						url: Symphony.WEBSITE + '/symphony/extension/uploadselectboxfield/get/',
						data: {
							destination: destination
						},
						success: function(result) {

							// Empty queue
							if(!result) {
								$('<li class="message"><span>' + Symphony.Language.get('There are currently no items available. Perhaps you want create one first?') + '</li>').appendTo(list);
							}

							// Append queue items
							else {
								$(result).hide().appendTo(list);

								// Highlight selected items
								stage.trigger('update');
							}

							// Slide queue
							list.find('li').slideDown('fast', function() {
								$(this).parent('ul').removeClass('loading');
							});
							
							// Save status
							queue_loaded = true;
						}
					});
				}
			};

		});

	});

})(jQuery.noConflict());