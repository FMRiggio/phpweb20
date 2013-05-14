SearchSuggestor = Class.create();

SearchSuggestor.prototype = {
	
	url			:	'/search/suggestion',
	delay		:	0.2,
	
	container	:	null,
	input		:	null,
	timer		:	null,
	query		:	null,
	
	initialize	:	function(container)
	{
		this.container = $(container);
		if (!this.container)
			return;
		
		this.input	=	this.container.down('input[name=q]');
		if (!this.input)
			return;
		
		this.input.setAttribute('autocomplete', 'off');
		this.input.observe('keypress', this.onQueryChanged.bindAsEventListener(this));
	},

	onQueryChanged	:	function(e)
	{
		clearTimeout(this.timer);
		switch(e.keyCode) {
			case Event.KEY_RETURN:
				var term = this.getSelectedSuggestion();
				if (term.length > 0) {
					this.input.value = term;
					this.clearSuggestions();
				}
				return;
			case Event.KEY_ESC:
				this.clearSuggestions();
				return;
			case Event.KEY_DOWN:
				var total = this.getNumberOfSuggestions();
				var selected = this.getSelectedSuggestionIndex();
				
				if (selected == total -1) // ultima voce attualmente selezionata quindi deseleziono
					selected = -1;
				else if (selected < 0) //niente selezionato, seleziono la prima voce
					selected = 0;
				else 
					selected = (selected + 1);
				
				this.selectSuggestion(selected);
				Event.stop(e);
				return;
			case Event.KEY_PRESS:
				var total = this.getNumberOfSuggestions();
				var selected = this.getSelectedSuggestionIndex();
				
				if (selected == 0) //prima voce selezionata deseleziono tutto
					selected = -1;
				else if (selected < 0) //nulla selezionato, seleziono ultima voce
					selected = total -1;
				else
					selected = (selected - 1) % total;
				
				this.selectSuggestion(selected);
				Event.stop(e);
				return;
		}
		this.timer	=	setTimeout(this.loadSuggestion.bind(this), this.delay * 1000);
	},
	
	loadSuggestion	:	function()
	{
		var query = $F(this.input).strip();
		
		if (query.length == 0)
			this.clearSuggestions();
		
		if (query.length == 0 || query == this.query)
			return;
		
		this.query = query;
		
		var options = {
			parameters	:	'q=' + query,
			onSuccess	:	this.onSuggestionLoad.bind(this)
		};
		
		new Ajax.Request(this.url, options);
	},
	
	onSuggestionLoad	:	function(transport)
	{
		var json = transport.responseText.evalJSON(true);
		this.showSuggestions(json);
		
	},
	
	showSuggestions		:	function(suggestions)
	{
		
		this.clearSuggestions();
		
		if (suggestions.size() == 0)
			return;
		
		var ul	= Builder.node('ul');
		
		for (var i = 0; i < suggestions.size(); i++) {
			var li = $(Builder.node('li'));
			li.update(suggestions[i]);
			
			li.observe('mouseover', function(e) {
				Event.element(e).addClassName('active')
			});
			
			li.observe('mouseout', function(e) {
				Event.element(e).removeClassName('active')
			});
			
			li.observe('click', this.suggestionClicked.bindAsEventListener(this));
			
			ul.appendChild(li);
		}
		
		this.container.appendChild(ul);
	},
	
	clearSuggestions	:	function()
	{
		this.container.getElementsBySelector('ul').each(function(e){
			e.remove();
		});
		this.query = null;
	},
	
	suggestionClicked	:	function(e)
	{
		var elt = Event.element(e);
		var term = elt.innerHTML.strip();
		
		this.input.value = term;
		this.input.form.submit();
		
		this.clearSuggestions();
	},
	
	getNumberOfSuggestions	:	function()
	{
		return this.container.getElementsBySelector('li').size();
	},
	
	selectSuggestion	:	function(idx)
	{
		var items = this.container.getElementsBySelector('li');
		
		for (var i = 0; i < items.size(); i++) {
			if (i == idx) {
				items[i].addClassName('active');
			} else {
				items[i].removeClassName('active');
			}
		}
	},
	
	getSelectedSuggestionIndex	:	function()
	{
		var items = this.container.getElementsBySelector('li');
		
		for (var i = 0; i < items.size(); i++) {
			if (items[i].hasClassName('active')) {
				return i;
			}
		}
		return -1;
	},
	
	getSelectedSuggestion	:	function()
	{
		var items = this.container.getElementsBySelector('li');
		
		for (var i = 0; i < items.size(); i++) {
			if (items[i].hasClassName('active')) {
				return items[i].innerHTML.strip();
			}
		}
		return '';		
	}		
};