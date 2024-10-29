<?php 

if(trait_exists('my_default_methods_T_Todua')) return;

trait my_default_methods_T_Todua{
	// ===================================================================================================================== //
	// =====================================   my  default block for any of my plugins  ==================================== //
	// ===================================================================================================================== //
	protected $plugin_FILE;
	protected $plugin_DIR;
	
	public function __construct($arg1=false){
		//dont use __FILE__ or __DIR__ here, because noone knows which file is included as this trait is global accross TT's plugins
		$this->plugin_FILE	= (new \ReflectionClass(__CLASS__))->getFileName();
		$this->plugin_DIR	= dirname($this->plugin_FILE);
		$this->plugin_DIR_URL= plugin_dir_url($this->plugin_FILE);
		include_once(ABSPATH . "wp-admin/includes/plugin.php");  
		//setup initial variables
		if(method_exists($this, 'declare_static_settings')) { $this->declare_static_settings(); }				
		else { 
			$this->static_settings=array('show_opts'=>false, 'required_role'=>'manage_options', 'managed_from'=>'multisite', 'allowed_on'=>'both'); 
			$this->user_initial_options= array();
		}
		$this->my_plugin_vars();																		//setup 2nd initial variables
		$this->plugin_slug	=  sanitize_key($this->static_settings['TextDomain']);						//define short slug
		$this->opts= $this->refresh_options();															//setup final variables
		$this->__construct_my();																		//all other custom construction hooks
		$this->plugin_page_url= ( $this->opts['managed_from_primary_site'] ? network_admin_url( 'settings.php') : admin_url( 'options-general.php') ). '?page='.$this->plugin_slug; 					//determine which page needed
		//==== my other default hooks ===//
		// If plugin has options
		if($this->opts['show_opts']) { 
			//add admin menu
			add_action( ( $this->opts['managed_from_primary_site'] ? 'network_' : ''). 'admin_menu', function(){ add_submenu_page($this->opts['menu_pagePHP'], $this->opts['Name'], $this->opts['Name'], $this->opts['required_role'] , $this->plugin_slug,  array($this, 'opts_page_output') );} ); 
			//redirect to settings page after activation (if not bulk activation)
			add_action('activated_plugin', function($plugin) { if ( $plugin == plugin_basename( $this->plugin_FILE ) && !((new WP_Plugins_List_Table())->current_action()=='activate-selected')) { exit( wp_redirect($this->plugin_page_url.'&isactivation') ); } } ); 
		}
		// add Settings & Donate buttons in plugins list
		add_filter( (is_network_admin() ? 'network_admin_' : ''). 'plugin_action_links_'.plugin_basename($this->plugin_FILE),  function($links){
			$links[] = '<a href="'.$this->opts['donate_url'].'">'.$this->opts['menu_text']['donate'].'</a>'; 
			if($this->opts['show_opts']) { 	$links[] = "<a href='".$this->plugin_page_url."'>".$this->opts['menu_text']['settings'].'</a>';  }
			return $links; 
		});
		//translation hook
		add_action('plugins_loaded', array($this, 'load_textdomain') );
		//activation & deactivation (empty hooks by default. all important things migrated into `refresh_options`)
		register_activation_hook( $this->plugin_FILE, array($this, 'activate')   );
		register_deactivation_hook( $this->plugin_FILE, array($this, 'deactivate'));
		if(is_admin()) add_action( 'shutdown', array($this, 'my_shutdown_for_versioning'));
	}

	public function activate($network_wide){
		//if activation allowed from only on multisite or singlesite or Both?
		$die= $this->opts['allowed_on'] == 'both' ?  false :  (   ($this->opts['allowed_on'] =='multisite' && !$network_wide && is_multisite()) || ( $this->opts['allowed_on'] =='singlesite' && ($network_wide || is_network_admin()) )  ) ;
		if($die) {
			$text= '<h2>('.$this->opts['Name'].') '. $this->opts['menu_text']['activated_only_from']. ' <b style="color:red;">'.strtoupper($this->opts['allowed_on']).'</b> WordPress </h2>';
			die('<script>alert("'.strip_tags($text).'");</script>'.$text);
			return false;
		}
		if(method_exists($this, 'activation_funcs') ) {   $this->activation_funcs();  }
	}
	public function deactivate($network_wide){
		if(method_exists($this, 'deactivation_funcs') ) {   $this->deactivation_funcs();  }
	}

	public function my_plugin_vars(){  
		//add my default values 
		$this->static_settings = $this->static_settings    +    get_plugin_data( $this->plugin_FILE) +  
			array(
				'menu_text'			=> array(
					'donate'				=>__('Donate', 'wp-debug-from-dashboard'),
					'settings'				=>__('Settings', 'wp-debug-from-dashboard'),
					'open_settings'			=>__('You can access settings from dashboard of:', 'wp-debug-from-dashboard'),
					'activated_only_from'	=>__('Plugin activated only from', 'wp-debug-from-dashboard')
				),
				'donate_url'		=> 'http://paypal.me/tazotodua',
				'musthave_plugins'	=> 'https://www.protectpages.com/blog/must-have-wordpress-plugins/'
			)
		;
		$this->static_settings['managed_from_primary_site']	= $this->static_settings['managed_from']=='multisite' && is_multisite();
		$this->static_settings['menu_pagePHP']				= $this->static_settings['managed_from_primary_site'] ? 'settings.php' : 'options-general.php';
	}
	//load translation
	public function load_textdomain(){
		load_plugin_textdomain( $this->plugin_slug, false, basename($this->plugin_DIR). '/lang/' );  		
	}
	
	//get latest options (in case there were updated,refresh them)
	public function refresh_options(){
		$this->opts	= $this->get_option_CHOSEN($this->plugin_slug, array()); 
		foreach($this->user_initial_options as $name=>$value){ if (!array_key_exists($name, $this->opts)) {$this->opts[$name]=$value;  $should_update=true; }  }
		if(isset($should_update)) {	$this->update_option_CHOSEN($this->plugin_slug, $this->opts);	} 
		$this->opts = array_merge($this->opts, $this->static_settings);
		return $this->opts;
	}	
	
	// quick method to update this plugin's opts
	public function update_opts($opts=false){
		$this->update_option_CHOSEN($this->plugin_slug, ( $opts ? $opts : $this->opts) );
	}
	
	public function get_option_CHOSEN($optname,$default=false){
		return ( $this->static_settings['managed_from_primary_site'] ? get_site_option($optname,$default) :  get_option($optname,$default) );
	}
	public function update_option_CHOSEN($optname,$optvalue,$autoload=null){
		return ( $this->static_settings['managed_from_primary_site'] ? update_site_option($optname,$optvalue,$autoload) :  update_option($optname,$optvalue,$autoload) );
	}
	
	public function settings_page_part($type){ 
		if($type=="start"){ ?>
			<div class="clear"></div>
			<div class="myplugin postbox wrap">
				<h2><?php _e('Plugin Settings Page!', 'my_default_methods_T_Todua');?></h2>
			<?php
		}
		elseif($type=="end"){ ?>
				<div class="newBlock additionals">
					<h4></h4> 
					<h3><?php _e('More Actions', 'my_default_methods_T_Todua');?></h3>	
					<ul>
						<li>
						<p class="about-description"><?php printf(__('You can check other useful plugins at: <a href="%s">Must have free plugins for everyone</a>', 'my_default_methods_T_Todua'),  $this->opts['musthave_plugins'] );  ?> </p>
						</li>
					</ul>
					<ul>
						<li><div class="welcome-icon welcome-widgets-menus"><?php printf(__('If you found this plugin useful, <a href="%s" target="_blank">donations</a> welcomed.', 'my_default_methods_T_Todua'), $this->opts['donate_url']);?></div></li>
					</ul>
				</div>
				<style>
				.myplugin { max-width:100%; display:flex; flex-wrap:rap; justify-content:center; flex-direction:column; padding: 20px; }
				.myplugin >h2 {text-align:center;}
				.myplugin h3 {text-align:center;} 
				.myplugin table tr { border-bottom: 1px solid #cacaca; }
				.myplugin table td {min-width:160px;}
				.myplugin p.submit {text-align: center;}
				zz.myplugin input[type="text"]{width:100%;}
				.additionals{ text-align:center; margin:5px;  padding: 5px; background: #efeab7;     padding: 5px 0 0 20px;}
				.additionals a{font-weight:bold;font-size:1.1em; color:blue;}  
				</style>
				<div class="clear"></div>
			</div>
		<?php
		}
	}
	
	public function my_shutdown_for_versioning(){
		if($this->opts['last_version'] != $this->opts['Version']){
			$this->opts['last_version'] = $this->opts['Version'];
			$this->update_option_CHOSEN($this->plugin_slug, $this->opts);
		}
	}
	// ================================================   ##end of default block##  ============================================= //
	// ========================================================================================================================== //
}
?>