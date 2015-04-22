var lastNum = 0;

function switchShowcaseTab(num)
{
	$('#showcase nav li').removeClass('active');
	$('#showcase nav li').eq(num).addClass('active');
	if (lastNum == num)
	{
		return;
	}
	lastNum = num;
	$('#showcase .tab').slideUp('fast');
	$('#showcase .tab').eq(num).slideDown('fast');
}

function nextShowcaseTab()
{
	var num = $('#showcase nav li.active').index();
	num ++;
	num %= $('#showcase nav li').length;
	switchShowcaseTab(num);
}

$(function()
{
	$('#main .search input').focus();

	var timeout = window.setInterval(nextShowcaseTab, 5000);

	$('#showcase nav li').each(function(i, index)
	{
		$(this).click(function(e)
		{
			window.clearTimeout(timeout);
			switchShowcaseTab(i);
			e.preventDefault();
		});
	});

	switchShowcaseTab(0);
});
