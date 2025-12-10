function YcdMomenyCounter() {
    this.initialValue = 0;
    this.increasePerSec = 0;
    this.decimals = 0;
    this.targetValue = 0;
    this.startDateStr = '';
    this.prefix = '';
    this.counterEl = '';
    this.timer = '';

    this.init();
    this.preview();
}

YcdMomenyCounter.prototype = new YcgGeneral();

YcdMomenyCounter.prototype.init = function() {
    const counterEl = document.getElementById('ycd-money-counter');
    if (!counterEl) return;
    console.log(this.initialValue);
    var initialValue = parseFloat(counterEl.getAttribute('data-initial')) || 0;
    var increasePerSec = parseFloat(counterEl.getAttribute('data-increase')) || 0;
    var startDateStr = counterEl.getAttribute('data-start-date');
  
    var decimals = parseInt(counterEl.getAttribute('data-decimals')) || 0;
    var prefix = counterEl.getAttribute('data-prefix') || '';
    var targetValue = parseFloat(counterEl.getAttribute('data-target')) || null;

    this.initialValue = initialValue;
    this.increasePerSec = increasePerSec;
    this.startDateStr = startDateStr;
    this.decimals = decimals;
    this.prefix = prefix;
    this.targetValue = targetValue;
    this.counterEl = counterEl;
    this.options = JSON.parse(counterEl.getAttribute('data-options'));

    const fontSize = counterEl.style.fontSize;
    counterEl.style.fontSize = fontSize;
    
    this.run()
}

YcdMomenyCounter.prototype.preview = function() {
    var that = this;

    var preRender = function(e) {
        if (that.timer) {
            clearInterval(that.timer);
            that.timer = null;
        }
       that.run();
    }

    jQuery('#ycd-money-prefix').on('change', function(e) {
        that.prefix = e.target.value;
       preRender(e)
    })
    jQuery('#ycd-money-decimal-places').on('change', function(e) {
        that.decimals = e.target.value;
       preRender(e)
    })
    jQuery('#ycd-money-increase-unite').on('change', function(e) {
        that.increasePerSec = parseFloat(e.target.value);
       preRender(e)
    })
    jQuery('#ycd-money-initial').on('change', function(e) {
        that.initialValue = parseFloat(e.target.value);
       preRender(e)
    })
    jQuery('#ycd-money-start-date').on('change', function(e) {
        that.startDateStr = e.target.value;
       preRender(e)
    })
    jQuery('#ycd-money-target-value').on('change', function(e) {
        that.targetValue = e.target.value;
       preRender(e)
    })
    jQuery('#ycd-money-font-size').on('change', function(e) {
        that.counterEl.style.fontSize = e.target.value;
    })

    jQuery('#ycd-money-color').minicolors({
		format: 'rgb',
		opacity: 1
	}).on("change", function(e) {
        jQuery("#ycd-money-counter").css({color: e.target.value});
    })
    jQuery('#ycd-money-bg-color').minicolors({
		format: 'rgb',
		opacity: 1
	}).on("change", function(e) {
        jQuery("#ycd-money-counter").css({background: e.target.value});
    })
}

YcdMomenyCounter.prototype.run = function() {
    var initialValue = this.initialValue;
    var increasePerSec = this.increasePerSec;
    var startDateStr = this.startDateStr;
    var decimals = this.decimals;
    var prefix = this.prefix;
    var targetValue = this.targetValue;
    var counterEl = this.counterEl;

    let startDate = new Date(startDateStr);
    let now = new Date();

    let elapsedSeconds = Math.max(0, Math.floor((now - startDate) / 1000));
    let currentValue = initialValue + (increasePerSec * elapsedSeconds);

    function formatNumber(value) {
        return value.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function updateCounter() {
        currentValue += increasePerSec;
        if (targetValue && currentValue >= targetValue) {
            currentValue = targetValue;
            clearInterval(timer);
        }
        counterEl.innerHTML = prefix + formatNumber(currentValue);
    }

    if (targetValue && currentValue >= targetValue) {
        this.endBehavior(counterEl, this.options)
        currentValue = targetValue;
    }

    counterEl.innerHTML = prefix + formatNumber(currentValue);
    const timer = setInterval(updateCounter, 1000);
    this.timer = timer;
}

document.addEventListener("DOMContentLoaded", function () {
    new YcdMomenyCounter();
});