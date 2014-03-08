
	$(document).ready(function()
	{

		$('#i_cm_delete.hide').hide();
		$('#i_cm_reset.hide').hide();
		$('#i_cm_selected.hide').hide();
		$('#i_cm_image.hide').hide();
		$('#i_cm_loading1').hide();
		$('#i_cm_loading2').hide();
		$('#i_cm_results').slideUp();

		// function for checking the "custom media" checkbox
		$('#i_cm_custom').click(function(e)
		{

			if ( $(this).is(':checked') )
			{
				$('#i_cm_action').val('add');
				$('#i_cm_image').empty();
				$('#i_cm_heading').val('');
				$('#i_cm_title').val('');
				$('#i_cm_description').val('');
				$('#i_cm_url').val('');
				$('#i_cm_heading').removeAttr('readonly');
				$('#i_cm_title').removeAttr('readonly');
				$('#i_cm_description').removeAttr('readonly');
				$('#i_cm_url').removeAttr('readonly');
				$('#i_cm_edit').attr('checked', 'checked');
				$('#i_cm_selected').slideDown('normal', function() { $('#i_cm_heading').focus(); } );
				$('#i_cm_reset:hidden').show();
			}
			else
			{
				$('#i_cm_heading').attr('readonly', 'readonly');
				$('#i_cm_title').attr('readonly', 'readonly');
				$('#i_cm_description').attr('readonly', 'readonly');
				$('#i_cm_url').attr('readonly', 'readonly');
				$('#i_cm_edit').removeAttr('checked');
				$('#i_cm_selected').slideUp();
			}

		});

		$('#cm_delete_link').click(function(e)
		{
			e.preventDefault();
			deleteMedia();
		});

		$('#cm_reset_link').click(function(e)
		{
			e.preventDefault();
			resetMedia();
		});

		// function for checking the "edit this" checkbox
		$('#i_cm_edit').click(function(e)
		{

			if ( $(this).is(':checked') )
			{
				$('#i_cm_heading').removeAttr('readonly');
				$('#i_cm_title').removeAttr('readonly');
				$('#i_cm_description').removeAttr('readonly');
				$('#i_cm_url').removeAttr('readonly');
				$('#i_cm_heading').focus();
			}
			else
			{
				$('#i_cm_heading').attr('readonly', 'readonly');
				$('#i_cm_title').attr('readonly', 'readonly');
				$('#i_cm_description').attr('readonly', 'readonly');
				$('#i_cm_url').attr('readonly', 'readonly');
			}

		});

		$('#i_cm_search').click(function(e)
		{
			e.preventDefault();
			ajax_search();
		});

		$('#i_cm_keywords').keypress(function(e)
		{
			if (e.keyCode == 13)
			{
				e.preventDefault();
				ajax_search();
			}
		});

		$('#cm_previous_link').on('click', function(e)
		{
			e.preventDefault();
			var page = $('#i_cm_page').val();
			page--;
			$('#i_cm_page').val(page);
			ajax_search();
		});

		$('#cm_next_link').on('click', function(e)
		{
			e.preventDefault();
			var page = $('#i_cm_page').val();
			page++;
			$('#i_cm_page').val(page);
			ajax_search();
		});

		$('#i_cm_select_small').click(function(e)
		{
			e.preventDefault();
			$('#i_cm_image').empty();
			$(document.createElement('img')).attr('src', $('#i_cm_small_image').val() ).appendTo('#i_cm_image');
			$('#i_cm_selected_image').val('small');
		});

		$('#i_cm_select_medium').click(function(e)
		{
			e.preventDefault();
			$('#i_cm_image').empty();
			$(document.createElement('img')).attr('src', $('#i_cm_medium_image').val() ).appendTo('#i_cm_image');
			$('#i_cm_selected_image').val('medium');
		});

		$('#i_cm_select_large').click(function(e)
		{
			e.preventDefault();
			$('#i_cm_image').empty();
			$(document.createElement('img')).attr('src', $('#i_cm_large_image').val() ).appendTo('#i_cm_image');
			$('#i_cm_selected_image').val('large');
		});

		$('#i_cm_image_remove').click(function(e)
		{
			e.preventDefault();
			$('#i_cm_image').empty();
			$('#i_cm_image_controls').hide();
			$('#i_cm_selected_image').val('none');
		});

	});

	function ajax_search()
	{

		$('#i_cm_loading1').show();
		$('#i_cm_loading2').show();
		$('#i_cm_results').show();

		var type = $("input[name='cm_type']:checked").val();
		var keywords = $('#i_cm_keywords').val();
		var page = $('#i_cm_page').val();

		var params = {
			type: type,
			keywords: keywords,
			page: page
		};

		$.post('./plugins/currentmedia/find.php', params, function(data) {
			$('#i_cm_loading1').hide();
			$('#i_cm_loading2').hide();
			if (data.length > 0) {
				$('#i_cm_results').html(data);
			}
		});
	}

	function selectThis(number)
	{

		var type = $("input[name='cm_type']:checked").val();
		var small_image = $('#i_cm_small_image' + number).val();
		var medium_image = $('#i_cm_medium_image' + number).val();
		var large_image = $('#i_cm_large_image' + number).val();
		var asin = $('#i_cm_asin' + number).val();
		var title = $('#i_cm_title' + number).val();
		var description = $('#i_cm_meta' + number).val();
		var url = $('#i_cm_url' + number).val();
		var listening = $('#i_cm_listening_words').val();
		var watching = $('#i_cm_watching_words').val();
		var reading = $('#i_cm_reading_words').val();
		var playing = $('#i_cm_playing_words').val();
		var is_entered = $('#i_cm_is_entered').val();

		$('#i_cm_image').empty();
		$(document.createElement('img')).attr('src', small_image).appendTo('#i_cm_image');
		$('#i_cm_selected_image').val('small');
		$('#i_cm_asin').val(asin);
		$('#i_cm_title').val(title);
		$('#i_cm_description').val(description);
		$('#i_cm_url').val(url);
		$('#i_cm_small_image').val(small_image);
		$('#i_cm_medium_image').val(medium_image);
		$('#i_cm_large_image').val(large_image);
		$('#i_cm_is_entered').val('1');

		// begin if: selecting an item when the form was previously blank; 'add' mode.
		if (is_entered == '0')
		{
			$('#i_cm_reset:hidden').show();
			$('#i_cm_action').val('add');
		}

		if (type == '1' || type == '5' || type == '2')
		{
			$('#i_cm_heading').val(watching);
		}
		else if (type == '3')
		{
			$('#i_cm_heading').val(reading);
		}
		else if (type == '4')
		{
			$('#i_cm_heading').val(listening);
		}
		else if (type == '6')
		{
			$('#i_cm_heading').val(playing);
		}

		$('#i_cm_image_controls:hidden').show();
		$('#i_cm_selected').show();

	}

	function resetMedia()
	{

		$('#i_cm_image').empty();
		$('#i_cm_selected_image').val('');
		$('#i_cm_asin').val('');
		$('#i_cm_title').val('');
		$('#i_cm_description').val('');
		$('#i_cm_url').val('');
		$('#i_cm_small_image').val('');
		$('#i_cm_medium_image').val('');
		$('#i_cm_large_image').val('');
		$('#i_cm_action').val('none');
		$('#i_cm_is_entered').val('0');

		$('#i_cm_reset:visible').hide();
		$('#i_cm_image_controls:visible').hide();
		$('#i_cm_selected:visible').hide();

	}

	function deleteMedia()
	{

		$('#i_cm_image').empty();
		$('#i_cm_selected_image').val('');
		$('#i_cm_asin').val('');
		$('#i_cm_title').val('');
		$('#i_cm_description').val('');
		$('#i_cm_url').val('');
		$('#i_cm_small_image').val('');
		$('#i_cm_medium_image').val('');
		$('#i_cm_large_image').val('');
		$('#i_cm_action').val('delete');
		$('#i_cm_is_entered').val('0');

		$('#i_cm_image_controls:visible').hide();
		$('#i_cm_selected:visible').hide();

	}
