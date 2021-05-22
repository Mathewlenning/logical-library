logical = window.logical || {};
logical =
{
    messagetype: 'success',

    notify: function (responseText) {

        if(typeof UIkit === "function")
        {
            UIkit.notification({
                message: responseText,
                status: logical.messagetype,
                pos: 'top-center',
                timeout: 3000
            });

            return;
        }

        logical.clearMessage();
        jQuery('#content').prepend(responseText);
    },

    clearMessage: function () {
        jQuery('#system-message-container').remove();
    },

    isAdmin: function () {
        return jQuery('body').hasClass('admin')
    },

    hasValue: function (name) {
        var input = document.getElementsByName(name);

        if(input[0] === undefined)
        {
            return false;
        }

        var hasValue = false;
        switch (input[0].type) {
            case 'radio':
            case 'checkbox':

                var i = 0;
                while (i < input.length) {
                    if (input[i].checked) {
                        hasValue = true;
                        break;
                    }
                    i++;
                }
                break;

            default:

                if (input[0].value.trim() != '') {
                    hasValue = true;
                }
        }

        return hasValue;
    },

    empty: function (value)
    {
        if (typeof value == 'undefined'
            || value == ''
            || parseInt(value) == 0
            || value == false
            || value == null) {
            return true;
        }

        return false;
    },

    hasAttr: function (object, attrName) {
        var attr = object.attr(attrName);
        return (typeof attr !== typeof undefined && attr !== false);
    },

    foreach:function(values, callback)
    {
	    for (var i = 0; i < values.length; i++)
	    {
            if(typeof callback == 'function')
            {
                callback(i, values[i]);

                continue;
            }

            if(typeof window[callback] == 'function')
            {
                window[callback](i, values[i]);
            }

	    }
    },

    copyToClipboard:function(text)
    {
	    var textArea = document.createElement("textarea");
	    textArea.style.position = 'fixed';
	    textArea.style.top = 0;
	    textArea.style.left = 0;
	    textArea.style.width = '2em';
	    textArea.style.height = '2em';
	    textArea.style.padding = 0;
	    textArea.style.border = 'none';
	    textArea.style.outline = 'none';
	    textArea.style.boxShadow = 'none';
	    textArea.style.background = 'transparent';
	    textArea.value = text;
	    document.body.appendChild(textArea);
	    textArea.focus();
	    textArea.select();

	    var result = false;

	    try
	    {
	        result = document.execCommand('copy');
	    }
	    catch (err)
	    {
	        result = false;
	    }

	    document.body.removeChild(textArea);

	    return result;
    }
};

logical.ajax =
{
    submitButton: function(form, task, callback)
    {
        var settings = {
            url: form.attr('action'),
            type:'POST',
            dataType: 'json',
            data: this.serialize(form, task),
            beforeSend: function()
            {
                var systemMessage = jQuery('#system-message-container .alert');
                systemMessage.attr('class', 'alert muted');
                form.closest('.js-wrapper').addClass('js-ajax_loading');
            }
        };

        if(typeof callback == 'function')
        {
            return callback(jQuery.ajax(settings), form);
        }

        if(typeof window[callback] == 'function')
        {
            return window[callback](jQuery.ajax(settings), form);
        }

        return [jQuery.ajax(settings), form];
    },

    serialize:function(form, task)
    {
        if(typeof tinyMCE === 'object')
        {
            tinyMCE.triggerSave();
        }

        return logical.form.getData(form, 'ajax.' + task);
    },

	getSettings: function (form, task) {
		return {
			url: form.attr('action'),
			type: 'POST',
			dataType: 'json',
			data: logical.ajax.serialize(form, task)
		};
	}
};

logical.form =
{
    submitButton: function(event)
    {
        event.preventDefault();
        var targ = logical.form.getTarget(event);

        var confirmMessage = targ.attr('data-confirm');

        if(typeof confirmMessage != 'undefined'
            && confirmMessage.trim() != ''
            && confirm(confirmMessage) != true)
        {
            return false;
        }

        var task = targ.attr('data-task');
        var dataAjaxCallback = targ.attr('data-ajax');
        var form = jQuery('#' + targ.attr('form'));

        if(form.prop('tagName') !== 'FORM')
        {
            form = jQuery(targ.closest('form'));
        }

        if(task !== 'cancel')
        {
            if (logical.form.validateForm(form) === false) {
                return false;
            }
        }

        if (typeof window[dataAjaxCallback] == 'function')
        {
            dataAjaxCallback = window[dataAjaxCallback];
        }

        if (typeof dataAjaxCallback == 'function')
        {
            return logical.ajax.submitButton(form, task, dataAjaxCallback)
        }

        this.setTask(form, task);

        form.submit();
    },

    validateForm:function (form)
    {
        if (logical.hasAttr(form, 'data-validation') === false)
        {
            return true;
        }

        var validateCallback = form.attr('data-validation_script');
        var requiredFields = form.find('.required');

        if (typeof window[validateCallback] == 'function')
        {
            validateCallback = window[validateCallback];
        }

        if (typeof validateCallback == 'function')
        {
            return validateCallback(form, requiredFields);
        }

        var invalid = false;
        var invalidFields = [];

        logical.foreach(requiredFields, function (index, element)
        {
            if(logical.form.validElement(element) === false)
            {
                invalid = true;
                invalidFields.push(jQuery(element));
            }
        });

        if (invalid === true)
        {
            logical.messagetype = 'danger';
            logical.notify('Required Fields missing');
            logical.messagetype = 'success';

           invalidFields[0].focus();
           return false;

        }

        return true;
    },

    validElement: function(element)
    {
        element = jQuery(element);

        var hasValue = false;
        switch (element.prop('tagName').toLowerCase())
        {
            case 'radio':
            case 'checkbox':
                hasValue = logical.hasValue(element.attr('name'))
                break;

            case 'fieldset':
                var input = element.find('input');
                hasValue = logical.hasValue(jQuery(input[0]).attr('name'));
                break;
            case 'label':
                hasValue = true;
                break;
            default:

                if (logical.empty(element.val()) === false)
                {
                    hasValue = true;
                }
        }


        if(hasValue === true)
        {
            return true;
        }

        element.addClass('invalid');
        element.change(function (event)
        {
            var targ = logical.form.getTarget(event);

            if (logical.hasValue(targ.val()) === false)
            {
                targ.removeClass('invalid');
            }
        });

        return false;
    },

    getTarget: function(event)
    {
        var targ = event.target || event.srcElement;

        var tagName = targ.tagName.toUpperCase();

        target = targ;

        if(tagName !== 'INPUT'
            && tagName !== 'SELECT'
            && tagName !== 'OPTION'
            && tagName !== 'TEXTAREA')
        {
           target = logical.form.getTargetFromElement(targ);
        }

        return jQuery(target);
    },

    getTargetFromElement: function(element)
    {
        var tagName = element.tagName.toUpperCase();

        if (logical.hasAttr(jQuery(element), 'data-task') === false && tagName !== 'BUTTON' && tagName !=='A')
        {
            return logical.form.getTargetFromElement(element.parentElement)
        }

        return element;
    },

    getData: function(form, task)
    {
        var taskInput = this.getTaskInput(form);
        var orignialValue = taskInput.val();

        taskInput.val(task);

        var formData = form.serialize();

        taskInput.val(orignialValue);

        return formData;
    },

    setTask: function(form, task)
    {
        var taskInput =  this.getTaskInput(form);
        taskInput.val(task);

        return true;
    },

    getTaskInput: function(form)
    {
        return  this.getInput('task', form);
    },

    getInput: function (name, form)
    {
        var input = form.find('input[name ="' + name + '"]');

        if(input.prop('tagName') === 'undefined')
        {
            form.append('<input type="hidden" name="'+ name +'"/>');
            input = form.find('input[name ="' + name + '"]');
        }

        return input;
    },

    checkAll: function(event)
    {
        var targ = logical.form.getTarget(event);

        var cids = targ.parents('form').find('input[name="cid[]"]');

        cids.each(function()
        {
            jQuery(this).prop('checked', targ.prop('checked'));
        });
    },

    fileInputIndicator: function(event)
    {
        var target = logical.form.getTarget(event);

        var defaultIndicator = target.parent().find('[data-id = "file_indicator"] div[data-function="default"]');
        var valueIndicator =  target.parent().find('[data-id = "file_indicator"] div[data-function="value"]');

        if (target.val() == '')
        {
            defaultIndicator.removeClass('hide');
            valueIndicator.addClass('hide');
            return;
        }

        defaultIndicator.addClass('hide');

        var displayValue = target.val().split('\\').reverse();
        var ext = displayValue[0].split('.').reverse();

        valueIndicator.html(displayValue[0].substr(0, 5) + '..<strong>.' + ext[0] + '</strong>');
        valueIndicator.removeClass('hide');
    },

    assignForm: function(containerId,formId)
    {
        var container = jQuery('#'+containerId);

        var inputs = container.find('input');
        var selects = container.find('select');
        var textarea = container.find('textarea');
        var button = container.find('button');

        inputs.attr('form', formId);
        selects.attr('form', formId);
        textarea.attr('form', formId);
        button.attr('form', formId);
    },

    initPhone: function(selector)
    {
        var input = jQuery(selector);

        input.on('keydown', function(event)
        {
           logical.form.checkPhoneInput(event);
        });

        input.on('keyup', function (event) {
            logical.form.formatPhoneNumber(event);
        });

        jQuery(selector).trigger('keyup');
    },

    checkPhoneInput: function(event)
    {
        var val = logical.form.getPhoneValue(event);
        var backspaceKeyCode = 8;
        var tabKeyCode = 9;

        if (val.length >= 10 && event.keyCode !== backspaceKeyCode && event.keyCode != tabKeyCode)
        {
            event.preventDefault();
            return false;
        }
    },

    getPhoneValue: function (event)
    {
        var targ = logical.form.getTarget(event);
        var val = targ.val().trim();

        return val.replace(/\D/g,'').trim();
    },

    formatPhoneNumber:function(event)
    {
        var targ = logical.form.getTarget(event);
        var val = logical.form.getPhoneValue(event);

        if(logical.empty(val) === true)
        {
            targ.val(val);

            return;
        }

        if (val.length >= 11)
        {
            val = val.substr(0, 10);
        }

        areaCode = val.substr(0,3);
        prefix = val.substr(3,3);
        lineNumber = val.substr(6,4);

        val = '(' + areaCode;

        if(logical.empty(prefix) === false || prefix == 0)
        {
            val = val +  ') ' + prefix;
        }

        if(logical.empty(lineNumber) === false || lineNumber == 0)
        {
            val = val +  '-' + lineNumber;
        }

        targ.val(val);
    }
};

logical.math =
{
    decimalAdjust:function(type, value, exp)
    {
        // If the exp is undefined or zero...
        if (typeof exp === 'undefined' || +exp === 0) {
            return Math[type](value);
        }

        value = +value;
        exp = +exp;

        // If the value is not a number or the exp is not an integer...
        if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
            return NaN;
        }

        // Shift
        value = value.toString().split('e');
        value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
        // Shift back
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
    },

    toCurrency:function (event)
    {
        var targ = logical.form.getTarget(event);
        targ.val(parseFloat(targ.val()).toFixed(2));
    }
};


