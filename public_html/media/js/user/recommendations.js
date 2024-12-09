$(function()
{
	var userName = $('#user-name').val();
	var media = $('#media').val();

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
			var ul = target.parents('ul');
			target.hide();
			if (ul.find('li:not(.hidden)').length == 0)
			{
				tr.find('td').slideUp('fast');
			}
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
		$('[data-id=\'' + key + '\']').each(function () {
			hide($(this), false);
		});
		e.preventDefault();
	});

	$('.missing .delete-trigger-alt-setting, .missing .delete-trigger-alt-version, .missing .delete-trigger-spin-off, .missing .delete-trigger-character, .missing .delete-trigger-summary, .missing .delete-trigger-side-story, .missing .delete-trigger-other').click(function (e) {
		e.preventDefault();

		var type = 'Other';

		var c = $(this).attr('class').replace('delete-trigger-', '');

		if (c === 'alt-setting') {
			type = 'Alt. setting';
		} else if (c === 'alt-version') {
			type = 'Alt. version';
		} else if (c === 'spin-off') {
			type = 'Spin-off';
		} else if (c === 'character') {
			type = 'Character';
		} else if (c === 'summary') {
			type = 'Summary';
		} else if (c === 'side-story') {
			type = 'Side story';
		}

		$('[data-tooltip="' + type + '"]').each(function () {
			var id = $(this).parents('li').attr('data-id');

			var ids = readHidden(userName);

			ids.push(id);

			writeHidden(userName, ids);

			$('[data-id="' + id + '"]').each(function () {
				hide($(this), true);
			});
		});
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
				$(this).parents('tr').find('td').slideDown();
				$(this).slideDown();
			});
			$('.missing li.hidden').removeClass('hidden');
		});
		e.preventDefault();
	});



	var hidden = readHidden(userName);
	for (var i in hidden)
	{
		var key = hidden[i];
		if (key.indexOf(media) == 0)
		{
			$('[data-id=\'' + key + '\']').each(function () {
				hide($(this), true);
			});
		}
	}
});
