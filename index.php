<?php
/*
Plugin Name: Add Scripts & Short-codes in Footer
Description: Most themes only offer putting the sanitized phrase in the Footer. With this plugin, you can enrich your website FOOTER. Simply go to Appearence>Widgets (P.S. Also, review <a href="https://www.protectpages.com/blog/must-have-wordpress-plugins/">OTHER MUST-HAVE PLUGINS FOR EVERYONE</a>. )
Version: 1.15
Text Domain: add-scripts-short-codes-in-footer
Domain Path: /languages
Author: TazoTodua
Author URI: http://www.protectpages.com/profile
Plugin URI: https://www.protectpages.com/web-coding-programming-software/our-wordpress-plugins/
Donate link: http://paypal.me/tazotodua
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

namespace AddScriptAndCodesInFooter;

if (!defined('ABSPATH')) exit; include_once(__DIR__.'/____defaults.php');

class MyClass{
	use \my_default_methods_T_Todua;
	
	public function __construct_my(){
		add_action('wp_head',			array($this, 'my_header_func')		);
		add_action('wp_loaded',			array($this, 'my_footer_deregister'), 	99);
		add_action('widgets_init',		array($this, 'my_widgets_init')		);
		//add_action('plugins_loaded', array($this, 'plugin_loaded_my'), 11);
	}	
	
	public function declare_static_settings(){
		//multisite, singlesite, both
		$this->static_settings=array('show_opts'=>true, 'required_role'=>'manage_options', 'managed_from'=>'multisite', 'allowed_on'=>'multisite');      
		
		$this->user_initial_options	= array( 
			'remove_footer'	=> false, 
			'footer_id'		=> ''	
		);
	}
	

	// ============================================================================================================== //
	// ============================================================================================================== //
	
	public function my_widgets_init(){
		$value= 'footer-addition-asacif';
		register_sidebar( array('name' => 'WP Footer Addition' ,'id' => strtolower($value),	'before_widget'=>'<div class="sideb_clas '.$value.'">','after_widget'=>'</div>','before_title'=>'<h2 class="sideb_around">','after_title'=>'</h2>',) );
	}

	public function my_footer_deregister(){
		if($this->opts['remove_footer']){
			remove_all_actions('wp_footer') ;
			//foreach( $GLOBALS['wp_filter']['wp_footer']->callbacks  as $eachCallback){
		}
		add_action('wp_footer',		array($this, 'my_footer_func2'), 	10);
	}


	public function my_footer_func2(){
	  if(is_active_sidebar('footer-addition-asacif')){
		echo '<div class="footer-addition footer-asacif">';
		dynamic_sidebar('footer-addition-asacif');
		echo '</div>';
		if($this->opts['remove_footer'] && !empty($this->opts['footer_id']) ){ ?>
		<script>  (function(){ var x=document.getElementById('<?php echo $this->opts['footer_id'];?>'); if(x) {x.parentNode.removeChild(x);}  })();</script>
		<?php  
		} 
	  } 
	}
		
		

	public function my_header_func(){ 
		echo '<style>.footer-asacif{ text-align:center; display:flex; justify-content:center;  background: #e7e7e7; margin:20px 0px 0px;  background:#969696; padding: 20px 0px 10px; }  .footer-asacif *{ width:100%;} </style>';
	}
	
	public function opts_page_output(){
		//if form updated
		if(isset($_POST["_wpnonce"]) && check_admin_referer("form1_nonce_".$this->plugin_slug) ) {
			$this->opts['remove_footer']= !empty($_POST[ $this->plugin_slug ]['remove_footer']) ;
			$this->opts['footer_id']	= trim($_POST[ $this->plugin_slug ]['footer_id']) ;
			$this->update_option_CHOSEN($this->plugin_slug, $this->opts);
		}
		?>
		
		<?php $this->settings_page_part("start"); ?>
		<style>
		</style>
		<div class="newBlock settings">
			<h4></h4>
			<form method="post" action="">
			<table class="form-table">
				<tr>
					<th scope="row">
						<?php _e('Remove existing extra "wp_footer" parts from theme?', 'add-scripts-short-codes-in-footer');?>
					</th>
					<td>
						<fieldset>
							<label>
								<input name="<?php echo $this->plugin_slug;?>[remove_footer]" type="radio" value="0" <?php checked(!$this->opts['remove_footer']); ?>><?php _e( 'No', 'add-scripts-short-codes-in-footer' );?>
							</label>
							<label>
								<input name="<?php echo $this->plugin_slug;?>[remove_footer]" type="radio" value="1" <?php checked($this->opts['remove_footer']); ?>><?php _e( 'Yes', 'add-scripts-short-codes-in-footer' );?>
							</label>
							<p class="description">
							</p>
						</fieldset>
					</td>
				</tr>
				<tr>
				
					<th scope="row">
					
						<label for="footer_id">
							<?php _e('Remove existing "footer"  block using #ID', 'add-scripts-short-codes-in-footer');?>
						</label>
						
					</th>
					<td>
						<input name="<?php echo $this->plugin_slug;?>[footer_id]" id="footer_id" class="regular-text" type="text" placeholder="i.e.  site-footer" value="<?php echo $this->opts['footer_id']; ?>" >  <?php _e('p.s. Leave field empty if not needed', 'add-scripts-short-codes-in-footer');?>
						
						<p class="description">
						<?php _e('Remove existing "footer" block-ID from theme <b>using JavaScript</b>. (You can use this if the above first (in the top) solution cant remove some parts of footer. However, JavaScript removal is not a standard way, instead it\'s a temporary solution). If you want this, then enter HTML ID of target footer element.', 'add-scripts-short-codes-in-footer');?>
						</p>
					</td>
				</tr>
			</table>
			<?php 
			wp_nonce_field( "form1_nonce_".$this->plugin_slug);
			submit_button();
			?>
			</form>
		</div>
		<?php $this->settings_page_part("end"); ?>
		
		<?php
	}
}

$new = new MyClass;
	
?>