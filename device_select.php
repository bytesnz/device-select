<?php

/*
  Plugin Name: Device Select
  Plugin URI: http://github.com/weldstudio/device-select
  Description: Adds the ability to have desktop, touchscreen and mobile friendly versions of the site that the user can easily choose between.
  Author: The Weld Studio
  Author URI: http://www.theweldstudio.com
  Version: 0.1
 */

if (!class_exists('DeviceSelect')) {
	class DeviceSelect extends WP_Widget {
		protected static $options;
		protected $cookie = 'device_select_type';

		function __construct() {
			parent::__construct('device_select',
					__('Device Select', 'device_select'),
					array( 'description' => __('Allows the user to switch between '
							. 'the mobile, touch-friendly or desktop version of the site',
							'device_select')));

 			$this->options = array(
				'title' => array(
						'l' => __('Widget Title', 'device_select'),
						'e' => __('Leave blank for no title', 'device_select'),
						'd' => ''
				),
				'desktop_label' =>array(
						'l' => __('Desktop-friendly Link Label', 'device_select'),
						'e' => __('Leave blank to hide link', 'device_select'),
						'd' => __('Desktop', 'device_select')
				),
				'desktop_class' =>array(
						'l' => __('Desktop-friendly Class', 'device_select'),
						'd' => 'desktop',
				),
				'touch_label' =>array(
						'l' => __('Touch-friendly Link Label', 'device_select'),
						'e' => __('Leave blank to hide link', 'device_select'),
						'd' => __('Touch', 'device_select')
				),
				'touch_class' =>array(
						'l' => __('Touch-friendly Class', 'device_select'),
						'd' => 'touch',
				),
				'mobile_label' =>array(
						'l' => __('Mobile-friendly Link Label', 'device_select'),
						'e' => __('Leave blank to hide link', 'device_select'),
						'd' => __('Mobile', 'device_select')
				),
				'mobile_class' =>array(
						'l' => __('Mobile-friendly Class', 'device_select'),
						'd' => 'mobile',
				),
				'separator' => array(
						'l' => __('Separator', 'device_select'),
						'e' => __('This will be placed between each link', 'device_select'),
						'd' => ' | ',
				)
			);
			add_filter('wp_head', array(&$this, 'print_javascript'));
			add_filter('body_class', array(&$this, 'body_classes'));
			add_filter('send_headers', array(&$this, 'send_cookie'));
		}

		/**
		 * Print the widget
		 *
		 */
		function widget( $args, $instance ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
			echo $args['before_widget'];
			
			if (!empty($title))
					echo $args['before_title'] . $title . $args['after_title'];
		
			$links = array();

			foreach(array('desktop', 'touch', 'mobile') as $l) {
				if (!empty($instance[$l . '_label'])) {
					$links[] = '<a class="' . $instance[$l . '_class'] . '" onclick="device_select.change(\'' . $instance[$l . '_class'] . '\')">' . __($instance[$l . '_label'], 'device_select') . '</a>';
				}
			}

			echo join ($instance['separator'], $links);
			
			echo $args['after_widget'];
		}
		
		/**
		 * Back-end widget form. 	
		 *  
		 * @see WP_Widget::form() 	 
		 * 	 
		 * @param array $instance Previously saved values from database. 	 
		 */ 	
		public function form( $instance ) {

			foreach ($this->options as $o => &$opt) {
				if (!isset($instance[$o]) && $opt['d']) {
					$instance[$o] = $opt['d'];
				}
				?>
				<p> 	
					<label for="<?php echo $this->get_field_id($o); ?>"><?php echo $opt['l']; ?></label> 	
					<?php echo (isset($opt['e']) ? '<span>' . $opt['e'] . '</span>' : ''); ?>
					<input class="widefat" id="<?php echo $this->get_field_id($o); ?>" name="<?php echo $this->get_field_name($o ); ?>" type="text" value="<?php echo esc_attr($instance[$o]); ?>"> 	
				</p> 	
			<?php
			}
		} 
		
		/** 
		 * Sanitize widget form values as they are saved. 
		 * 
		 * @see WP_Widget::update() 	
		 *
		 * @param array $new_instance Values just sent to be saved. 	
		 * @param array $old_instance Previously saved values from database. 	
		 * 	
		 * @return array Updated safe values to be saved.
		 */ 
		public function update( $new_instance, $old_instance) {
			$instance = array();
			foreach ($this->options as $o => $opt) {
				#$instance[$o] = (!empty($new_instance[$o]) ? strip_tags($new_instance[$o]) : '');
				if (!empty($new_instance[$o])) {
					$instance[$o] = strip_tags($new_instance[$o]);
				} else {
					$instance[$o] = '';
				}
			}
			return $instance; 
		}

		/**
		 */
		function body_classes($classes) {
			if (is_active_widget(false, false, $this->id_base, true)) {
				$class = null;
				/* Check if forcing they way the page is displayed */
				if (isset($_COOKIE[$this->cookie])) {
					$class = $_COOKIE[$this->cookie];
				}

				if (is_null($class)) {
					/* Set the settings cookie so we don't have to continuously check type of
					 * browser.
					 */
					$class = $this->get_device_class();
				}

				if (!is_null($class)) {
					$classes[] = $class;
				}
			}
			return $classes;
		}

		protected function &_getSettings() {
			if (!isset($this->_settings)) {
				$this->_settings = $this->get_settings();
				$this->_settings = $this->_settings[$this->number];
			}
			
			return $this->_settings;
		}

		function print_javascript() {
			print '<script>' . $this->generate_javascript()
					. '</script>';
		}

		protected function generate_javascript($settings = null) {
			if (is_null($settings)) {
				$settings = $this->_getSettings();
			}

			$classes = array();

			if (!empty($settings['touch_label'])) {
				$classes[] = $settings['touch_class'];
			}
			if (!empty($settings['mobile_label'])) {
				$classes[] = $settings['mobile_class'];
			}
			if (!empty($settings['desktop_class'])) {
				$classes[] = $settings['desktop_class'];
			}
			
			return "
device_select = {
	classes: ['" . join("', '", $classes) . "'],
	change: function(style) {
		if (jQuery.inArray(style, this.classes) != -1) {
			for (s in this.classes) {
				if(jQuery('body').hasClass(this.classes[s]))
					jQuery('body').removeClass(this.classes[s]);
			}
			jQuery('body').addClass(style);
			document.cookie = '" . $this->cookie . "=' + style;
			
		}
	}
};
";

}

		function send_cookie() {
			if (is_active_widget(false, false, $this->id_base, true)) {
				if (isset($_COOKIE[$this->cookie])) {
					$settings = $this->_getSettings();

					if (in_array($_COOKIE[$this->cookie], array($settings['desktop_class'], $settings['touch_class'], $settings['mobile_class']))) {
						return;
					}
				}
				/* Set the settings cookie so we don't have to continuously check type of
				 * browser.
				 */
				$class = $this->get_device_class();

				setcookie($this->cookie, $class);
				$_COOKIE[$this->cookie] = $class;
			}
		}

		/**
		 * @todo Add ability to detect request desktop version
		 */
		function get_device_class() {
			$settings = $this->_getSettings();

			require_once('lib/Mobile-Detect/Mobile_Detect.php');
			if (class_exists(Mobile_Detect)) {
				$detect = new Mobile_Detect;
				/* Determine what type of browser we have */
				if ($detect->isTablet()) {
					if (!empty($settings['touch_label'])) {
						return $settings['touch_class'];
					}
				}

				if ($detect->isMobile()) {
					if (!empty($settings['mobile_label'])) {
						return $settings['mobile_class'];
					}
				}
				
				if (!empty($settings['desktop_class'])) {
					return $settings['desktop_class'];
				}
			}
			
			return null;
		}

	}

	function device_select_register_widget() {
		register_widget('DeviceSelect');
	}

	add_action('widgets_init', 'device_select_register_widget');

}
?>
