<?php

namespace Parfaitement;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class FormBase {
	public $core;
	public $name;
	public static $formName;

	public function __construct( Core $core ) {
		$this->core     = $core;
		$this->name     = class_basename( $this );
		self::$formName = $this->name;

		// Loading eventual setup class
		$setup_class = get_class( $this ) . 'Setup';
		if ( class_exists( $setup_class ) ) {
			$setup_class::handle();
		}

		add_action( 'admin_post_nopriv_' . $this->name, [ $this, 'handle' ] );
		add_action( 'admin_post_' . $this->name, [ $this, 'handle' ] );
	}

	/**
	 * Get data to be validated from the request.
	 *
	 * @return array
	 */
	public function validationData() {
		return $this->core->request->input();
	}

	static public function name() {
		return self::$formName;
	}

	/**
	 * Get custom messages for validator errors.
	 *
	 * @return array
	 */
	public function messages() {
		return [];
	}

	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array
	 */
	public function attributes() {
		return [];
	}

	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array
	 */
	public function rules() {
		return [];
	}

	/**
	 * Actions to take when form failed
	 *
	 */
	public function fail() {
		return wp_redirect( wp_get_referer() );
	}

	/**
	 * Actions to take when form was sucessfully submitted
	 *
	 */
	public function success() {
		return wp_redirect( site_url() );
	}

	function handle() {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], $this->name ) ) {
			return $this->fail();
		}

		$validator = $this->core->validation->make(
			$this->validationData(),
			$this->rules(),
			$this->messages(),
			$this->attributes()
		);

		if ( $validator->fails() ) {
			$_SESSION['errors'] = $validator->errors();
			$_SESSION['old']    = $this->core->request->input();

			return $this->fail();
		}

		if ( method_exists( $this, 'beforeSuccess' ) ) {
			return $this->beforeSuccess( $validator ) ? $this->success() : $this->fail();
		}

		return $this->success();
	}

	static public function errors() {
		if ( isset( $_SESSION['errors'] ) ) {
			$errors = $_SESSION['errors'];
			unset( $_SESSION['errors'] );

			return $errors;
		}

		return new ViewErrorBag();
	}

	static public function old( $key, $default = '' ) {
		return $_SESSION['old'][ $key ] ?: $default;
	}
}