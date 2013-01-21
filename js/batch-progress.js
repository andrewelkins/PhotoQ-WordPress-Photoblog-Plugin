/**
 * Shameless adaptation from Drupal's progress.js and batch.js
 */
jQuery(document).ready( function(){
	
	var updateCallback = function (progress, pb) {
		if (progress == 100) {
			pb.stopMonitoring();
			jQuery('div.percentage', this.element).html(batchProgressL10n.doneStr);
		}
	};

	var errorCallback = function(pb){
		var div = document.createElement('p');
		div.className = 'error';
		jQuery(div).html(batchProgressL10n.abortStr);
		jQuery('#batchProgress').prepend(div);
		jQuery('.submit').show();
	};

	//Attach the progress bar behavior to the batchProgress element
	if (jQuery('.updated').size() && typeof(batchId) != "undefined") {
		var progress = new progressBar('updateprogress', updateCallback, "POST", errorCallback);
		progress.setProgress(-1);
		jQuery('.updated:last').append('<div id="batchProgress"></div>');
		jQuery("#batchProgress").append(progress.element);
		progress.startMonitoring(ajaxurl, 10);
	}
	
});

/**
 * A progressbar object. Initialized with the given id. Must be inserted into
 * the DOM afterwards through progressBar.element.
 *
 * method is the function which will perform the HTTP request to get the
 * progress bar state. Either "GET" or "POST".
 *
 * e.g. pb = new progressBar('myProgressBar');
 *      some_element.appendChild(pb.element);
 */
progressBar = function (id, updateCallback, method, errorCallback) {
  var pb = this;
  this.id = id;
  this.method = method || "GET";
  this.updateCallback = updateCallback;
  this.errorCallback = errorCallback;
  this.element = document.createElement('div');
  this.element.id = id;
  this.element.className = 'photoQProgress';
  jQuery(this.element).html('<div class="percentage">&nbsp;</div>'+'<span class="progressBar" id="progressBar"></span>'+
                       '<div class="message">&nbsp;</div>');
};

/**
 * Set the percentage and status message for the progressbar.
 */
progressBar.prototype.setProgress = function (percentage) {
  if (percentage >= 0 && percentage <= 100) {
	var percent = Math.ceil(percentage * 350 / 100);
	jQuery('#progressBar').css("background", "url("+ batchProgressL10n.progressBarUrl +") no-repeat -" + (350-percent) + "px");
    jQuery('div.percentage', this.element).html(batchProgressL10n.waitStr1 + " " + this.roundToTwoDecimal(percentage) +"% " + batchProgressL10n.waitStr2);
  }
  if (this.updateCallback) {
    this.updateCallback(percentage, this);
  }
};

progressBar.prototype.roundToTwoDecimal = function(number){
	return Math.round(number*100)/100;
}

/**
 * Start monitoring progress via Ajax.
 */
progressBar.prototype.startMonitoring = function (uri, delay) {
	//hide the save buttons
	jQuery('.submit').hide();
	this.delay = delay;
	this.uri = uri;
	this.sendPing();
};

/**
 * Stop monitoring progress via Ajax.
 */
progressBar.prototype.stopMonitoring = function () {
	clearTimeout(this.timer);
	//show submit buttons
	jQuery('.submit').show();
	// This allows monitoring to be stopped from within the callback
	this.uri = null;
};

/**
 * Request progress data from server.
 */
progressBar.prototype.sendPing = function () {
  if (this.timer) {
    clearTimeout(this.timer);
  }
  if (this.uri) {
    var pb = this;
    // When doing a post request, you need non-null data. Otherwise a
    // HTTP 411 or HTTP 406 (with Apache mod_security) error may result.
    jQuery.ajax({
      type: this.method,
      url: this.uri,
      data: "action=photoq_batchProcessing&id=" + batchId +"&_ajax_nonce=" + ajaxNonce, 
      dataType: 'json',
      success: function (progress) {
        // Display errors
        if (progress.errMsg) {
        	pb.displayError(progress.errMsg, false);
        }
        // Update display
        pb.setProgress(progress.percentage);
        // Schedule next timer
        pb.timer = setTimeout(function() { pb.sendPing(); }, pb.delay);
      },
      error: function (xmlhttp, errorType, errorThrown) {
    	  if(errorType == 'timeout')
    		  pb.displayError('timeout ' + errorThrown + ' ' + xmlhttp.responseText, true);
    	  else
    		  pb.displayError(xmlhttp.responseText, true);
      }
    });
  }
};

/**
 * Display errors on the page.
 */
progressBar.prototype.displayError = function (string, abort) {
	var error = document.createElement('div');
	error.className = 'error';
	error.innerHTML = '<h4>' + batchProgressL10n.foundErrs + '</h4>' + string;
	if(abort){
		jQuery(this.element).before(error).hide();
		if(this.errorCallback) {
			this.errorCallback(this);
		}
	}else{
		jQuery(this.element).before(error);
	}
};