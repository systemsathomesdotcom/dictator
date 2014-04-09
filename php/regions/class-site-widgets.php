<?php

namespace Dictator\Regions;

class Site_Widgets extends Region {

	protected $schema = array(
		'_type'         => 'prototype',
		'_get_callback' => 'get_sidebars',
		'_prototype'    => array(
			'_type'           => 'prototype',
			'_get_callback'   => 'get_sidebar_widgets',
			'_prototype'      => array(
				'_type'          => 'array',
				'_get_callback'  => 'get_widget_data',
				),
			)
		);

	/**
	 * Object-level cache for sidebar data
	 */
	protected $sidebars;

	/**
	 * Get the difference between the state file and WordPress
	 *
	 * @return array
	 */
	public function get_differences() {

		$this->differences = array();
		foreach( $this->get_imposed_data() as $sidebar_id => $widgets ) {

			$result = $this->get_sidebar_difference( $sidebar_id, $widgets );

			if ( ! empty( $result ) ) {
				$this->differences[ $sidebar_id ] = $result;
			}

		}

		return $this->differences;

	}

	/**
	 * Get the sidebars and their widget data on the site
	 *
	 * @return array
	 */
	protected function get_sidebars() {
		global $wp_registered_sidebars;

		if ( ! array_key_exists( 'wp_inactive_widgets', $wp_registered_sidebars ) ) {
			$this->register_unused_sidebar();
		}

		$this->sidebars = array();
		$sidebars_widgets = get_option( 'sidebars_widgets', array() );
		foreach( $wp_registered_sidebars as $sidebar_id => $sidebar_data ) {

			$widget_data = array();
			foreach( $sidebars_widgets[ $sidebar_id ] as $widget_id ) {

				$parts = explode( '-', $widget_id );
				$option_index = array_pop( $parts );
				$name = implode( '-', $parts );

				$options = get_option( 'widget_' . $name );

				$widget_data[ $widget_id ] = $options[ $option_index ];
			}

			$this->sidebars[ $sidebar_id ] = $widget_data;
		}

		return array_keys( $this->sidebars );
	}

	/**
	 * Get the widget data associated with a given sidebar
	 *
	 * @return array
	 */
	protected function get_sidebar_widgets() {

		$sidebar_id = $this->current_schema_attribute_parents[0];

		return array_keys( $this->sidebars[ $sidebar_id ] );
	}

	/**
	 * Get the data for a given widget
	 *
	 * @return mixed
	 */
	protected function get_widget_data() {

		$sidebar_id = $this->current_schema_attribute_parents[0];
		$widget_id = $this->current_schema_attribute_parents[1];
		return $this->sidebars[ $sidebar_id ][ $widget_id ];
	}

	/**
	 * Impose some state data onto a region
	 *
	 * @param string $key
	 * @param array $value
	 * @return true|WP_Error
	 */
	public function impose( $key, $value ) {


		return true;

	}

	/**
	 * Get the difference between the declared sidebar and the actual sidebar
	 *
	 * @param string $sidebar_id
	 * @param array $sidebar_widgets
	 * @return array|false
	 */
	protected function get_sidebar_difference( $sidebar_id, $sidebar_widgets ) {

		$result = array(
			'dictated'        => $sidebar_widgets,
			'current'         => array(),
		);

		$sidebars = $this->get_current_data();
		if ( ! isset( $sidebars[ $sidebar_id ] ) ) {
			return $result;
		}

		$result['current'] = $sidebars[ $sidebar_id ];

		if ( \Dictator::array_diff_recursive( $result['dictated'], $result['current'] ) ) {
			return $result;
		} else {
			return false;
		}

	}

	/**
	 * Register the sidebar for unused widgets
	 * Core does this in /wp-admin/widgets.php, which isn't helpful
	 */
	protected function register_unused_sidebar() {

		register_sidebar(array(
			'name' => __('Inactive Widgets'),
			'id' => 'wp_inactive_widgets',
			'class' => 'inactive-sidebar',
			'description' => __( 'Drag widgets here to remove them from the sidebar but keep their settings.' ),
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '',
			'after_title' => '',
		));

	}

}
