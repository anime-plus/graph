$(function()
{
	/* timeouts to prevent flickering */
	function stopTooltipRemoval(target)
	{
		var timeout = $(target).data('tooltip-hide-timeout');
		window.clearTimeout(timeout);
		$(target).data('tooltip-hide-timeout', null);
	}

	function startTooltipRemoval(target)
	{
	}

	$('[data-tooltip]').each(function()
	{
		$(this).mouseenter(function()
		{
			var target = $(this);
			var title = $(target).attr('data-tooltip');
			var posMy = 'center top';
			var posAt = 'center bottom';

			var div = $('<div class="tooltip"/>').append($('<span>').html(title.replace(/\|/g, '<br>').replace(/'/g, '&rsquo;')));
			$(target).data('tooltip', div);

			$('body').append(div);
			$(div).position(
			{
				of: $(target),
				my: posMy,
				at: posAt,
				collision: 'fit fit'
			}).mouseenter(function()
			{
				stopTooltipRemoval(target);
			}).mouseleave(function()
			{
				startTooltipRemoval(target);
			}).show();

		}).mouseleave(function()
		{
			var target = $(this);
			var div = $(target).data('tooltip');
			if (!div)
			{
				return;
			}
			div.remove();
		});
	});
});
