BlogImageManager = Class.create();

BlogImageManager.prototype = {

    initialize : function(container)
    {
        this.container = $(container);

        if (!this.container)
            return;

        this.container.getElementsBySelector('form').each(function(form) {
            form.observe('submit',
                         this.onDeleteClick.bindAsEventListener(this));
        }.bind(this));
        
        var options = {
        	overlap		:	'horizontal',
        	constraint	:	false,
        	onUpdate	:	this.onSortUpdate.bind(this)
        };
        
        Sortable.create(this.container, options);

    },

	onDeleteClick	:	function(e)
	{
		Event.stop(e);
		var form = Event.element(e);
		
		var options = {
			method		:	form.method,
			parameters	:	form.serialize(),
			onSuccess	:	this.onDeleteSuccess.bind(this),
			onFailure	:	this.onDeleteFailure.bind(this)
		}
		
		message_write('Cancellazione immagine...');
		new Ajax.Request(form.action, options);
	},
	
	onDeleteSuccess	:	function(transport)
	{
		var json = transport.responseText.evalJSON(true);
		
		if (json.deleted) {
			var id_immagine = json.id_immagine
			
			var input = this.container.down('input[value=' + id_immagine + ']');
			if (input) {
				var options = {
					duration	: 0.3,
					afterFinish	: function(effect) {
						message_clear();
						effect.element.remove();
					}
				}
                new Effect.Fade(input.up('li'), options);
                return;				
			}
			this.onDeleteFailure(transport);
		}
	},
	
	onDeleteFailure	:	function(transport)
	{
		message_write('Errore nella cancellazione dell\'immagine');
	},
	
	onSortUpdate	:	function(draggable)
	{
		var form 	= this.container.down('form');
		var id_post = $F(form.down('input[name=id]'));
		
		var options = {
			method		:	form.method,
			parameters	:	'reorder=1'
						+	'&id=' + id_post
						+	'&'	+	Sortable.serialize(draggable),
			onSuccess	:	function() { message_clear(); }
		}
		
		message_write('Aggiornamento ordine immagine...');
		new Ajax.Request(form.action, options);
	}
}