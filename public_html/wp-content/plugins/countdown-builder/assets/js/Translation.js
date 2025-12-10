function YcdTranslation() {
	this.init();
}

YcdTranslation.prototype.init = function () {
	this.addTranslation();
	this.action();
};

YcdTranslation.prototype.action = function () {
	this.toggle();
	this.select2();
	this.changeLanguage();
	this.deleteItem();
};

YcdTranslation.prototype.select2 = function () {
	var select2 = jQuery(".ycd-container-wrapper .ycd-select2");

	if(!select2.length) {
		return false;
	}

	select2.select2({
		width: "200px"
	});
}

YcdTranslation.prototype.changeLanguage = function () {
	jQuery('.ycd-tr-language').bind('change', function () {
		var id = jQuery(this).data('id');
		var text = jQuery("option:selected", this).text();

		jQuery('.ycd-translation-wrapper-'+id+' .tab-language-tab').text(text);
	})
}

YcdTranslation.prototype.deleteItem = function () {
	jQuery('.delete-item').bind('click', function (e) {
		e.preventDefault();
		if (confirm("Are you sure?")) {
			var id = jQuery(this).data('id');
			jQuery('.ycd-translation-wrapper-'+id).remove();
			return ;
		}
	})
}

YcdTranslation.prototype.toggle = function () {
	jQuery('.ycd-translation-header').unbind('click').bind('click', function (e) {
		e.preventDefault();
		var hiddenClass = 'ycd-hide';

		var body = jQuery(this).next('.ycd-translation-body');
		if (body.hasClass(hiddenClass)) {
			body.removeClass(hiddenClass)
		}
		else {
			body.addClass(hiddenClass);
		}
	})
}

YcdTranslation.getMaxIndex = function () {
	var idsList = [];
	jQuery('.ycd-container-wrapper .ycd-translation-wrapper').each(function(current) {
		let id = jQuery(this).data('id');
		console.log(id);
		idsList.push(id);
	});
	let max = Math.max.apply(null, idsList);
	if (max === -Infinity) {
		max = 1;
	} else {
		max += 1;
	}

	return max;
};

YcdTranslation.prototype.addTranslation = function () {
	var button = jQuery('.ycd-add-translation');

	if (!button.length) {
		return ;
	}
	var that = this;

	button.bind('click', function (e) {
		e.preventDefault();
		var template = jQuery('#template-wrapper').html();
		var index = YcdTranslation.getMaxIndex();
		template = template.replaceAll('{translationId}', index);
		var langKey = jQuery('[name="ycd-tr[{translationId}][language]"] option:selected').text();
		template = template.replaceAll('{isoCode}', langKey);
		if (jQuery('.ycd-container-wrapper .ycd-translation-wrapper').length === 0) {
			jQuery('.ycd-container-wrapper p').remove();
		}
		jQuery('.ycd-container-wrapper').append(template);
		that.action();
	})

}

jQuery(document).ready(function () {
	new YcdTranslation();
})