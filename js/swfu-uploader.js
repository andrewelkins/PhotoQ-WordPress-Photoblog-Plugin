var swfu; 
var uplsize = 0;
var quotaAvailable = swfuUploadL10n.quotaAvailable;

window.onload = function () { 
	swfu = new SWFUpload({ 
		debug: false,
		upload_url : swfuUploadL10n.uploadUrl.replace("&amp;", "&"), 
		flash_url : swfuUploadL10n.flashUrl, 
		file_size_limit : swfuUploadL10n.fileSizeLimit,
		file_queue_limit: 0,
		file_types : "*.jpg;*.gif;*.png;*.JPG;*.GIF;*.PNG",
		file_types_description: "Web Image Files...",
		post_params : { "auth_cookie" : swfuUploadL10n.authCookie,
						"logged_in_cookie": swfuUploadL10n.loggedInCookie,
						"batch_upload" : "1",
						"photoq-uploadBatch" : swfuUploadL10n.nonce 
		},
		file_queue_error_handler : fileQueueError,
		file_queued_handler : fileQueued, 
		file_dialog_complete_handler : fileDialogComplete, 
		upload_start_handler : uploadStart,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : uploadSuccess,
		upload_complete_handler : uploadComplete,
		button_text: '<span class="button">'+swfuUploadL10n.buttonText+'<\/span>',
		button_text_style: '.button { color: #ffffff; text-align: center; font-size: 11px; font-weight: bold; font-family:"Lucida Grande","Lucida Sans Unicode",Tahoma,Verdana,sans-serif; }',
		button_height: "22",
		button_width: "134",
		button_image_url: swfuUploadL10n.buttonImageUrl,
		button_placeholder_id: "flash-browse-button"
	}); 

};