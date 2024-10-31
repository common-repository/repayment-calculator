<?php

function lrc_get_stored_settings() {

	$settings = get_option('lrc_settings');
    if(!is_array($settings)) $settings = array();
    $default = array(
        'formheader'		=> false,
        'currency'          => '$',
        'ba'                => 'before',
        'currencyspace'     => '',
        'separator'         => 'comma',
        'decimalcomma'      => '',
        'decimals'	        => 'float',
        'rounding'          => 'noround',
        'loanlabel'			=> 'How much do you want to borrow?',
        'loanmin'			=> 5000,
        'loanmax'			=> 100000,
        'loaninitial'		=> 5000,
        'loanstep'			=> 5000,
        'termlabel'         => 'For how long?',
        'period'			=> 'years',
        'periodmin'			=> 1,
        'periodmax'			=> 15,
        'periodinitial'		=> 6,
        'periodstep'		=> 1,
        'singleperiodlabel' => '',
        'periodlabel'       => '',
        'repaymentperiod'   => 'rmonths',
        'showtermmarkers'   => false,
        'showloanmarkers'   => false,
        'usecreditselector' => 'checked',
        'creditselectorlabel'=> 'Your credit rating',
        'initialrating'     => 2,
        'primary'           => 2.6,
        'secondary'         => 3.4,
        'trigger'           => 50000,
        'triggers'	=> array(
             array(
                 'name'     => 'Poor',
                 'low'      => '18.1',
             ),
             array(
                 'name'     => 'Average',
                 'low'      => '14.1',
             ),
             array(
                 'name'     => 'Good',
                 'low'      => '10.1',
             ),
             array(
                 'name'     => 'Very Good',
                 'low'      => '6.1',
             ),
             array(
                 'name'     => 'Excellent',
                 'low'      => '3',
             )
        ),
        'adminfee'			=> '',
        'adminfeevalue'		=> 15,
        'adminfeetype'		=> 'fixed',
        'adminfeemin'		=> '50',
        'adminfeemax'		=> '200',
        'adminfeewhen'		=> 'beforeinterest',
        'termfee'			=> false,
        'termfeevalue'		=> 12,
        'applynow'			=> 'checked',
        'applynowlabel'		=> __('Apply Now','quick-interest-slider'),
        'applynowaction'	=> false,
        'applynowquery'		=> false,
        'querystructure'    => '?amount=[amount]&term=[term]',
        'sort'              => 'principal,term,rate,rating,interest,processing,repayment,grandtotal',
        'userepayment'      => 'checked',
        'userate'           => 'checked',
        'userating'         => 'checked',
        'useinterest'       => 'checked',
        'usegrandtotal'     => 'checked',
        'useprincipal'      => 'checked',
        'useterm'           => 'checked',
        'useprocessing'     => 'checked',
        'repaymentcaption'  => 'Repayment amount',
        'ratecaption'       => 'APR',
        'nominalapr'        => '',
        'ratingcaption'     => 'Credit rating',
        'interestcaption'   => 'Interest to pay',
        'grandtotalcaption' => 'Total to pay',
        'principalcaption'  => 'Loan amount',
        'termcaption'       => 'Loan Term',
        'processingcaption' => 'Processing',
        'output-padding'    => '3',
        'input-background'  => '#FFFFFF',
        'input-colour'      => '#245da2',
        'slider-colour'     => '#CCCCCC',
        'output-background' => '#245da2',
        'output-colour'     => '#FFFFFF',
        'output-strong'     => 'checked',
        'apply-background'  => '#1a82c7',
        'hover-background'  => '#245da2',
        'apply-label'       => '#FFFFFF',
        'excellent-colour'  => '#0b5345',
        'verygood-colour'   => '#39b54a',
        'good-colour'       => '#fca140',
        'fair-colour'       => '#f1592a',
        'poor-colour'       => '#e74c3c',
    );
	
    $settings = lrc_apply_defaults($default,$settings);
    
	return $settings;
}

function lrc_apply_defaults(array $default, array $settings) {
	/*
		Just fill in blanks in the defaults
	*/
	foreach ($settings as $key => $value) {
		if (!isset($default[$key])) {
			$default[$key] = $value;
		} else {
			if (is_array($value)) {
				$default[$key] = lrc_apply_defaults($default[$key],$value);
			}
		}
	}
	
	/*
		Now merge settings ontop of the defaults
	*/
	return lrc_splice($settings, $default);
}

function lrc_splice($a1,$a2) {
	foreach ($a2 as $a2k => $a2v) {
		if (is_array($a2v)) {
			if (!isset($a1[$a2k])) $a1[$a2k] = $a2v;
			else {
				if (is_array($a1[$a2k])) $a1[$a2k] = lrc_splice($a1[$a2k],$a2v);
			}
		} else {
			if (!isset($a1[$a2k])) $a1[$a2k] = $a2v;
		}
	}
	return $a1;
}