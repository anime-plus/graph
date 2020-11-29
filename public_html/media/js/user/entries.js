var resetHeight = function()
{
	$('body').css('min-height', '');
}

var slideUp = function(target, cb)
{
	if (typeof(cb) === 'undefined')
	{
		cb = resetHeight;
	}
	target.stop(true, true).slideUp('fast', cb);
}

var slideDown = function(target)
{
	target.show();
	target.css('height', 'auto');
	var height = target.height();
	target.stop(true, true); //this can make target hidden
	target.css('height', '0').show();
	target.animate({'height': height}, 'medium', resetHeight);
}

function toggleEntries(target, data, ajax, cb)
{
	if (typeof(cb) === 'undefined')
	{
		cb = function()
		{
		}
	}
	if (typeof(ajax) === 'undefined')
	{
		ajax = true;
	}
	var url = '/' + $('#user-name').val() + '/entries,' + $('#media').val();
	var uniqueId = JSON.stringify(data);
	if (target.data('unique-id') == uniqueId)
	{
		if (target.is(':visible'))
		{
			slideUp(target);
		}
		else
		{
			cb();
			slideDown(target);
		}
		return;
	}

	$('body').css('min-height', $('body').height() + 'px');

	target.data('unique-id', uniqueId);
	slideUp(target, function()
	{
		if (ajax)
		{
			$.get(url, data, function(response)
			{
				target.html(response);
				cb();
				slideDown(target);
			});
		}
		else
		{
			cb();
			slideDown(target);
		}
	});
}

$(function()
{
	$('.entries-wrapper').on('click', '.close', function(event)
	{
		var target = $(this).parents('.entries-wrapper');
		if ($(target).hasClass('singular'))
		{
			target = $(this).parents('.entries-wrapper');
		}
		slideUp(target);
		event.preventDefault();
	});
});
