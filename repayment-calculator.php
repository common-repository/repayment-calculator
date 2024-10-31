<?php
/*
Plugin Name: Loan Calculator
Plugin URI: https://www.calculator.io/loan-calculator/
Description: Loan calculator with sliders, credit ratings, interest rates and multiple display options.
Version: 1.3
Author: Loan Calculator
Author URI: https://www.calculator.io/loan-calculator/
Text Domain: lrc
Domain Path: /languages
*/

require_once( plugin_dir_path( __FILE__ ) . '/options.php' );

add_shortcode('lrc', 'lrc_loop');

add_action('wp_enqueue_scripts', 'lrc_scripts');
add_action('wp_head', 'lrc_head_css');

add_filter('plugin_action_links', 'lrc_plugin_action_links', 10, 2 );

if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );

function lrc_block_init() {
    
    if ( !function_exists( 'register_block_type' ) ) {
        return;
    }
    
    $settings	= lrc_get_stored_settings(null);
	
    // Register our block editor script.
	wp_register_script(
		'block',
		plugins_url( 'block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' )
	);

	// Register our block, and explicitly define the attributes we accept.
	register_block_type(
        'lrc/block', array(
		'attributes' => array(
                   'calculator'  => array(
                       'type'=> 'string',
                       'default'   => 'one'
                   ),
               ),
		'editor_script'   => 'block', // The script name we gave in the wp_register_script() call.
		'render_callback' => 'lrc_loop'
        )
	);
}

add_action( 'init', 'lrc_block_init' );

function lrc_loop() {
    
    global $post;
    
    // Apply Now Button
    
    if (!empty($_POST['lrcapply'])) {
        $settings   = lrc_get_stored_settings();
        $rating     = sanitize_text_field($_POST['creditselector']);
        $credit     = $settings['triggers'][$rating]['name'];
        $apr        = $settings['usecreditselector'] ? $settings['triggers'][$rating]['low'] : $settings['primary'];
        $url        = $settings['applynowaction'];
        if ($settings['applynowquery']) {
            $settings['querystructure'] = str_replace('[amount]', sanitize_text_field($_POST['loan-amount']), $settings['querystructure']);
            $settings['querystructure'] = str_replace('[term]', sanitize_text_field($_POST['loan-period']), $settings['querystructure']);
            $settings['querystructure'] = str_replace('[rating]', $credit, $settings['querystructure']);
            $settings['querystructure'] = str_replace('[apr]', $apr, $settings['querystructure']);
            $url = $url.$settings['querystructure'];
        }
        echo "<p>".__('Redirecting....','lrc')."</p><meta http-equiv='refresh' content='0;url=$url' />";
        die();
    
	} else { // Default Display
        $digit1 = mt_rand(1,10);
        $digit2 = mt_rand(1,10);
        if( $digit2 >= $digit1 ) {
            $values['thesum'] = "$digit1 + $digit2";
            $values['answer'] = $digit1 + $digit2;
        } else {
            $values['thesum'] = "$digit1 - $digit2";
            $values['answer'] = $digit1 - $digit2;
        }
        return lrc_display($values ,array(),null);
    }
}

// Display the form on the page

function lrc_display($formvalues,$formerrors,$registered) {
    
    $settings	= lrc_get_stored_settings();

    if ($settings['ba'] == 'before') {
        $settings['cb'] = $settings['currency'];
        $settings['ca'] = ' ';
    } else {
        $settings['ca'] = $settings['currency'];
        $settings['cb'] = ' ';
    }

    if (!$settings['periodlabel']) $settings['periodlabel'] = $settings['period'];
    if (!$settings['singleperiodlabel']) $settings['singleperiodlabel'] = rtrim($settings['period'], 's');
    
	// Normalize values
	
    $outputA = array();
    
    foreach ($settings as $k => $v) {
        $outputA[$k] = $v; 
        
        if (!is_array($v)) {
		
		  if (@strtolower($v) == 'checked') $outputA[$k] = true;
		
		  if ($v == '') $outputA[$k] = false;
		
		  if (@preg_match('/[0-9.]+/',$v)) $outputA[$k] = (float) $v;
        }
	}
    
    $newTriggers = [];
	foreach ($outputA['triggers'] as $k => $v) {
		$newTriggers[] = $v;
	}
	
	$outputA['triggers'] = $newTriggers;
    
    if (!isset($formvalues['loan-amount'])) $formvalues['loan-amount'] = $settings['loaninitial'];
    if (!isset($formvalues['loan-period'])) $formvalues['loan-period'] = $settings['periodinitial'];

	$s_form = 'N/A';
	
	$output  = '<script type="text/javascript">';
	$output .= 'lrc__rates["lrc"] = '.json_encode($outputA).';';
	$output .= 'lrc_form = '.json_encode($s_form).';';
	$output .= '</script>';
    $output .= '<form action="" class="lrc_form" method="POST" id="lrc">';
	
    // Form Header
    
    if ($settings['formheader']) {
        $output .= '<h2>'.$settings['formheader'].'</h2>';
    }
    
    $output .= '<div class="lrc-sections lrc-float"><div class="lrc-inputs">';
    
    $output .= '<div class="range lrc-slider-principal">';
	
    // Principal Slider
    
    if ($settings['loanlabel'] ) {
        $output .= '<div class="lrc-slider-label">'.$settings['loanlabel'].'</div>';
    }
    
    $output .= '<div class="lrc_slider_output"><div class="output-pad"><span class="lrc-down circle-control"></span><span class="output-values"><output></output></span><span class="lrc-up circle-control"></span></div></div>';
            
    $output .= '<input type="range" name="loan-amount" min="'.$settings['loanmin'].'" max="'.$settings['loanmax'].'" value="'.$formvalues['loan-amount'].'" step="'.$settings['loanstep'].'" data-lrc>';
    
    if ($settings['showloanmarkers']) {
    
        $output .= '<div class="lrc_slider_markers" style="position: relative; margin-left: 8px; margin-right: 8px; border-left: 1px solid black; border-right: 1px solid black; height: 10px">';
			
        $inner_value = (float) $settings['loanmax'] - $settings['loanmin'];
        $pps = $inner_value / $settings['loanstep'];
        $ppw = 100 / $pps;
							
        for ($i = 1; $i < $pps; $i++) {
            $output .= '<div class="lrc_slider_marker" style="position: absolute; height: 10px; left: '.$ppw * $i.'%; margin-left: -1px; width: 1px; background-color: black;"></div>';
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    // Term Slider
        
    if ($settings['termlabel'] ) {
        $output .= '<div class="lrc-slider-label">'.$settings['termlabel'].'</div>';
    }
    
    $output .= '<div class="range lrc-slider-term">';
			
	$output .= '<div class="lrc_slider_output"><div class="output-pad"><span class="lrc-down circle-control"></span><span class="output-values"><output></output></span><span class="lrc-up circle-control"></span></div></div>';

    $output .= '<input type="range" name="loan-period" min="'.$settings['periodmin'].'" max="'.$settings['periodmax'].'" value="'.$formvalues['loan-period'].'" step="'.$settings['periodstep'].'" data-lrc>';
    
    if ($settings['showtermmarkers']) {
    
        $output .= '<div class="lrc_slider_markers" style="position: relative; margin-left: 9px; margin-right: 5px; border-left: 1px solid black; border-right: 1px solid black; height: 10px">';
			
        $inner_value = (float) $settings['periodmax'] - $settings['periodmin'];
        $pps = $inner_value / $settings['periodstep'];
        $ppw = 100 / $pps;
							
        for ($i = 1; $i < $pps; $i++) {
            $output .= '<div class="lrc_slider_marker" style="position: absolute; height: 10px; left: '.$ppw * $i.'%; margin-left: -1px; width: 1px; background-color: black;"></div>';
        }
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    // Credit Rating Selector
    
    if ($settings['usecreditselector']) {
        
        $classes = ['poor','fair','good','verygood','excellent'];
    
        if ($settings['creditselectorlabel']) $output .= '<div class="lrc-slider-label">'.$settings['creditselectorlabel'].':</div>';
    
        $output .= '<div class="creditscore">';

        $count = count(array_filter(array_column($settings['triggers'],'name')));
        
        for ($i = 0; $i < $count; $i++) {
        
            $checked = $i == $settings['initialrating'] ? 'checked="checked"' : '';
            $output .= '<input type="radio" name="creditselector" value="'.$i.'" '.$checked.' id="'.$settings['triggers'][$i]['name'].'"><label for="'.$settings['triggers'][$i]['name'].'" class="'.$classes[$i].'">'.$settings['triggers'][$i]['name'].'</label>';
        
        }
        $output .= '</div>';
    
        $output .= '<div style="clear:both"></div>';
    }
    
    $output .= '</div>';
    
    // Display output messages
        
    $output .= '<div class="lrc-outputs">';

    $strongon = $settings['output-strong'] ? '<strong>' : '';
	$strongoff = $settings['output-strong'] ? '</strong>' : '';
    
    $output .= '<table class="output-table">';

    $sort = explode(",", $settings['sort']);
    for ($i = 0; $i < count($sort); $i++) {
        $name = $sort[$i];
        if ($settings['use'.$name]) {
            if (!$i) {
                $output .= '<tr><td class="table-label"><a href="https://www.calculator.io/loan-calculator/" target="_blank">'.$settings[$name.'caption'].'</a></td><td class="values-colour table-output">'.$strongon.'<span class="'.$name.'"></span>'.$strongoff.'</td></tr>';
            } else {
                $output .= '<tr><td class="table-label">'.$settings[$name.'caption'].'</td><td class="values-colour table-output">'.$strongon.'<span class="'.$name.'"></span>'.$strongoff.'</td></tr>';
            }
        }
    }
    
    $output .= '</table>';
    
    $output .= '</div>';

    $output .= '</div>';
    
    // Application Form
    
    if ($settings['applynow']) $output .= '<div class="ltc_register"><input type="submit" class="submit" name="lrcapply" onclick="this.value=\'Processing\'" value="'.$settings['applynowlabel'].'" /></div>';

    $output .= '</form>';
    return $output;
}

// Enqueue Scripts and Styles

function lrc_scripts() {
    wp_enqueue_style( 'lrc_style',plugins_url('repayment-calculator.css', __FILE__));
    wp_enqueue_script("jquery-effects-core");
	wp_enqueue_script('lrc_script',plugins_url('repayment-calculator.js', __FILE__ ), array( 'jquery' ));
    wp_add_inline_script( 'lrc_script', 'lrc__rates = [];');
}

// Dashboard Link

function lrc_plugin_action_links($links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ) {
		$lrc_links = '<a href="'.get_admin_url().'options-general.php?page=repayment-calculator/settings.php">'.__('Settings','lrc').'</a>';
		array_unshift( $links, $lrc_links );
		}
	return $links;
}

// Add to Head
function lrc_head_css () {
    ?>
    <style>
    <?php echo lrc_generate_css(); ?>   
    </style>
    <?php
}

function lrc_generate_css() {
    $settings	= lrc_get_stored_settings();
    $excellent = lrc_hex2rgb($settings['excellent-colour']);
    $verygood = lrc_hex2rgb($settings['verygood-colour']);
    $good = lrc_hex2rgb($settings['good-colour']);
    $fair = lrc_hex2rgb($settings['fair-colour']);
    $poor = lrc_hex2rgb($settings['poor-colour']);
    $width = '';
    $count = count(array_filter(array_column($settings['triggers'],'name')));
    for ($i = 1; $i <= $count; $i++) $width = $width.((100 - $count+1) / $count).'% ';
    
    $style = '.lrc {background: '.$settings['slider-colour'].';}
.lrc__fill, .lrc__handle {background: '.$settings['input-colour'].';}
.lrc-slider-label {color: '.$settings['input-colour'].';}
.creditscore {grid-template-columns: '.$width.';}
.creditscore .excellent{color: #FFF;background-color: rgba('.$excellent[0].','.$excellent[1].','.$excellent[2].',0.5);}
.creditscore .verygood {color: #FFF;background-color: rgba('.$verygood[0].','.$verygood[1].','.$verygood[2].',0.5);}
.creditscore .good  {color: #FFF;background-color: rgba('.$good[0].','.$good[1].','.$good[2].',0.5);}
.creditscore .fair{color: #FFF;background-color: rgba('.$fair[0].','.$fair[1].','.$fair[2].',0.5);}
.creditscore .poor {color: #FFF;background-color: rgba('.$poor[0].','.$poor[1].','.$poor[2].',0.5);}
.creditscore .excellent:hover, .creditscore input[type="radio"]:checked + .excellent {background-color: rgba('.$excellent[0].','.$excellent[1].','.$excellent[2].',1);}
.creditscore .verygood:hover, .creditscore input[type="radio"]:checked + .verygood {background-color: rgba('.$verygood[0].','.$verygood[1].','.$verygood[2].',1);}
.creditscore .good:hover, .creditscore input[type="radio"]:checked + .good {background-color: rgba('.$good[0].','.$good[1].','.$good[2].',1);}
.creditscore .fair:hover, .creditscore input[type="radio"]:checked + .fair {background-color: rgba('.$fair[0].','.$fair[1].','.$fair[2].',1);}
.creditscore .poor:hover, .creditscore input[type="radio"]:checked + .poor {background-color: rgba('.$poor[0].','.$poor[1].','.$poor[2].',1);}
.creditscore input[type="radio"]:checked + .poor {background-color: rgba('.$poor[0].','.$poor[1].','.$poor[2].',1);}
.output-values output {color: '.$settings['input-colour'].';}
.lrc-inputs {border-color: '.$settings['output-background'].';}
.lrc-outputs {background-color: '.$settings['output-background'].';}
.lrc-down:after, .lrc-up:after, .lrc-up:before {background-color: '.$settings['input-colour'].';}
table td.table-label,table td.table-output {color: '.$settings['output-colour'].';}
.lrc-down,.lrc-up {border-color: '.$settings['input-colour'].';}
.lrc-up:hover, .lrc-down:hover { background-color: '.$settings['slider-colour'].';}
.ltc_register .submit {background: '.$settings['apply-background'].';color:'.$settings['apply-label'].';}
.ltc_register .submit:hover {background: '.$settings['hover-background'].';}';

return $style;
}

   
    // Hex to RGB converter
function lrc_hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);
   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   return $rgb;
}
    
    