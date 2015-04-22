$(function()
{
	$('.entries-trigger').click(function(e)
	{
		var target = $(this).parents('.achi-entry').find('.entries-wrapper');
		toggleEntries(target, [], false);
		e.preventDefault();
	});
	$('.previous-msg a').click(function(e)
	{
		var targets = $(this).parents('.section').find('.achi-entry.hidden');
		targets.each(function()
		{
			var target = $(this);
			if (target.is(':hidden'))
			{
				target.find('.entries-wrapper').hide();
				target.show();
				target.css('height', $(this).height());
				target.hide();
				target.slideDown(function()
				{
					target.css('height', '');
				});
			}
			else
			{
				target.slideUp();
			}
		});
		$(this).fadeOut('fast', function()
		{
			$(this).text($(this).text() == 'Show them' ? 'Hide them' : 'Show them').fadeIn('fast');
		});
		e.preventDefault();
	});
});
