Event.observe(window, 'load', function() {
	var publishButton = $('status-publish');
	var unpublishButton = $('status-unpublish');
	var deleteButton = $('status-delete');
	
	if (publishButton) {
		publishButton.observe('click', function(e) {
			if (!confirm("Fai click su OK per pubblicare questo post"))
				Event.stop(e);
		});	
	}

	if (unpublishButton) {
		unpublishButton.observe('click', function(e) {
			if (!confirm("Fai click su OK per vedere l'anteprima di questo post"))
				Event.stop(e);
		});	
	}

	if (deleteButton) {
		deleteButton.observe('click', function(e) {
			if (!confirm('Fai click su OK per cancellare permanentemente questo post'))
				Event.stop(e);
		});
	}	
	
	var im = new BlogImageManager('post_images');
});