$(function()
{
	$('.history-monthly .entries-trigger').click(function(e)
	{
		var key = $(this).attr('data-key');
		toggleEntries($('.history-monthly .entries-wrapper'), {'sender': 'monthly-history', 'filter-param': key}, false, function()
		{
			$('.history-monthly .entries-wrapper .entries-sub-wrapper').hide();
			$('.history-monthly .entries-wrapper #' + key).show();
		});
		e.preventDefault();
	});
});
