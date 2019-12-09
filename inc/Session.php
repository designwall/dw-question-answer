<?php

function dwqa_add_notice( $message, $type = 'success', $comment = false ) {
	global $dwqa;
	$dwqa->session->add( $message, $type, $comment );
}

function dwqa_clear_notices() {
	global $dwqa;
	$dwqa->session->clear();
}

add_action( 'dwqa_before_edit_form', 'dwqa_print_notices' );
add_action( 'dwqa_before_question_submit_form', 'dwqa_print_notices' );
function dwqa_print_notices( $comment = false ) {
	global $dwqa;
	echo $dwqa->session->print_notices( $comment );
}

function dwqa_count_notices( $type = '', $comment = false ) {
	global $dwqa;
	return $dwqa->session->count( $type, $comment );
}

function dwqa_add_wp_error_message( $errors, $comment = false ) {
	if ( is_wp_error( $errors ) ) {
		dwqa_add_notice( $errors->get_error_message(), 'error', $comment );
	}
}

class DWQA_Session {
	protected $_data = array();
	protected $_dirty = false;

	public function __get( $key ) {
		return $this->get( $key );
	}

	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	public function get( $key, $default = '' ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty = true;
		}
	}

	public function add( $message, $type = 'success', $comment = false ) {
		if ( ! did_action( 'init' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before init.', 'dw-question-answer' ), '1.4.0' );
			return;
		}

		global $dwqa;

		$key = $comment ? 'dwqa-comment-notices' : 'dwqa-notices';

		$notices = $this->get( $key, array() );

		$notices[ $type ][] = $message;

		$this->set( $key, $notices );
	}

	public function clear() {
		if ( ! did_action( 'init' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before init.', 'dw-question-answer' ), '1.4.0' );
			return;
		}

		global $dwqa;
		$this->set( 'dwqa-notices', null );
	}

	public function print_notices( $comment = false ) {
		if ( ! did_action( 'init' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before init.', 'dw-question-answer' ), '1.4.0' );
			return;
		}

		global $dwqa;

		$key = $comment ? 'dwqa-comment-notices' : 'dwqa-notices';
		$notices = $this->get( $key, array() );
		$types = array( 'error', 'success', 'info' );

		foreach( $types as $type ) {
			if ( $this->count( $type, $comment ) > 0 ) {
				foreach( $notices[ $type ] as $message ) {
					return sprintf( '<p class="dwqa-alert dwqa-alert-%s">%s</p>', $type, $message );
				}
			}
		}

		dwqa_clear_notices();
	}

	public function count( $type = '', $comment = false ) {
		if ( ! did_action( 'init' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before init.', 'dw-question-answer' ), '1.4.0' );
			return;
		}

		$key = $comment ? 'dwqa-comment-notices' : 'dwqa-notices';
		$all_notices = $this->get( $key, array() );
		$count = 0;
		if ( isset( $all_notices[ $type ] ) ) {
			$count = absint( sizeof( $all_notices[ $type ] ) );
		} elseif ( empty( $type ) ) {
			foreach( $all_notices as $notices ) {
				$count += absint( sizeof( $notices ) );
			}
		}

		return $count;
	}
}