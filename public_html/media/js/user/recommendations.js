$(function()
{
	$('.missing tbody tr').each(function()
	{
		var tr = $(this);
		var ul = tr.find('ul');
		ul.each(function()
		{
			$('<ul class="expand"/>').hide().insertAfter($(this));
		});
		var newTr = $('<tr><td colspan="2"/></tr>');
		var link = $('<a class="more" href="#">(more)</a>').click(function(e)
		{
			e.preventDefault();
			tr.find('.expand').slideDown(function()
			{
				link.slideUp();
			});
		});
		newTr.insertAfter(tr).find('td').append(link).hide();
	});
});



$(function()
{
	var userName = $('#user-name').val();
	var media = $('#media').val();
	var num1 = 8;
	var num2 = 5;

	if (typeof(Storage) === 'undefined')
	{
		$('.missing .delete-trigger').hide();
		return;
	}



	var readHidden = function(userName)
	{
		var storageHidden = typeof(localStorage.hidden) !== 'undefined'
			? JSON.parse(localStorage.hidden)
			: {};
		if (userName in storageHidden)
		{
			return storageHidden[userName];
		}
		return [];
	}



	var writeHidden = function(userName, hidden)
	{
		//filter out duplicates
		hidden = hidden.filter(function(el,index,arr)
		{
			return index == arr.indexOf(el);
		});
		var storageHidden = typeof(localStorage.hidden) !== 'undefined'
			? JSON.parse(localStorage.hidden)
			: {};
		storageHidden[userName] = hidden;
		//filter out empty users
		for (var key in storageHidden)
		{
			if (storageHidden[key].length == 0)
			{
				delete storageHidden[key]; //it's safe in js
			}
		}
		localStorage.hidden = JSON.stringify(storageHidden);
	}



	var collapseUls = function()
	{
		$('.tooltip').fadeOut(function()
		{
			$(this).remove();
		});
		$('.missing tbody.tainted tr').each(function()
		{
			var tr = $(this);
			var doExpand = false;
			var all = Math.max.apply(Math, tr.find('td').map(function()
			{
				return $(this).find('li:not(.hidden)').length;
			})) < num1;
			$(this).find('td').each(function()
			{
				var td = $(this);
				var ul1 = td.find('ul:first');
				var ul2 = td.find('ul.expand');

				var index = 0;
				td.find('li').each(function()
				{
					var li = $(this);
					if (index < num2 || all)
					{
						var justAppeared = li.parents('ul').hasClass('expand') && li.is(':not(:visible)');
						ul1.append(li);
						if (justAppeared)
							li.hide().slideDown();
					}
					else
					{
						ul2.append(li);
					}
					if (!li.hasClass('hidden'))
					{
						index ++;
					}
				});
				if (ul2.find('li').length > 0)
				{
					doExpand = true;
				}
			});
			if (tr.find('.proposed li:not(.hidden)').length == 0)
			{
				doExpand = false;
			}

			if (doExpand)
			{
				tr.next().find('td').slideDown();
			}
			else
			{
				tr.next().find('td').slideUp();
			}
		});
		$('.missing tbody.tainted').removeClass('tainted');
	}
	$('.missing tbody').addClass('tainted');
	collapseUls();



	var hide = function(target, fast)
	{
		var prevState = $.fx.off;
		if (fast)
		{
			$.fx.off = true;
		}
		target.addClass('hidden');
		target.slideUp(function()
		{
			var tr = target.parents('tr');
			var td = target.parents('td');
			var ul = target.parents('ul');
			target.hide();
			if (ul.find('li:not(.hidden)').length == 0)
			{
				tr.find('td').slideUp('fast');
			}
			tr.parents('tbody').addClass('tainted');
			collapseUls();
		});

		var hidden = readHidden(userName);
		var filtered = $.grep(hidden, function(item, index)
		{
			return item.indexOf(media) == 0;
		});
		$('.missing .undelete-msg strong').text(filtered.length);
		$('.missing .undelete-msg').slideDown();
		$.fx.off = prevState;
	}



	$('.missing .delete-trigger').click(function(e)
	{
		var key = $(this).parents('li').attr('data-id');
		var hidden = readHidden(userName);
		hidden.push(key);
		writeHidden(userName, hidden);
		hide($(this).parents('li'), false);
		e.preventDefault();
	});



	$('.missing .undelete-trigger').click(function(e)
	{
		var hidden = readHidden(userName);
		var filtered = $.grep(hidden, function(item, index)
		{
			return item.indexOf(media) != 0;
		});
		writeHidden(userName, filtered);

		$('.missing .undelete-msg').slideUp(function()
		{
			$('.missing li.hidden').each(function()
			{
				$(this).parents('tbody').addClass('tainted');
				$(this).parents('tr').find('td').slideDown();
				$(this).slideDown();
			});
			$('.missing li.hidden').removeClass('hidden');
			collapseUls();
		});
		e.preventDefault();
	});



	var hidden = readHidden(userName);
	for (var i in hidden)
	{
		var key = hidden[i];
		if (key.indexOf(media) == 0)
		{
			var target = $('[data-id=\'' + key + '\']');
			hide(target, true);
		}
	}
});
