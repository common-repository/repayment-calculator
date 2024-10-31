<?php
add_action('admin_menu', 'lrc_page_init');
add_action('admin_notices', 'lrc_admin_notice' );
add_action('admin_enqueue_scripts', 'lrc_scripts_init');

function lrc_page_init() {
	add_options_page('Repayment Calculator', 'Repayment Calculator', 'manage_options', __FILE__, 'lrc_settings');
}

function lrc_settings(){
    
    $apostrophe=$none=$comma=$space=$dot=$before=$after=false;
    $days=$weeks=$months=$years=false;
    $rdays=$rweeks=$rmonths=$ryears=false;
    $non=$none=$float=$always=false;
    $fixed=$percent=false;
    $beforeinterest=$afterinterest=false;
    $tenround=$hundredround=$thousandround = false;
    
    if( isset( $_POST['Submit'])) {
        
        $options = array(
            'formheader',
            'currency',
            'ba',
            'separator',
            'decimals',
            'rounding',
            'loanlabel',
            'loanmin',
            'loanmax',
            'loaninitial',
            'loanstep',
            'termlabel',
            'period',
            'periodmin',
            'periodmax',
            'periodinitial',
            'periodstep',
            'singleperiodlabel',
            'periodlabel',
            'adminfeevalue',
            'adminfeevalue',
            'adminfeetype',
            'adminfeemin',
            'adminfeemax',
            'adminfeewhen',
            'termfeevalue',
            'primary',
            'secondary',
            'trigger',
            'creditselectorlabel',
            'initialrating',
            'triggers',
            'separator',
            'decimals',
            'rounding',
            'repaymentperiod',
            'applynowlabel',
            'applynowaction',
            'querystructure',
            'sort',
            'input-background',
            'input-colour',
            'slider-colour',
            'output-background',
            'output-colour',
            'apply-background',
            'hover-background',
            'apply-label',
            'excellent-colour',
            'verygood-colour',
            'good-colour',
            'fair-colour',
            'poor-colour'
        );
        
		foreach ($options as $item) {
            if (is_array($_POST[$item])) {
                if ($item == 'triggers') {
                    $triggers = array();
                    for ($i = 0; $i < 5; $i++) {
                        $x = $_POST['triggers'][$i];
                        $triggers[] = array(
                            'name' => trim(stripslashes($_POST['triggers'][$i]['name'])),
                            'low' => (float) trim(stripslashes($_POST['triggers'][$i]['low']))
                        );
                    }
                $settings['triggers'] = $triggers;
                }
            } else {
				$settings[$item] = stripslashes($_POST[$item]);
            }
		}
        
        $options = array(
            'currencyspace',
            'decimalcomma',
            'adminfee',
            'applynow',
            'showtermmarkers',
            'showloanmarkers',
            'usecreditselector',
            'termfee',
            'applynowquery',
            'nominalapr',
            'output-strong'
        );
        foreach ($options as $item) {
            $settings[$item] = isset($_POST[$item]) ? 'checked' : '';
        }

        $sort = explode(",", $settings['sort']);
        foreach ($sort as $item) {
            $settings['use'.$item] = isset($_POST['use'.$item]) ? 'checked' : '';
            $settings[$item.'caption'] = sanitize_text_field( $_POST[$item.'caption']);
        }
        update_option( 'lrc_settings', $settings);

		lrc_admin_notice(__('The settings have been updated', 'lrc'));
    }

    if( isset( $_POST['Reset'])) {
		delete_option('lrc_settings');
		lrc_admin_notice(__('The settings have been reset', 'lrc'));
	}

	$settings = lrc_get_stored_settings();

    ${$settings['ba']}              = 'checked="checked"';
    ${$settings['period']}          = 'checked="checked"';
    ${$settings['repaymentperiod']} = 'checked="checked"';
    ${$settings['separator']}       = 'checked="checked"';
    ${$settings['decimals']}        = 'checked="checked"';
    ${$settings['rounding']}        = 'checked="checked"';
    ${$settings['initialrating']}   = 'checked="checked"';
    ${$settings['adminfeetype']}    = 'checked';
    ${$settings['adminfeewhen']}    = 'checked';
    
    if ($settings['ba'] == 'before') {
        $settings['cb'] = $settings['currency'];
        $settings['ca'] = ' ';
    } else {
        $settings['ca'] = $settings['currency'];
        $settings['cb'] = ' ';
    }
    
    $content ='<form method="post" action="">
    <div class="lrc-options">';
    
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">

    <h2>'.__('Using the Plugin', 'lrc').'</h2>';
    
    $content .='<p>'.__('Add the form to a post or page using the shortcode [lrc]', 'lrc').'.</p>
    
    <p>'.__('If you need help', 'lrc').' <a href="https://loanpaymentplugin.com/credit-scores/" target="_blank">'.__('click here', 'lrc').'</a> or send an email to <a href="mailto:mail@quick-plugins.com">mail@quick-plugins.com</a>.</p>'; 
    
    $content .='</fieldset>';
    
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Form Header', 'lrc').'</h2>
    <p class="description">'.__('Add a title to the top of the form', 'lrc').'.</p>
    <p><input type="text" name="formheader" value ="' . $settings['formheader'] . '" /></p>
    
    </fieldset>';
    
    // Amount Slider
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Amount', 'lrc').' '.__('Slider', 'lrc').' '.__('Settings', 'lrc').'</h2>';
    
    $content .='<p>'.__('Amount', 'lrc').' '.__('Slider', 'lrc').' '.__('Label', 'lrc').':<input type="text" name="loanlabel" value ="' . $settings['loanlabel'] . '" /></p>
    
    <p>'.__('Currency', 'lrc').' '.__('Symbol','lrc').': <input type="text" style="width:3em;" name="currency" value ="' . $settings['currency'] . '" /></p>
    
    <p>'.__('Minimum value', 'lrc').': '.$settings['cb'].'<input type="text" style="width:5em;" name="loanmin" value ="' . $settings['loanmin'] . '" />'.$settings['ca'].'&nbsp;&nbsp;&nbsp;
    '.__('Maximum value', 'lrc').': '.$settings['cb'].'<input type="text" style="width:5em;" name="loanmax" value ="' . $settings['loanmax'] . '" />'.$settings['ca'].'&nbsp;&nbsp;&nbsp;
    '.__('Initial value', 'lrc').': '.$settings['cb'].'<input type="text" style="width:5em;" name="loaninitial" value ="' . $settings['loaninitial'] . '" />'.$settings['ca'].'&nbsp;&nbsp;&nbsp;
    '.__('Step', 'lrc').': '.$settings['cb'].'<input type="text" style="width:5em;" name="loanstep" value ="' . $settings['loanstep'] . '" />'.$settings['ca'].'</p>
    
    <p><b>'.__('Show markers', 'lrc').':</b> <input type="checkbox" name="showloanmarkers" value="checked" '.$settings['showloanmarkers'].'> <span class = "description">'.__('Small steps will mean lots of marker lines. Use with caution', 'lrc').'</span></p>
    
    </fieldset>';
    
    // Term Slider
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Slider', 'lrc').' '.__('Settings', 'lrc').'</h2>';
    
    $content .='<p>'.__('Term', 'lrc').' '.__('Slider', 'lrc').' '.__('Label', 'lrc').':<input type="text" name="termlabel" value ="' . $settings['termlabel'] . '" /></p>';

    $content .='<p>'.__('Minimum term', 'lrc').': <input type="text" style="width:5em;" name="periodmin" value ="' . $settings['periodmin'] . '" /> ' . $settings['period'] . '&nbsp;&nbsp;&nbsp;
    '.__('Maximum term', 'lrc').': <input type="text" style="width:5em;" name="periodmax" value ="' . $settings['periodmax'] . '" /> ' . $settings['period'] . '&nbsp;&nbsp;&nbsp;
    '.__('Initial term', 'lrc').': <input type="text" style="width:5em;" name="periodinitial" value ="' . $settings['periodinitial'] . '" /> ' . $settings['period'] . '&nbsp;&nbsp;&nbsp;
    '.__('Step', 'lrc').': <input type="text" style="width:5em;" name="periodstep" value ="' . $settings['periodstep'] . '" /> ' . $settings['period'] . '</p>
	
    <p><b>'.__('Loan Period', 'lrc').':</b> <input type="radio" name="period" value="days" ' . $days . ' />'.__('Days', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="period" value="weeks" ' . $weeks . ' />'.__('Weeks', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="period" value="months" ' . $months . ' />'.__('Months', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="period" value="years" ' . $years . ' />'.__('Years', 'lrc').'</p>
    <p><b>'.__('Term', 'lrc').' '.__('Labels', 'lrc').'</b> '.__('Singular', 'lrc').': <input type="text" style="width:6em" placeholder="'.rtrim($settings['period'],'s').'" name="singleperiodlabel" value ="' . $settings['singleperiodlabel'] . '" />&nbsp;&nbsp;&nbsp;
    '.__('Plural', 'lrc').': <input type="text" style="width:6em" placeholder="'.$settings['period'].'" name="periodlabel" value ="' . $settings['periodlabel'] . '" /></p>
    <p><b>'.__('Show markers', 'lrc').':</b> <input type="checkbox" name="showtermmarkers" value="checked" '.$settings['showtermmarkers'].'> <span class = "description">'.__('Small steps will mean lots of marker lines. Use with caution', 'lrc').'</span></p>';
    
    $content .= '</fieldset>';
    
    // Interest Rate and Credit Scores
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">';

    $content .= '<h2>'.__('Interest', 'lrc').'</h2>
    
    <p>'.__('Primary Rate', 'lrc').': <input type="text" name="primary" style="width:3em;" value ="' . $settings['primary'] . '" />% APR&nbsp;&nbsp;&nbsp;
    '.__('Secondary Rate', 'lrc').': <input type="text" name="secondary" style="width:3em;" value ="' . $settings['secondary'] . '" />% APR&nbsp;&nbsp;&nbsp;
    '.__('Trigger', 'lrc').': <input type="text" name="trigger" style="width:5em;" value ="' . $settings['trigger'] . '" /></p>
    
    <p><b>'.__('Use nominal APR', 'lrc').':</b> <input type="checkbox" name="nominalapr" value="checked" '.$settings['nominalapr'].'>  <span class="description">('.__('The default is', 'lrc').' <a href="https://loanpaymentplugin.com/features/interest-calculations/apr-conversion/">'.__('Effective APR', 'lrc').'</a>)</span></p>
    
    <p><b>'.__('Use credit ratings', 'lrc').':</b> <input type="checkbox" name="usecreditselector" value="checked" '.$settings['usecreditselector'].'></p><p class="description">'.__('Disables repayment interest rate', 'lrc').'</p>
    
    <p>'.__('Interest', 'lrc').' '.__('Selector', 'lrc').' '.__('Label', 'lrc').':<input type="text" name="creditselectorlabel" value ="' . $settings['creditselectorlabel'] . '" /></p>
    <table id="creditselector">
        <thead>
			<tr>
                <th>'.__('Label', 'lrc').'</th>
				<th>'.__('Rate', 'lrc').'</th>
                <th>'.__('Colour', 'lrc').'</th>
                <th>'.__('Initial Rating', 'lrc').'</th>
			</tr>
    </thead><tbody>';
    
    $classes = ['poor','fair','good','verygood','excellent'];
		
    for ($i = 0; $i < 5; $i++) {
        $checked = $settings['initialrating'] == $i ? 'checked="checked"' : '';
        $content .= '<tr>
        <td><input type="text" style="width:8em;" name="triggers['.$i.'][name]" value ="' . $settings['triggers'][$i]['name'] . '" /></td>
        <td><input type="text" style="width:4em;" name="triggers['.$i.'][low]" value ="' . $settings['triggers'][$i]['low'] . '" />%</td>
        <td><input type="text" class="lrc-color" label="' . $settings[$classes[$i].'-colour'].'" name="'.$classes[$i].'-colour" value="' . $settings[$classes[$i].'-colour'].'" /></td>
        <td><input type="radio" name="initialrating" value ="' . $i . '" '.$checked.'/></td>
        </tr>';
    }
    $content .='</tbody></table>';
    
    $content .= '';

    $content .= '</fieldset>';

    // Processing Fee

    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Processing Fee', 'lrc').'</h2>
    <p><input type="checkbox" name="adminfee" value="checked" ' . $settings['adminfee'] . '/>'.__('Add a processing fee', 'lrc').' '.__('calculated from the amount slider value', 'lrc').'.</p>
    <p><b>'.__('Amount', 'lrc').' '.__('Processing fee', 'lrc').':</b> <input type="text" style="width:4em;" name="adminfeevalue" . value ="' . $settings['adminfeevalue'] . '" />&nbsp;&nbsp;&nbsp;<input type="radio" name="adminfeetype" value="fixed" ' . $fixed . ' />'.__('Fixed', 'lrc').'&nbsp;&nbsp;&nbsp;<input type="radio" name="adminfeetype" value="percent" ' . $percent . ' />'.__('Percent', 'lrc').'</p>
    
    <p><input type="checkbox" name="termfee" value="checked" ' . $settings['termfee'] . '/>'.__('Add a processing fee', 'lrc').' '.__('calculated from the term slider value', 'lrc').' ('.__('eg: an amount multiplied by the number of', 'lrc').' '. $settings['period'] . ').</p>
    <p><b>'.__('Term', 'lrc').' '.__('Processing fee', 'lrc').':</b> <input type="text" style="width:4em;" name="termfeevalue" . value ="' . $settings['termfeevalue'] . '" /> x '.__('number of', 'lrc').' '. $settings['period'] . ').</p>
    </p>'.__('Add fees', 'lrc').': <input type="radio" name="adminfeewhen" value="beforeinterest" ' . $beforeinterest . ' />'.__('before interest', 'lrc').'&nbsp;&nbsp;&nbsp;<input type="radio" name="adminfeewhen" value="afterinterest" ' . $afterinterest . ' />'.__('after interest', 'lrc').'
    <p>'.__('Minimum fee', 'lrc').': <input type="text" style="width:4em;" name="adminfeemin" . value ="' . $settings['adminfeemin'] . '" />&nbsp;&nbsp;&nbsp;'.__('Maximum fee', 'lrc').': <input type="text" style="width:4em;" name="adminfeemax" . value ="' . $settings['adminfeemax'] . '" /></p>
    
    </fieldset>';

    // Output Section
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
        
        <h2>'.__('Output Table', 'lrc').'</h2>
        <p>'.__('Check those outputs you want to use. Drag and drop to change the order', 'lrc').'.</p>
        <style>table#sorting{width:100%;}
        #sorting tbody tr{outline: 1px solid #888;background:#E0E0E0;}
        #sorting tbody td{padding: 2px;vertical-align:middle;}
        #sorting{border-collapse:separate;border-spacing:0 5px;}</style>
        <script>
        jQuery(function() 
        {var lrc_rsort = jQuery( "#lrc_rsort" ).sortable(
        {axis: "y",cursor: "move",opacity:0.8,update:function(e,ui)
        {var order = lrc_rsort.sortable("toArray").join();jQuery("#lrc_register_sort").val(order);}});});
        </script>
        <table id="sorting">
        <thead>
        <tr>
        <th style="width:5%">'.__('Use', 'lrc').'</th>
        <th style="width:15%">'.__('Output', 'lrc').'</th>
        <th>'.__('Value', 'lrc').'</th>
        </tr>
        </thead>
        
        <tbody id="lrc_rsort">';
        $sort = explode(",", $settings['sort']);
        foreach ($sort as $name) {
            switch ( $name ) {
                case 'principal':
                    $label = __('Principal', 'lrc');
                    $addon = __('Includes the currency symbol', 'lrc');
                    $type= 'text';
                break;
                case 'term':
                    $label = __('Term', 'lrc');
                    $addon = __('Includes the period', 'lrc');
                    $type= 'text';
                break;
                case 'rate':
                    $label = __('Interest', 'lrc').' '.__('rate', 'lrc');
                    $addon = __('Includes the % symbol', 'lrc');
                    $type= 'text';
                break;
                case 'rating':
                    $label = __('Credit Rating', 'lrc');
                    $addon = '';
                    $type= 'text';
                break;
                case 'interest':
                    $label = __('Interest', 'lrc');
                    $addon = __('The interest to pay', 'lrc').' ('.__('includes the currency symbol', 'lrc').')';
                    $type= 'text';
                break;
                case 'processing':
                    $label = __('Processing', 'lrc');
                    $addon = __('The total processing fee', 'lrc').' ('.__('includes the currency symbol', 'lrc').')';
                    $type= 'text';
                break;
                case 'repayment':
                    $label = __('Repayment', 'lrc');
                    $addon = __('The value of each repayment', 'lrc').' ('.__('includes the currency symbol', 'lrc').')';
                    $type= 'text';
                break;
                case 'grandtotal':
                    $label = __('Total to Pay', 'lrc');
                    $addon = __('Includes the currency symbol', 'lrc');
                    $type= 'text';
                break;
        }
        $content .= '<tr id="'.$name.'">
        <td style="width:5%"><input type="checkbox" name="use'.$name.'" ' . $settings['use'.$name] . ' value="checked" /></td>
        <td style="width:15%">'.$label.'</td>
        <td><input type="text" name="'.$name.'caption" value="' . $settings[$name.'caption'] . '" /></td>
        <td>'.$addon.'</td></tr>';
    }
    $content .='</tbody>
    </table>
    <input type="hidden" id="lrc_register_sort" name="sort" value="'.$settings['sort'].'" />
        
    </fieldset>';

    // Number Formats
    $content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <h2>'.__('Output Options', 'lrc').'</h2>
    
    <p><b>'.__('Repayment Period', 'lrc').':</b> <input type="radio" name="repaymentperiod" value="rdays" ' . $rdays . ' />'.__('Days', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="repaymentperiod" value="rweeks" ' . $rweeks . ' />'.__('Weeks', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="repaymentperiod" value="rmonths" ' . $rmonths . ' />'.__('Months', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="repaymentperiod" value="ryears" ' . $ryears . ' />'.__('Years', 'lrc').'</p>
    
    <p><b>'.__('Currency', 'lrc').' '.__('Position', 'lrc').':</b> <input type="radio" name="ba" value="before" ' . $before . ' />'.__('Before amount', 'lrc').'&nbsp;<input type="radio" name="ba" value="after" ' . $after . ' />'.__('After amount', 'lrc').'</p>
    
    <p><b>'.__('Currency', 'lrc').' '.__('Space', 'lrc').':</b> <input type="checkbox" name="currencyspace" value="checked" '.$settings['currencyspace'].'> '.__('Adds a space between currency symbol and amount', 'lrc').'</p>
    
    <p><b>'.__('Thousands separator', 'lrc').':</b> <input type="radio" name="separator" value="none" ' . $none . ' />None&nbsp;&nbsp;&nbsp;
    <input type="radio" name="separator" value="comma" ' . $comma . ' />Comma&nbsp;&nbsp;&nbsp;
    <input type="radio" name="separator" value="apostrophe" ' . $apostrophe . ' />Apostrophe&nbsp;&nbsp;&nbsp;
    <input type="radio" name="separator" value="dot" ' . $dot . ' />Period&nbsp;&nbsp;&nbsp;
    <input type="radio" name="separator" value="space" ' . $space . ' />Space</p>
    <p class="description">'.__('The period separator changes the decimal to a comma', 'lrc').'</p>
    
    <p><b>'.__('Decimals', 'lrc').':</b> <input type="radio" name="decimals" value="non" ' . $non . ' />'.__('None', 'lrc').' ($1234)&nbsp;&nbsp;&nbsp;
    <input type="radio" name="decimals" value="float" ' . $float . ' />'.__('Floating', 'lrc').' ($1234 or $1234.56)&nbsp;&nbsp;&nbsp;
    <input type="radio" name="decimals" value="always" ' . $always . ' />'.__('Always on', 'lrc').' ($1234.00 or $1234.56)</p>
    <p><b>'.__('Decimal Comma', 'lrc').':</b> <input type="checkbox" name="decimalcomma" value="checked" '.$settings['decimalcomma'].'> '.__('Shows a comma on decimals', 'lrc').'</p>
    
    <p><b>'.__('Rounding', 'lrc').':</b> <input type="radio" name="rounding" value="noround" ' . $noround . ' />'.__('None', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="rounding" value="tenround" ' . $tenround . ' />'.__('Nearest ten', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="rounding" value="hundredround" ' . $hundredround . ' />'.__('Nearest hundred', 'lrc').'&nbsp;&nbsp;&nbsp;
    <input type="radio" name="rounding" value="thousandround" ' . $thousandround . ' />'.__('Nearest thousand', 'lrc').'&nbsp;&nbsp;&nbsp;<em>'.__('Use With Caution', 'lrc').'!</em></p>
    </fieldset>';
    
    // Apply Now Button
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    <h2>'.__('Apply Now Button', 'lrc').'</h2>
    <p><input type="checkbox" name="applynow"  value="checked" ' . $settings['applynow'] . '/> '.__('Add an Apply now button to the form', 'lrc').'</p>
    <p class="description">'.__('This does not process the form data', 'lrc').'. '.__('All the button does is send the visitor to the URL given below', 'lrc').'.</p>
    <p>'.__('Apply now label', 'lrc').':<input type="text" name="applynowlabel" value ="' . $settings['applynowlabel'] . '" /></p>
    <p>'.__('Form action URL', 'lrc').':<input type="text" name="applynowaction" value ="' . $settings['applynowaction'] . '" /></p>
    <p><input type="checkbox" name="applynowquery"  value="checked" ' . $settings['applynowquery'] . '/> '.__('Append query to URL', 'lrc').'<p>
    <p>'.__('Query string', 'lrc').':<input type="text" name="querystructure" value ="' . $settings['querystructure'] . '" /></p>
    <p class="descripton">Shortcode options: [amount], [term], [rating], [apr]</p>
    </fieldset>';

    // Styling
    $content .='<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">
    
    <table style="width:100%">
    <tr>
    <th style="width:33%">Input Side</th>
    <th style="width:33%">Output Side</th>
    <th style="width:33%">Apply Button</th>
    </tr>

    <tr>
    <td>'.__('Background Colour', 'lrc').':<br>
    <input type="text" class="lrc-color" label="input-background" name="input-background" value="' . $settings['input-background'] . '" /><br> 
    '.__('Labels and slider handle colour', 'lrc').':<br>
    <input type="text" class="lrc-color" label="input-colour" name="input-colour" value="' . $settings['input-colour'] . '" /><br>
    '.__('Slider Track', 'lrc').':<br><input type="text" class="lrc-color" label="slider-colour" name="slider-colour" value="' . $settings['slider-colour'] . '" /></td>
    
    <td>'.__('Background Colour', 'lrc').':<br>
    <input type="text" class="lrc-color" label="output-background" name="output-background" value="' . $settings['output-background'] . '" /><br>
    '.__('Font Colour', 'lrc').':<br><input type="text" class="lrc-color" label="output-colour" name="output-colour" value="' . $settings['output-colour'] . '" /><br>
    <input type="checkbox" name="output-strong" value="checked" '.$settings['output-strong'].'> '.__('Embolden output values', 'lrc').'.</td>
    
    <td>'.__('Background', 'lrc').':<br>
    <input type="text" class="lrc-color" label="input-background" name="apply-background" value="' . $settings['apply-background'] . '" /><br> 
    '.__('Hover', 'lrc').':<br>
    <input type="text" class="lrc-color" label="input-background" name="hover-background" value="' . $settings['hover-background'] . '" /><br>
    '.__('Label', 'lrc').':<br><input type="text" class="lrc-color" label="apply-label" name="apply-label" value="' . $settings['apply-label'] . '" /></td>
    </tr>
    
    </table>

    </fieldset>';
    
    // Save and Reset
    $content .= '<p><input type="submit" name="Submit" class="button-primary" style="color: #FFF;" value="Save Changes" /> <input type="submit" name="Reset" class="button-secondary" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the settings?\' );"/></p>
    </div>
    </form>';
	echo $content;
}

function lrc_admin_notice($message) {if (!empty( $message)) echo '<div class="updated"><p>'.$message.'</p></div>';}

function lrc_scripts_init($hook) {
    wp_enqueue_style('lrc_settings',plugins_url('settings.css', __FILE__));
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_media();
    wp_enqueue_script('lrc-media', plugins_url('media.js', __FILE__ ), array( 'jquery','wp-color-picker' ), false, true );
}