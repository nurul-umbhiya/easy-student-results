<?php
/**
 * Jon_Autoloader
 *
 * Autoloads class by VendorName_Package_SubFolder_Filename format
 *
 * @author  Jon Falcon <darkutubuki143@gmail.com>
 * @version 0.1.8
 */
class RPS_Autoloader_LoaderClass {
	/**
	 * Base path
	 * @var string
	 */
	private $basePath;

	/**
	 * Default Namespace(s)
	 * @var string|array
	 */
	private $namespaces;

	/**
	 * A short abbreviation for DIRECTORY_SEPARATOR
	 */
	const DS = DIRECTORY_SEPARATOR;

	/**
	 * A short abbreviation for PATH_SEPARATOR
	 */
	const PS = PATH_SEPARATOR;

	/**
	 * Initialize the object and its properties
	 * @param Optional $basePath 	Base Path to the library
	 */
	public function __construct( $namespaces = 'RPS', $basePath = null ) {
		if( $basePath ) {
			$this->setBasePath( $basePath );
		} else {
			$basePath = dirname( __FILE__ );
		}

		$this->setNamespaces( $namespaces );
	}

	/**
	 * Set the base path
	 * @param string $path 		Path to the library
	 * @return Object $this   	Supports Chaining
	 */
	public function setBasePath( $path ) {
		$this->basePath = $path;

		return $this;
	}

	/**
	 * Set the base namespaces
	 * @param string|array $namespaces 		List of namespaces
	 * @return Object $this  						Supports chaining
	 */
	public function setNamespaces( $namespaces ) {
		if( !is_array( $namespaces ) ) {
			$namespaces = array( $namespaces );
		}
		$this->namespaces = $namespaces;

		return $this;
	}

	/**
	 * Register a namespace to lessen the processing time
	 * @param string $namespace 	Add a namespace
	 * @return Object $this  		Supports chaining
	 */
	public function addNamespace( $namespace ) {
		$this->namespaces[] = $namespace;

		return $this;
	}

	/**
	 * Adds a path to the system's include path
	 * @param string $path 		Path to the library
	 * @return Object $this  	Supports chaining
	 */
	public function addPath( $path ) {
		set_include_path( get_include_path() . self::PS . $path );

		return $this;
	}

	/**
	 * Batch include files
	 * @param  array  $files 	List of files to be included
	 * @return Object $this  						Supports chaining
	 */
	public function includeFiles( array $files ) {
		foreach( $files as $file ) {
			include( $file );
		}

		return $this;
	}

	/**
	 * Register the autoloader to the SPL Autoload
	 * @param  boolean $prepend 	Do you want to prepend the function on the stack
	 * @return Object $this  		Supports chaining
	 */
	public function register( $prepend = false ) {
		// regirster autoloading method
		//if (function_exists('__autoload') and ! in_array('__autoload', spl_autoload_functions())) { // make sure old way of autoloading classes is not broken
			//spl_autoload_register('__autoload');
		//}
		spl_autoload_register( array( $this, 'loadClass' ), $throwException = false );

		return $this;
	}

	/**
	 * Include the missing class
	 * @param  string $className 	Name of the class
	 */
	public function loadClass( $className ) {
		$namespacesRegex = implode( "_|", $this->namespaces );
		$regex           = "/^{$namespacesRegex}.*/";

		if( preg_match( $regex, $className ) && ( $file = $this->findClass( $className ) ) ) {
			include( $file );

			return true;
		}
	}

	/**
	 * Finds the class if it exists
	 * @param  string $className 		Name of the class
	 * @return string|boolean           Returns the file path if it exist. Otherwise return false.
	 */	
	public function findClass( $className ) {
		$filePath = $this->basePath . self::DS . str_replace( "_", self::DS, $className ) . ".php";

		if( file_exists( $filePath ) ) {
			return $filePath;
		}

		return false;
	}
}