function YcdSimpleCountdown()
{
	this.countdownRun = true;
	this.isActive = true;
	this.seconds = 0;
	this.id = 0;
	this.doubeleDigits = false;
	this.timerInterval;
	this.countdownContainer = jQuery('.ycd-simple-container');
}

YcdSimpleCountdown.run = function()
{
	var simpleCountdown = jQuery('.ycd-simple-container');

	if (!simpleCountdown.length) {
		return false;
	}

	simpleCountdown.each(function () {
	   var options = jQuery(this).data('options');
	   var id = jQuery(this).data('id');
		var obj = new YcdSimpleCountdown();

		options['id'] = id;
		obj.options = options;
		obj.id = id;
		obj.init();
		YcgGeneral.bind(obj);
	});
};

YcdSimpleCountdown.prototype = new YcgGeneral();

YcdSimpleCountdown.prototype.init = function()
{
	this.doubeleDigits = this.options['ycd-enable-simple-double-digits'];

	this.render();
	this.livePreview();
};

YcdSimpleCountdown.prototype.responsive = function() {
	var scale = function () {
		jQuery('.ycd-simple-content-wrapper').each(function () {
			var wrapperWidth = jQuery('.ycd-simple-mode-textUnderCountdown', this).get(0).scrollWidth;
			var scaleDegree =  jQuery(this).width()/wrapperWidth;

			if(wrapperWidth > jQuery(this).width()) {
				jQuery('.ycd-simple-container', this).css({
					'transform': 'scale('+ scaleDegree +', '+scaleDegree+')'
				});
			}
			else {
				jQuery('.ycd-simple-container', this).css({
					'transform': 'scale('+ 1 +', '+1+')'
				});
			}
		});
	};

	scale();
	jQuery(window).resize(function () {
		scale();
	})
};

YcdSimpleCountdown.prototype.changeDate = function() {
	var datePicker = jQuery('#ycd-date-time-picker, #ycd-coming-soon-start');
	if(!datePicker.length) {
		return false;
	}

	datePicker.change(function () {
		jQuery(window).trigger('ycdChangeDate');
	})
};

YcdSimpleCountdown.prototype.changeTimeZone = function() {
	var timeZone = jQuery('.js-circle-time-zone');

	if(!timeZone.length) {
		return false;
	}

	timeZone.bind('change', function() {
		jQuery(window).trigger('ycdChangeDate');
	});
};

YcdSimpleCountdown.prototype.changeDateDuration = function() {
	var types = jQuery('.ycd-timer-time-settings');

	if(!types.length) {
		return false;
	}
	var that = this;
	var countdown = this.countdownContainer;
	types.unbind('change').bind('change', function() {
		var val = jQuery(this).val();

		if (val == '') {
			val = 0;
			jQuery(this).val(val);
		}
		var timeName = jQuery(this).attr('name');
		var options = countdown.data('options');
		options[timeName] = parseInt(val);

		that.reInitSecondsByOptions(options);
	});
};

YcdSimpleCountdown.prototype.changeFontFamily = function() {
	var types = jQuery('.js-simple-font-family');

	if(!types.length) {
		return false;
	}
	types.bind('change', function() {
		var val = jQuery(this).val();
		var type = jQuery(this).data('field-type');

		jQuery('.ycd-simple-countdown-'+type).css({'font-family': val});
	});
};

YcdSimpleCountdown.prototype.changeFontSizes = function() {
	var types = jQuery('.ycd-simple-font-size');

	if(!types.length) {
		return false;
	}
	var that = this;
	var countdown = this.countdownContainer;
	types.bind('change', function() {
		var val = jQuery(this).val();
		var type = jQuery(this).data('field-type');

		jQuery('.ycd-simple-countdown-'+type).css({'font-size': val});
	});
};

YcdSimpleCountdown.prototype.changeColor = function() {
	var types = jQuery('.js-ycd-simple-time-color');

	if(!types.length) {
		return false;
	}
	var that = this;
	var countdown = this.countdownContainer;
	types.minicolors({
		format: 'rgb',
		opacity: 1,
		change: function () {
			var val = jQuery(this).val();
			var type = jQuery(this).data('time-type');
			jQuery('.ycd-simple-countdown-'+type).css({color: val});
		}
	});
};

YcdSimpleCountdown.prototype.eventListener = function ()
{
	var that = this;

	jQuery(window).bind('ycdChangeDate', function () {
		var val = jQuery('#ycd-date-time-picker').val()+':00';
		var selectedTimezone = jQuery('.js-circle-time-zone option:selected').val();
		var seconds = that.setCounterTime(val, selectedTimezone);
		that.seconds = seconds*1000;
		that.countdown();
	});
};

YcdSimpleCountdown.prototype.changeDateType = function() {
	var types = jQuery('.ycd-date-type');

	if(!types.length) {
		return false;
	}
	var that = this;
	var countdowns = this.countdownContainer;
	types.bind('change', function() {
		var val = jQuery(this).val();
		var timeName = jQuery(this).attr('name');
		var options = countdowns.data('options');
		options[timeName] = val;

		that.reInitSecondsByOptions(options);
	});
};

YcdSimpleCountdown.prototype.reInitSecondsByOptions = function (options)
{
	var seconds = this.getSeconds(options);

	this.seconds = seconds*1000;
	this.countdown();
};


YcdSimpleCountdown.prototype.livePreview = function()
{
	var adminElement = jQuery('.ycd-simple-text');
	if (!adminElement.length) {
		return false;
	}

	this.eventListener();
	this.changeText();
	this.changeSwitch();
	this.changeDateType();
	this.changeDate();
	this.changeTimeZone();
	this.changeDateDuration();
	this.changeFontSizes();
	this.changeFontFamily();
	this.changeColor();
	// this.changeBorderColor();
	this.changeDoubeleDigits();
	this.changeDotes();
	this.changeAlign();
	this.changeSwithchBorder();
};

YcdSimpleCountdown.prototype.changeText = function()
{
	var texts = jQuery('.ycd-simple-text');

	texts.bind('input', function () {
	   var unite = jQuery(this).data('time-type');
	   jQuery('.ycd-simple-countdown-'+unite+'-label').html(jQuery(this).val());
	});
};

YcdSimpleCountdown.prototype.changeDoubeleDigits = function()
{
	var texts = jQuery('#enable-double-digits');
	var that = this;

	texts.bind('change', function () {
	   var status = jQuery(this).is(':checked');
	   that.doubeleDigits = status;
	});
};

YcdSimpleCountdown.prototype.changeAlign = function () {
	var alignSelect = jQuery('.js-simple-timer-align');

	if (!alignSelect.length) {
		return ;
	}

	alignSelect.bind('change', function() {
		var selectedValue = jQuery(this).val();
		jQuery('.ycd-countdown-wrapper ').css({textAlign: selectedValue});
	})
}

YcdSimpleCountdown.prototype.changeDotes = function () {
	var dotesSelect = jQuery('.js-simple-timer-dotes');

	if (!dotesSelect.length) {
		return ;
	}

	dotesSelect.bind('change', function() {
		var selectedValue = jQuery(this).val();
		if (selectedValue == '') {
			jQuery('.ycd-simple-timer-dots').hide()
		}
		else {
			jQuery('.ycd-simple-timer-dots').show()
		}
		jQuery('.ycd-simple-timer-dots').text(selectedValue);
	})
}

YcdSimpleCountdown.prototype.changeSwitch = function()
{
	var status = jQuery('.js-ycd-time-status');

	if (!status.length) {
		return false;
	}

	status.bind('change', function () {
	   var currentStatus = jQuery(this).is(':checked');
	   var type = jQuery(this).data('time-type');
	   var wrapper = jQuery('.ycd-simple-current-unite-'+type);
	   if (currentStatus) {
		   jQuery(wrapper).prev().find('.ycd-simple-timer-dots').removeClass('ycd-hide');
			wrapper.removeClass('ycd-hide');
	   }
	   else {
	   	    if (jQuery(wrapper).nextAll().not(".ycd-hide").length == 0) {
		        jQuery(wrapper).prev().find('.ycd-simple-timer-dots').addClass('ycd-hide');
	        }
			wrapper.addClass('ycd-hide');
	   }
	});
};

YcdSimpleCountdown.prototype.render = function()
{
	this.addTimeToClock();
	this.listeners();
	this.countdown();
	this.responsive();
};

YcdSimpleCountdown.prototype.listeners = function () {
	var that = this;
	jQuery(window).bind("tabInactive", function () {
		that.isActive = false;
	})
	jQuery(window).bind("tabActive", function () {
		that.isActive = true;
	})
}

YcdSimpleCountdown.prototype.countdown = function()
{
	var unites = ['years', 'months', 'days', 'hours', 'minutes', 'seconds'];
	var that = this;
	var id = that.id;
	var options = this.options;

	var countdownWrapper = jQuery('.ycd-simple-wrapper-'+this.id);
	var runCountdown = function() {
		if (!that.isActive && options['ycd-countdown-stop-inactive']) {
			return false;
		}

		// Get today's date and time
		var now = new Date().getTime();

		// Find the distance between now and the count down date
		var distance = that.seconds;

		// If the count down is finished, write some text
		distance = that.getFilteredDistance();

		if (distance <= 0  && that.countdownRun) {
			clearInterval(x);
			if(YcdArgs.isAdmin || options['ycd-countdown-expire-behavior'] == 'countToUp') {
				return false;
			}
			that.endBehavior(countdownWrapper, that.options);
			return;
		}
		var curDate = new Date();
		distance = (new Date()).getTime() + (distance) - curDate.getTime();

		// Time calculations for days, hours, minutes, and seconds
		var unitesValues = {};
		unitesValues.years = Math.floor(distance / (1000 * 31557600));
		if (options['ycd-simple-enable-years']) {
			distance = distance % (1000 * 31557600);
		}

		if (options['ycd-simple-enable-months']) {
			unitesValues.months = Math.floor(distance / (2629800 * 1000));
		}

		if (options['ycd-simple-enable-months']) {
			unitesValues.days = Math.floor(distance  % (2629800 * 1000) / (1000 * 86400) );
		}
		else {
			unitesValues.days = Math.floor(distance / (1000 * 86400) );
		}

		unitesValues.hours = Math.floor((distance % (1000 * 86400)) / (1000 * 3600)); // Corrected hours calculation
		unitesValues.minutes = Math.floor((distance % (1000 * 3600)) / (1000 * 60));
		unitesValues.seconds = Math.floor((distance % (1000 * 60)) / 1000);

		for (var i in unites) {
			var unite = unites[i];
			var selector = '.ycd-simple-mode-textUnderCountdown-' + id + ' .ycd-simple-countdown-' + unite + '-time';
			var currentUniteValue = unitesValues[unite];

			if (currentUniteValue < 10 && that.doubleDigits) {
				currentUniteValue = "0" + currentUniteValue;
			}

			jQuery(selector).text(currentUniteValue);
		}

		if (options['ycd-countdown-expire-behavior'] == 'countToUp' && that.seconds <= 0) {
			that.countdownRun = false;
		}

		if (!that.countdownRun) {
			that.seconds += 1000;
		} else {
			that.seconds -= 1000;
		}
	};

	clearInterval(this.timerInterval);
	var x = setInterval(function() {
		runCountdown();
	}, 1000);
	this.timerInterval = x;
};

YcdSimpleCountdown.prototype.getFilteredDistance = function() {
	if (this.seconds > 0) {
		return this.seconds;
	}
	var options = this.options

	if (options['ycd-countdown-expire-behavior'] == 'countToUp') {
		this.countdownRun = false;

		if (options['ycd-count-up-from-end-date']) {
			var date = new Date(
				moment(options['ycd-date-time-picker'])
					.tz(options['ycd-time-zone'])
					.format('MM/DD/YYYY H:m:s'))
					.getTime();
			var now = new Date().getTime();
			this.seconds = now - date;
			return this.seconds;
		}
		else {
			return 0;
		}
	}

	return 0;
};

YcdSimpleCountdown.prototype.addBorderStyles = function() {
	if (jQuery('#ycd-simple-enable-unite-border').is(':checked')) {
		jQuery(".ycd-simple-current-unite-wrapper").css({
			width: jQuery('#ycd-simple-unite-width').val(),
			borderWidth: jQuery('#ycd-simple-unite-border-width').val(),
			borderRadius: jQuery('#ycd-simple-unite-border-radius').val(),
			borderStyle: jQuery('.ycd-simple-unite-border-type').val(),
			borderColor: jQuery("#ycd-simple-unite-border-color").val()
		})
	}
	else {
		jQuery(".ycd-simple-current-unite-wrapper").css({
			border: "none"
		});
	}
	var uniteMurginTop = jQuery("#ycd-simple-unite-margin-top").val();
	var uniteMurginRight = jQuery("#ycd-simple-unite-margin-right").val();
	var uniteMurginBottom = jQuery("#ycd-simple-unite-margin-bottom").val();
	var uniteMurginLeft = jQuery("#ycd-simple-unite-margin-left").val();
	
	var unitePaddingTop = jQuery("#ycd-simple-unite-padding-top").val();
	var unitePaddingRight = jQuery("#ycd-simple-unite-padding-right").val();
	var unitePaddingBottom = jQuery("#ycd-simple-unite-padding-bottom").val();
	var unitePaddingLeft = jQuery("#ycd-simple-unite-padding-left").val();

	jQuery(".ycd-simple-current-unite-wrapper").css({
		paddingTop: unitePaddingTop,
		paddingRight: unitePaddingRight,
		paddingBottom: unitePaddingBottom,
		paddingLeft: unitePaddingLeft,

		marginTop: uniteMurginTop,
		marginRight: uniteMurginRight,
		marginBottom: uniteMurginBottom,
		marginLeft: uniteMurginLeft,
	})

	var numbersMurginTop = jQuery("#ycd-simple-numbers-margin-top").val();
	var numbersMurginRight = jQuery("#ycd-simple-numbers-margin-right").val();
	var numbersMurginBottom = jQuery("#ycd-simple-numbers-margin-bottom").val();
	var numbersMurginLeft = jQuery("#ycd-simple-numbers-margin-left").val();
	jQuery(".ycd-simple-countdown-number").css({
		marginTop: numbersMurginTop,
		marginRight: numbersMurginRight,
		marginBottom: numbersMurginBottom,
		marginLeft: numbersMurginLeft,
	})
	var textMurginTop = jQuery("#ycd-simple-text-margin-top").val();
	var textMurginRight = jQuery("#ycd-simple-text-margin-right").val();
	var textMurginBottom = jQuery("#ycd-simple-text-margin-bottom").val();
	var textMurginLeft = jQuery("#ycd-simple-text-margin-left").val();

	jQuery(".ycd-simple-countdown-label").css({
		marginTop: textMurginTop,
		marginRight: textMurginRight,
		marginBottom: textMurginBottom,
		marginLeft: textMurginLeft,
	})
}

YcdSimpleCountdown.prototype.changeSwithchBorder = function() {
	var that = this;
	jQuery('#ycd-simple-enable-unite-border, #ycd-simple-unite-width, #ycd-simple-unite-border-width, #ycd-simple-unite-border-radius,.ycd-simple-unite-border-type').change(function() {
		that.addBorderStyles();
	})
	jQuery('#ycd-simple-unite-border-color').minicolors({
		format: 'rgb',
		opacity: 1,
		change: function () {
			that.addBorderStyles();
		}
	});
	jQuery('.ycd-numbers-margin').bind('change', function() {
		that.addBorderStyles();
	})
	jQuery('.ycd-text-margin').bind('change', function() {
		that.addBorderStyles();
	})
	jQuery('.ycd-unite-margin').bind('change', function() {
		that.addBorderStyles();
	})
	jQuery('.ycd-unite-padding').bind('change', function() {
		that.addBorderStyles();
	})
}

YcdSimpleCountdown.prototype.addTimeToClock = function()
{
	var options = this.options;
	var seconds = this.getSeconds(options);
	this.seconds = seconds*1000;
	this.id = options['id'];
	this.options['allSeconds'] = seconds;
	this.savedOptions = this.options;
};

jQuery(document).ready(function() {
	YcdSimpleCountdown.run();
});