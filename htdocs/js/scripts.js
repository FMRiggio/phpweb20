var settings = {
	messages			: 'messages',
	messages_hide_delay : 0.5
}

function init(e)
{
	var messages = $(settings.messages);
	
	if (messages && messages.visible()) {
		new Effect.Hightlight(messages);		
	}
	
	new SearchSuggestor('search');
}

function message_write(message)
{
	var messages = $(settings.messages);
	if (!messages)
		return;
	
	if (message.length == 0) {
		messages.hide();
		return;
	}
	
	messages.update(message);
	messages.show();
	new Effect.Highlight(messages);

}

function message_clear()
{
	setTimeout("message_write('')", settings.messages_hide_delay * 1000);
}

Event.observe(window, 'load', init);