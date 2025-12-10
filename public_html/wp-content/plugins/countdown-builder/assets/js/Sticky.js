function ycdAddEvent(element, eventName, fn) {
	if (element.addEventListener)
		element.addEventListener(eventName, fn, false);
	else if (element.attachEvent)
		element.attachEvent('on' + eventName, fn);
}

function YcdSticky() {

}

YcdSticky.prototype = new YcgGeneral();

YcdSticky.prototype.init = function() {
	var header = jQuery('.ycd-sticky-header');
	var settings = jQuery(header).data('settings');

	this.seconds = 0;
	this.isActive = true;
	this.listeners()
	this.header();
	this.stickyClock();

	if (settings['ycd-sticky-show-condition'] === 'stickyConditionScroll') {
		var currentScrollPercentage = function()
		{
			return ((document.documentElement.scrollTop + document.body.scrollTop) / (document.documentElement.scrollHeight - document.documentElement.clientHeight) * 100);
		}
		var scrollCallBack = function(){

			const scrollTop = parseInt(currentScrollPercentage());

			if (scrollTop >= parseInt(settings['ycd-sticky-scroll-percent'])) {
				jQuery(header).removeClass('ycd-hide-banner')
			}
			else {
				jQuery(header).addClass('ycd-hide-banner');
			}
		};
		scrollCallBack();
		jQuery(window).scroll(scrollCallBack);
	}
	else if (settings['ycd-sticky-show-condition'] === 'initial') {

		if (settings['ycd-sticky-scroll-delay'] != 0) {
			jQuery(".ycd-sticky-header").addClass('ycd-hide-banner');
			setTimeout(function () {
				jQuery(header).removeClass('ycd-hide-banner')
			}, parseInt(settings['ycd-sticky-scroll-delay'])*1000)
		}
	}
};

YcdSticky.prototype.listeners = function () {
	var that = this;
	jQuery(window).bind("tabInactive", function () {
		that.isActive = false;
	})
	jQuery(window).bind("tabActive", function () {
		that.isActive = true;
	})
}

YcdSticky.prototype.setCounterTime = function(calendarValue, selectedTimezone) {
	var currentDate = moment(new Date()).tz(selectedTimezone).format('MM/DD/YYYY H:m:s');
	//var currentDate = moment(new Date()).format('MM/DD/YYYY H:m:s');

	var dateTime = new Date(currentDate).valueOf();
	var timeNow = Math.floor(dateTime / 1000);
	var seconds = Math.floor(new Date(calendarValue).getTime() / 1000) - timeNow;
	if (seconds < 0) {
		seconds = 0;
	}

	return seconds;
};

YcdSticky.prototype.stickyClock = function() {
	var that = this;
	var header = jQuery('.ycd-sticky-header');
	var settings = jQuery(header).data('settings');

	var endDate = settings.endDate;
	endDate = endDate.replace(/-/g, '/');
	var currentDate = moment(new Date(endDate));
	var countDownDate = new Date(currentDate).getTime();
	var stickyClock = jQuery('.ycd-sticky-clock');
	this.seconds = that.getSeconds(settings)*1000;

	var runTimer = function () {
		var enableDays = settings['ycd-sticky-enable-days'];
		var enableHours = settings['ycd-sticky-enable-hours'];
		var enableMinutes = settings['ycd-sticky-enable-minutes'];
		var enableSeconds = settings['ycd-sticky-enable-seconds'];
	
		var now = moment().tz(settings.timeZone).format('MM/DD/YYYY HH:mm:ss');
		//var now = moment().format('MM/DD/YYYY HH:mm:ss');
		if (!that.isActive && settings['ycd-countdown-stop-inactive']) {
			return false;
		}
	
		that.seconds -= 1000;
		var distance = that.seconds;
	
		var days = Math.floor(distance / (1000 * 60 * 60 * 24));
		var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
		var seconds = Math.floor((distance % (1000 * 60)) / 1000);
		var isDouble = YCD_STICKY_ARGS.double;
	
		days = (days > 0) ? days : 0;
		hours = (hours > 0) ? hours : 0;
		minutes = (minutes > 0) ? minutes : 0;
		seconds = (seconds > 0) ? seconds : 0;
	
		if (isDouble) {
			if (days < 10) days = '0' + days;
			if (hours < 10) hours = '0' + hours;
			if (minutes < 10) minutes = '0' + minutes;
			if (seconds < 10) seconds = '0' + seconds;
		}
	
		// Build clockHtml dynamically based on enabled units
		var clockParts = [];
		if (enableDays) clockParts.push(days + YCD_STICKY_ARGS.days);
		if (enableHours) clockParts.push(hours + YCD_STICKY_ARGS.hours);
		if (enableMinutes) clockParts.push(minutes + YCD_STICKY_ARGS.minutes);
		if (enableSeconds) clockParts.push(seconds + YCD_STICKY_ARGS.seconds);
	
		var clockHtml = clockParts.join(' ');
		stickyClock.html(clockHtml);
	
		if (distance < 0) {
			clearInterval(x);
			that.endBehavior(stickyClock, settings);
		}
	};
	
	var x = setInterval(function() {
		runTimer();
	}, 1000);
	runTimer();
};

YcdSticky.prototype.endBehavior = function(countdown, options) {

	if (options['ycd-countdown-end-sound']) {
		var soundUrl = options['ycd-countdown-end-sound-url'];
		var song = new Audio (soundUrl);
		song.play();
	}

	var id = options.id;
	var behavior = options['ycd-countdown-expire-behavior'];
	var expireText = options['ycd-expire-text'];
	var expireUrl = options['ycd-expire-url'];
	var headerWrapper = jQuery('.ycd-sticky-header-'+id);
	var countdownWrapper = countdown.parents('.ycd-countdown-wrapper').first();

	jQuery(window).trigger('YcdExpired', {'id':  id});

	switch(behavior) {
		case 'hideCountdown':
			headerWrapper.hide();
			break;
		case 'showText':
			countdown.fadeOut('slow').replaceWith(expireText);
			break;
		case 'redirectToURL':
			countdown.fadeOut('slow');
			window.location.href = expireUrl;
			break;
	}
};

YcdSticky.prototype.initClose = function () {
	var closeButton = jQuery('.ycd-sticky-close-text');

	if (!closeButton.length) {
		return false;
	}
	var that = this;

	closeButton.bind('click', function () {
		var id = jQuery(this).parent().data('id');
		that.closeBannerById(id);
	});
};

YcdSticky.prototype.closeBannerById = function (id) {
	var currentHeader = jQuery('.ycd-sticky-header-'+id);

	if (currentHeader.length)  {
		currentHeader.hide();
		jQuery(window).trigger('ycdStickyCountdownClose', [id]);
	}
};

YcdSticky.prototype.buttonBehavior = function()
{
	var header = jQuery('.ycd-sticky-header');
	var settings = jQuery(header).data('settings');
	var that = this;

	jQuery('.ycd-sticky-button').bind('click', function (e) {
		e.preventDefault();
		var behavior = settings['ycd-sticky-expire-behavior'];
		if (behavior == 'redirectToURL') {
			var url = settings['ycd-sticky-url'];
			if (settings['ycd-sticky-url-new-tab']) {
				window.open(url);
			}
			else {
				window.location = url;
			}
		}
		else if (behavior == 'copy') {
			var textAreaId = 'ycd-copy-text-area-' + 1;
			var value = settings['ycd-sticky-button-copy'];
			var textArea = jQuery('<input>', {
				id: textAreaId,
				value: value,
				style: 'position: absolute; right: -10000px'
			});

			if (!jQuery('#' + textAreaId).length) {
				jQuery('body').append(textArea);
			}
			jQuery('#' + textAreaId).select();
			document.execCommand('copy');
			jQuery('#' + textAreaId).remove();
			if (settings['ycd-sticky-copy-alert']) {
				alert(settings['ycd-sticky-alert-text']);
			}
		}
		else if(behavior == 'closeBanner') {
			that.closeBannerById(settings['id']);
		}

	});
};

YcdSticky.prototype.header = function() {
	var header = jQuery('.ycd-sticky-header');

	if(!header.length) {
		return false;
	}

	this.buttonBehavior();
	this.initClose();

	var sticky = header.offset().top;
	if (!header.data('footer')) {
		ycdAddEvent(window, 'scroll', function(e) {
			if (window.pageYOffset > sticky) {
				jQuery(header).addClass('ycd-sticky');
			} else {
				jQuery(header).removeClass('ycd-sticky');
			}
		});
	}
};

jQuery(document).ready(function() {
	var obj = new YcdSticky();
	obj.init();
});