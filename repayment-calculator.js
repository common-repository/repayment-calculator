jQuery(document).ready(function($) {
    (function(factory) {
        if (typeof define === 'function' && define.amd) {
            // AMD. Register as an anonymous module.
            define(['jquery'], factory);
        } else {
            // Browser globals
            factory(jQuery);
        }
    } (function($) {
		
        /**
        * Range feature detection
        * @return {Boolean}
        */
        function supportsRange() {
            var input = document.createElement('input');
            input.setAttribute('type', 'range');
            return input.type !== 'text';
        }

        var pluginName = 'lrc',
            pluginInstances = [],
            inputrange = supportsRange(),
            defaults = {
                polyfill: true,
                rangeClass: 'lrc',
                disabledClass: 'lrc--disabled',
                fillClass: 'lrc__fill',
                handleClass: 'lrc__handle',
                startEvent: ['mousedown', 'touchstart', 'pointerdown'],
                moveEvent: ['mousemove', 'touchmove', 'pointermove'],
                endEvent: ['mouseup', 'touchend', 'pointerup']
            };

        /**
        * Delays a function for the given number of milliseconds, and then calls
        * it with the arguments supplied.
        * @param  {Function} fn   [description]
        * @param  {Number}   wait [description]
        * @return {Function}
        */
        function delay(fn, wait) {
            var args = Array.prototype.slice.call(arguments, 2);
            return setTimeout(function(){ return fn.apply(null, args); }, wait);
        }

        /**
        * Returns a debounced function that will make sure the given
        * function is not triggered too much.
        * @param  {Function} fn Function to debounce.
        * @param  {Number}   debounceDuration OPTIONAL. The amount of time in milliseconds for which we will debounce the function. (defaults to 100ms)
        * @return {Function}
        */
        function debounce(fn, debounceDuration) {
            debounceDuration = debounceDuration || 100;
            return function() {
                if (!fn.debouncing) {
                    var args = Array.prototype.slice.apply(arguments);
                    fn.lastReturnVal = fn.apply(window, args);
                    fn.debouncing = true;
                }
                clearTimeout(fn.debounceTimeout);
                fn.debounceTimeout = setTimeout(function(){
                    fn.debouncing = false;
                }, debounceDuration);
                return fn.lastReturnVal;
            };
        }

        /**
        * Plugin
        * @param {String} element
        * @param {Object} options
        */
        function Plugin(element, options) {
            this.$window    = $(window);
            this.$document  = $(document);
            this.$element   = $(element);
            this.options    = $.extend( {}, defaults, options );
            this._defaults  = defaults;
            this._name      = pluginName;
            this.startEvent = this.options.startEvent.join('.' + pluginName + ' ') + '.' + pluginName;
            this.moveEvent  = this.options.moveEvent.join('.' + pluginName + ' ') + '.' + pluginName;
            this.endEvent   = this.options.endEvent.join('.' + pluginName + ' ') + '.' + pluginName;
            this.polyfill   = this.options.polyfill;
            this.onInit     = this.options.onInit;
            this.onSlide    = this.options.onSlide;
            this.onSlideEnd = this.options.onSlideEnd;

            // Plugin should only be used as a polyfill
            if (this.polyfill) {
                // Input range support?
                if (inputrange) { return false; }
            }

            this.identifier = 'js-' + pluginName + '-' +(+new Date());
            this.min        = parseFloat(this.$element[0].getAttribute('min') || 0);
            this.max        = parseFloat(this.$element[0].getAttribute('max') || 100);
            this.value      = parseFloat(this.$element[0].value || this.min + (this.max-this.min)/2);
            this.step       = parseFloat(this.$element[0].getAttribute('step') || 1);
			this.noChange	= false;
            this.$fill      = $('<div class="' + this.options.fillClass + '" />');
            this.$handle    = $('<div class="' + this.options.handleClass + '" />');
            this.$range     = $('<div class="' + this.options.rangeClass + '" id="' + this.identifier + '" />').insertAfter(this.$element).prepend(this.$fill, this.$handle);
            this.lockvalue	= -1;

            // visually hide the input
            this.$element.css({
                'position': 'absolute',
                'width': '1px',
                'height': '1px',
                'overflow': 'hidden',
                'opacity': '0'
            });

            // Store context
            this.handleDown = $.proxy(this.handleDown, this);
            this.handleMove = $.proxy(this.handleMove, this);
            this.handleEnd  = $.proxy(this.handleEnd, this);
            this.init();

            // Attach Events
            var _this = this;
            this.$window.on('resize' + '.' + pluginName, debounce(function() {
                // Simulate resizeEnd event.
                delay(function() { _this.update(); }, 300);
            }, 20));

            this.$document.on(this.startEvent, '#' + this.identifier + ':not(.' + this.options.disabledClass + ')', this.handleDown);

            // Listen to programmatic value changes
            this.$element.on('change' + '.' + pluginName, function(e, data) {
                if (data && data.origin === pluginName) {
                    return;
                }
				
                var value = e.target.value,
                    pos = _this.getPositionFromValue(value);
                _this.setPosition(pos);
            });
        }

        Plugin.prototype.init = function() {
            if (this.onInit && typeof this.onInit === 'function') {
                this.onInit();
            }
            this.update();
        };
		
		Plugin.prototype.lock	= function(args) {
			this.lockvalue		= args[0];
		}
		Plugin.prototype.unlock	= function() {
			this.lockvalue		= -1;
		}
        Plugin.prototype.update = function() {

			var hideagain		= 0;
			if (!this.$element.is(':visible')) { hideagain = 1; }
			
			if (hideagain) this.$element.closest('.hidethis').show();
            this.handleWidth    = this.$handle[0].offsetWidth;
            this.rangeWidth     = this.$range[0].offsetWidth;
            this.maxHandleX     = this.rangeWidth - this.handleWidth;
            this.grabX          = this.handleWidth / 2;
            this.position       = this.getPositionFromValue(this.value);
			if (hideagain) this.$element.closest('.hidethis').hide();

            // Consider disabled state
            if (this.$element[0].disabled) {
                this.$range.addClass(this.options.disabledClass);
            } else {
                this.$range.removeClass(this.options.disabledClass);
            }

			this.setPosition(this.position);

        };

        Plugin.prototype.handleDown = function(e) {
			
            e.preventDefault();
            this.$document.on(this.moveEvent, this.handleMove);
            this.$document.on(this.endEvent, this.handleEnd);

            // If we click on the handle don't set the new position
            if ((' ' + e.target.className + ' ').replace(/[\n\t]/g, ' ').indexOf(this.options.handleClass) > -1) {
                return;
            }

            var posX = this.getRelativePosition(this.$range[0], e),
                handleX = this.getPositionFromNode(this.$handle[0]) - this.getPositionFromNode(this.$range[0]);

            this.setPosition(posX - this.grabX);

            if (posX >= handleX && posX < handleX + this.handleWidth) {
                this.grabX = posX - handleX;
            }
        };

        Plugin.prototype.handleMove = function(e) {
            e.preventDefault();
            var posX = this.getRelativePosition(this.$range[0], e);
			
            this.setPosition(posX - this.grabX);
        };

        Plugin.prototype.handleEnd = function(e) {
            e.preventDefault();
            this.$document.off(this.moveEvent, this.handleMove);
            this.$document.off(this.endEvent, this.handleEnd);

			var value, left, ppp;
			
			ppp = this.getPositionFromValue(this.min + this.step);
			value = this.getValueFromPosition(Math.round(this.position / ppp) * ppp);
            left = this.getPositionFromValue(value);
			
			// Update ui
			this.$fill[0].style.width = (left + this.grabX)  + 'px';
			this.$handle[0].style.left = left + 'px';
			
			this.position = left;
            this.value = value;
			
            if (this.onSlideEnd && typeof this.onSlideEnd === 'function') {
                this.onSlideEnd(this.position, this.value);
            }
        };

        Plugin.prototype.cap = function(pos, min, max) {
            if (pos < min) { return min; }
            if (pos > max) { return max; }
            return pos;
        };

        Plugin.prototype.setPosition = function(pos) {
			
			var value, left, ppp;
			
			var tobe = ((this.getValueFromPosition(this.cap(pos, 0, this.maxHandleX)) / this.step) * this.step);
			
			if (tobe >= this.lockvalue && this.lockvalue > -1) {
				value	= this.lockvalue;
				left	= this.getPositionFromValue(this.lockvalue);
			} else {
				// Moving steps
				ppp		= this.getPositionFromValue(this.min + this.step);
				value	= this.getValueFromPosition(Math.round(pos / ppp) * ppp);
				left	= this.cap(pos, 0, this.maxHandleX);
			}
			
            //Snapping steps
            // value = (this.getValueFromPosition(this.cap(pos, 0, this.maxHandleX)) / this.step) * this.step;
            // left = this.getPositionFromValue(value);

            // Update ui
            this.$fill[0].style.width = (left + this.grabX)  + 'px';
            this.$handle[0].style.left = left + 'px';
            this.setValue(value);

            // Update globals
            this.position = left;
            this.value = value;

            if (this.onSlide && typeof this.onSlide === 'function') {
                this.onSlide(left, value);
            }
			
        };

        Plugin.prototype.getPositionFromNode = function(node) {
            var i = 0;
            while (node !== null) {
                i += node.offsetLeft;
                node = node.offsetParent;
            }
            return i;
        };

        Plugin.prototype.getRelativePosition = function(node, e) {
            return (e.pageX || e.originalEvent.clientX || e.originalEvent.touches[0].clientX || e.currentPoint.x) - this.getPositionFromNode(node);
        };

        Plugin.prototype.getPositionFromValue = function(value) {
            var percentage, pos;
            percentage = (value - this.min)/(this.max - this.min);
            pos = percentage * this.maxHandleX;
            return pos;
        };

        Plugin.prototype.getValueFromPosition = function(pos) {
            var percentage, value;
            percentage = ((pos) / (this.maxHandleX || 1));
            value = this.step * Math.round((((percentage) * (this.max - this.min)) + this.min) / this.step);
            return Number((value).toFixed(2));
        };

        Plugin.prototype.setValue = function(value) {
            if (value !== this.value) {
                this.$element.val(value).trigger('change', {origin: pluginName});
            }
        };

        Plugin.prototype.destroy = function() {
            this.$document.off(this.startEvent, '#' + this.identifier, this.handleDown);
            this.$element
                .off('.' + pluginName)
                .removeAttr('style')
                .removeData('plugin_' + pluginName);

            // Remove the generated markup
            if (this.$range && this.$range.length) {
                this.$range[0].parentNode.removeChild(this.$range[0]);
            }

            // Remove global events if there isn't any instance anymore.
            pluginInstances.splice(pluginInstances.indexOf(this.$element[0]),1);
            if (!pluginInstances.length) {
                this.$window.off('.' + pluginName);
            }
        };

        // A really lightweight plugin wrapper around the constructor,
        // preventing against multiple instantiations
        $.fn[pluginName] = function(options) {
			
			
			var args = [].slice.call(arguments);
			

			return this.each(function() {
                var $this = $(this),
                    data  = $this.data('plugin_' + pluginName);

                // Create a new instance.
                if (!data) {
                    $this.data('plugin_' + pluginName, (data = new Plugin(this, options)));
                    pluginInstances.push(this);
                }

                // Make it possible to access methods from public.
                // e.g `$element.lrc('method');`
                if (typeof options === 'string') {
                    data[args.shift()](args);
                }
            });
        };
    }));
});


var lrc_loan_selector = 'form.lrc_form';
var lrc_slider_selector = 'div.range';

function lrcCalculate(e) {
	
    /* Change relevent element's output value */
	var $ 			= jQuery,
		form 		= $(this).closest(lrc_loan_selector),
		rates 		= lrc__rates[form.attr('id')],
		sliders 	= form.find(lrc_slider_selector),
		p 			= form.find(lrc_slider_selector).filter('.lrc-slider-principal'),
		t			= form.find(lrc_slider_selector).filter('.lrc-slider-term'),
		principal 	= parseFloat(p.find('input[type=range]').val()) || 0,
		term 		= parseFloat(t.find('input[type=range]').val()) || ((!rates.periodslider)? 1:0);
	
	if ($(this).hasClass("output")) return;

	/* Output Principal */
    
	p.find('output').text(rates.cb+principal.toString().lrc_rounding(rates)+rates.ca);

	/* Output term */
    
	periodlabel = term == 1 ? rates.singleperiodlabel : rates.periodlabel;
    
	t.find('output').text(term+' '+periodlabel);
	
	form.find('.lrc-outputs').show();

	/* Everything below this point should happen no matter WHICH slider is moved */
    
	var outputs = [];
    
    var currencyspace = rates.currencyspace ? ' ' : '';
    rates.cb = rates.ca = '';
    
	if (rates.currency == false) rates.currency = '';
    if (rates.ba == 'before') rates.cb = rates.currency+currencyspace;
    if (rates.ba == 'after') rates.ca = currencyspace+rates.currency;
    interest = principal > rates.trigger ? rates.secondary : rates.primary;

    var creditscore = $("input[name='creditselector']:checked").val();
    var rating = rates.usecreditselector ? rates.triggers[creditscore].name : 'N/A';
    var low = rates.usecreditselector ? rates.triggers[creditscore].low : interest;
    
    outputs.push(lrc_amortization(term, principal, rates, low));
	
	if (rates.adminfeewhen == 'afterinterest') {
		lrc_adminfee_after(rates,outputs,term);
	}
    
	/* Display the Outputs */
    form.find('.rate').text(low+'%');
	form.find('.interest').text(rates.cb+lrc_doubledigit(outputs[0].interest,rates).lrc_rounding(rates)+rates.ca);
	form.find('.grandtotal').text(rates.cb+lrc_doubledigit(outputs[0].total,rates).lrc_rounding(rates)+rates.ca);
	form.find('.repayment').text(rates.cb+lrc_doubledigit(outputs[0].repayment,rates).lrc_rounding(rates)+rates.ca);
    form.find('.rating').text(rating);
	form.find('.principal').text(rates.cb+lrc_doubledigit(principal,rates).lrc_rounding(rates)+rates.ca);
    form.find('.term').text(term+' '+periodlabel);
	form.find('.processing').text(rates.cb+lrc_doubledigit(outputs[0].processing,rates).lrc_rounding(rates)+rates.ca);
	
	/* Fill the data into the hidden fields */
	form.find('input[name=repayment]').val(lrc_doubledigit(outputs[0].repayment,rates))
	form.find('input[name=totalamount]').val(lrc_doubledigit(outputs[0].total,rates))
}

function lrc_doubledigit(num,rates) {
	
	if (rates.decimals == 'none') return Math.round(num).toString();
	var n = num.toFixed(2);
	if (rates.decimals == 'float') return n.replace('.00','');
	return n;
	
}

function lrc_adminfee(rates,P,T) {
	
    var termfee = 0, adminfee = 0;
	if (rates.adminfee && rates.adminfeewhen == 'beforeinterest') {
		adminfee = P * (rates.adminfeevalue * .01);
		if (rates.adminfeetype != 'percent') {
			adminfee = rates.adminfeevalue;
		}
	}
    if (rates.termfee && rates.adminfeewhen == 'beforeinterest') {
		termfee = T * (rates.termfeevalue);
		adminfee = adminfee + termfee;
	}

    if (adminfee && adminfee < rates.adminfeemin && rates.adminfeemin != false) adminfee = rates.adminfeemin;
    if (adminfee && adminfee > rates.adminfeemax && rates.adminfeemax != false) adminfee = rates.adminfeemax;
    
	return {'total':P+adminfee,'processing':adminfee};
}

function lrc_adminfee_after(rates,outputs,T) {

	var adminfee = 0, termfee = 0;
    
    for (i in outputs) {
		P = outputs[i].total;

		if (rates.adminfee && rates.adminfeewhen == 'afterinterest') {
			adminfee = P * (rates.adminfeevalue * .01);
			if (rates.adminfeetype != 'percent') {
				adminfee = rates.adminfeevalue;
			}
		}
		if (rates.termfee && rates.adminfeewhen == 'afterinterest') {
			termfee = T * (rates.termfeevalue);
			adminfee = adminfee + termfee;
		}
		
		if (adminfee < rates.adminfeemin && rates.adminfeemin != false) adminfee = rates.adminfeemin;
		if (adminfee > rates.adminfeemax && rates.adminfeemax != false) adminfee = rates.adminfeemax;

		outputs[i].total = P + adminfee;
		outputs[i].processing = adminfee;
	}
}

function lrc_amortization(term, principal, rates, low) {

	var preP= lrc_adminfee(rates,principal,term);
	var P	= preP.total;        // Principal
	var T	= term;              // Term
    var I   = low || 0;
    var C   = lrc_rterm (rates); // Adjust number of repayment periods
	var V 	= lrc_term (rates);  // Adjust number of periods
    var Q   = T * C/V;           // 
    var A   = rates.nominalapr ? low * .01 / C : Math.pow(1 + I * .01,1/C) -1;
	if (A == 0) {
		var M = P/Q;
	} else {
		var M 	= (A * P) / (1 - Math.pow(1 + A,-Q)) || 0;
	}
	var R	= M * Q;
	
	return {'repayment':M,'total':R,'interest':R-principal,'processing':preP.processing,'actual':A};
}

function lrc_term (rates) {
    
	var P = rates.period.toLowerCase();
    if (P == 'years') var C = 1;
    if (P == 'months') var C = 12;
    if (P == 'weeks') var C = 52;
    if (P == 'days') var C = 365;
    return C;
}

function lrc_rterm (rates) {
    
	var P = rates.repaymentperiod.toLowerCase();
    if (P == 'ryears') var C = 1;
    if (P == 'rmonths') var C = 12;
    if (P == 'rweeks') var C = 52;
    if (P == 'rdays') var C = 365;
    return C;
}

function lrc_apply_all() {
	
	var $ = jQuery;
    
    /* Select all relevant loan slider forms */
	$(lrc_loan_selector).each(function() {
		/* Initialize sliders */
		var sliders = $(this).find('[data-lrc]'), x = $(this);
		
        sliders.change(lrcCalculate);
        sliders.lrc({polyfill:false});
        
		var form	= $(this),
			rates	= lrc__rates[form.attr('id')],
			buttons	= form.find('.circle-control');
		
		buttons.filter('.lrc-down').click(function() {
			
			var range = $(this).closest('.range').find('input[type=range]');
			var v = parseFloat(range.val());
			var s = parseFloat(range.attr('step')) || 1;
			var m = parseFloat(range.attr('min'));
			var n = v - s;
			if (n < m) range.val(m); 
			else range.val(n);
			
			range.change();
			
		});
		
		buttons.filter('.lrc-up').click(function() {
			
            var range = $(this).closest('.range').find('input[type=range]');
			var v = parseFloat(range.val());
			var s = parseFloat(range.attr('step')) || 1;
			var m = parseFloat(range.attr('max'));
			var n = v + s;
			if (n > m) range.val(m); 
			else range.val(n);			
			
			range.change();
			
		});
        
        form.find('input[name=creditselector]').change(function() {
			$(sliders[0]).change();
		});
		
		form.find('select').change(function() {
			$(sliders[0]).change();
		});
		
		$(sliders[0]).change();
		
	});
	
}
jQuery(document).ready(function($) {
    
	lrc_apply_all();

});

String.prototype.lrc_rounding = function(rates) {
    
    var rr = rates.rounding;
    var r = 1;

    if (rr == 'tenround')  var r = 10;
    if (rr == 'hundredround') var r = 100;
    if (rr == 'thousandround') var r = 1000;
    if (rr == 'noround') var num = this;
    else var num = Math.round(this / r) * r;
    
    var rs = rates.separator;
    
    if (rs == 'none') return num;
    if (rs == 'apostrophe')  var s = "'";
    else if (rs == 'dot')  var s = ".";
    else if (rs == 'comma')  var s = ",";
	else var s = ' ';
    var str = num.toString().split('.');
    if (str[0].length >= 4) {
        str[0] = str[0].replace(/(\d)(?=(\d{3})+$)/g, '$1'+s);
    }
    if (rs == 'dot' || rates.decimalcomma) var decimalsdevider = ',';
    else var decimalsdevider = '.';
    return str.join(decimalsdevider);
}