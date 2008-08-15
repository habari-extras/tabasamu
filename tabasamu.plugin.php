<?php

/**
 * Tabasamu smilies plugin for Habari.
 *
 * @package tabasamu
 */

require_once 'tabasamuformat.php';

class Tabasamu extends Plugin
{
	const VERSION= '0.8';
	const OPTION_NAME= 'tabasamu__package';
	const PACKAGE_FILE= 'smilies.xml';
	const DEFAULT_PACKAGE= 'phoenity';

	private $package;
	private $search= array();
	private $replace= array();

	/**
	 * Returns information about this plugin
	 *
	 * @return array Plugin info array
	 **/
	public function info()
	{
		return array (
			'name' => 'Tabasamu',
			'url' => 'http://drunkenmonkey.org/projects/tabasamu',
			'author' => 'Drunken Monkey Labs',
			'authorurl' => 'http://drunkenmonkey.org',
			'version' => self::VERSION,
			'description' => 'Selectable smilies for Habari.',
			'license' => 'Apache License 2.0',
		);
	}

	/**
	 * Set the default packages on plugin activation.
	 */
	public function action_plugin_activation( $file )
	{
		if ( $file == $this->get_file() ) {
			if ( ! Options::get( self::OPTION_NAME ) ) {
				Options::set( self::OPTION_NAME, self::DEFAULT_PACKAGE );
			}
		}
	}

	/**
	 * Apply the default Formatters on init.
	 */
	public function action_init()
	{
		Format::apply( 'tabasamu', 'post_content_out' );
		Format::apply( 'tabasamu', 'comment_content_out' );
	}

	/**
	 * Loads the Tabasamu package into memory and builds the
	 * search/replacement arrays for smiley replacements.
	 *
	 */
	private function load_smilies()
	{
		$package_name= Options::get( self::OPTION_NAME );
		$config_file= $this->get_path( $package_name ) . '/' . self::PACKAGE_FILE;
		if ( file_exists( $config_file ) ) {
			$this->package= simplexml_load_file( $config_file );
			foreach( $this->package->smiley as $smiley ) {
				$this->search[]= strval( $smiley->text );
				$this->replace[]= $this->img( $smiley, $package_name );
			}
		}
	}

	/**
	 * Replaces text smilies with image counterpart.
	 */
	public function filter_tabasamu_smilies( $content )
	{
		if ( ( ! $this->search ) || ( ! $this->replace ) ) {
			$this->load_smilies();
		}
		$out= '';
		$texts= preg_split( "/(<.*>)/U", $content, -1, PREG_SPLIT_DELIM_CAPTURE );
		foreach ( $texts as $text ) {
			if ( ( strlen($text) > 0 ) && ( $text{0} != '<' ) ) {
				$text= str_replace( $this->search, $this->replace, $text );
			}
			$out.= $text;
		}
		return $out;
	}

	/**
	 * Outputs the options form on the plugin page.
	 */
	public function action_plugin_ui( $plugin_id, $action )
	{
		if ( $plugin_id == $this->plugin_id() ) {
			$form= new FormUI( 'tabasamu' );
			$control= $form->append('select', 'control', self::OPTION_NAME, _t( 'The active Tabasamu smilies.' ) );
			foreach( $this->get_all_packages() as $package_name => $package ) {
				$control->options[$package_name]= $package->info->name . ' ' . $package->info->version;
			}
			$control->add_validator( 'validate_required' );
			$form->append( 'submit', 'save', _t( 'Save' ) );
			$form->out();
		}
	}

	/**
	 * Outputs the "configure" button on the plugin page.
	 */
	public function filter_plugin_config( $actions, $plugin_id ) {
		if ( $plugin_id == $this->plugin_id() ) {
			return array( _t('Choose Smilies') );
		}
		return $actions;
	}

	/**
	 * get the url to the smilies folder for given package name.
	 *
	 * @param string $package_name the smilies package name
	 */
	private function get_smiley_url( $package_name= NULL )
	{
		return $this->get_url() . '/' . $package_name;
	}

	/**
	 * get the absolute path to the smilies folder for given package name.
	 *
	 * @param string $package_name the smilies package name
	 */
	private function get_path( $package_name= NULL )
	{
		return dirname( $this->get_file() ) . '/' . $package_name;
	}

	/**
	 * Gets an array of all tabasamu packages that are available.
	 *
	 * @return array An array of package objects.
	 **/
	public function get_all_packages()
	{
		$packages= array();
		foreach ( glob( $this->get_path() . '*/' . self::PACKAGE_FILE ) as $file ) {
			$package_name= basename( dirname( $file ) );
			$packages[$package_name]= simplexml_load_file( $file );
		}
		return $packages;
	}

	/**
	 * Returns an html img tag for the given smiley / image. Attributes
	 * from the smilies config file are merged with the defaults. To set,
	 * for example, width you would do: <code>&lt;smiley width="20px"&gt;</code>.
	 *
	 * @param obj $smiley A simpleXML smiley object.
	 * @return string A HTML img tag for the given smiley.
	 */
	private function img( $smiley, $package_name )
	{
		$url= $this->get_smiley_url( $package_name );
		$atts= array(
			'class' => 'habari-smiley',
			'alt' => $smiley->text,
			'src' => $url . '/' . $smiley->image,
			);
		if ( $custom_atts= (array) $smiley->attributes() ) {
			$atts= array_merge( $atts, $custom_atts['@attributes'] );
		}
		foreach ( $atts as $k => $v ) {
			$atts[$k]= sprintf( "%s=\"%s\"", $k, htmlentities( trim( strval($v) ) ) );
		}
		return ' <img ' . implode( ' ', $atts ) . ' /> ';
	}

}

?>