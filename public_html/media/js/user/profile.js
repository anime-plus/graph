function getProfileAge()
{
	var now = new Date();
	var then = new Date($('#profile-details').attr('data-date'));
	var diff = now - then;
	diff /= 1000.0;
	return diff;
}

function updatePosition()
{
	var target = $('.queue-pos');
	var positionUrl = $('#profile-details').attr('data-queue-pos-url');
	var enqueueUrl = $('#profile-details').attr('data-queue-add-url');
	var queueMinWait = parseInt($('#profile-details').attr('data-queue-min-wait'));
	var oldTooltip = target.attr('data-tooltip');
	var updatingBanned = $('#profile-details').attr('data-ban-state') == '1';
	$.get(positionUrl, function(data)
	{
		text = 'Pos. in queue: ' + data.pos;
		if (data.pos)
			target.text(text).wrapInner('<span>');
		else
		{
			target.removeAttr('data-tooltip');
			var profileAge = getProfileAge();
			if (profileAge < queueMinWait)
			{
				target.text('Using fresh data').wrapInner('<span>');
			}
			else if (updatingBanned)
			{
				target.text('Cannot update').wrapInner('<span>');
			}
			else
			{
				var updateLink = $('<a href="#">Click to update</a>');
				updateLink.click(function(e)
					{
						e.preventDefault();
						$.get(enqueueUrl, function(data)
						{
							target.attr('data-tooltip', oldTooltip);
							updatePosition();
						});
					});
				target.html(updateLink);
			}
		}
	});
}

function updateTime()
{
	var profileAge = getProfileAge();
	var target = $('.updated');
	var text = '';
	if (profileAge < 60)
	{
		text = 'just now';
	}
	else if (profileAge < 60 * 60)
	{
		text = (profileAge / 60).toFixed(0) + ' minutes ago';
	}
	else if (profileAge < 24 * 60 * 60)
	{
		text = (profileAge / 3600).toFixed(1) + ' hours ago';
	}
	else
	{
		text = (profileAge / 86400).toFixed(1) + ' days ago';
	}

	target.text(text).wrapInner('<span>');
}

$(function()
{
	updatePosition();
	updateTime();
});
