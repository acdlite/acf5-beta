<?php 

/*
*  acf_get_valid_options_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_get_valid_options_page( $page = '' ) {
	
	// allow for string
	if( is_string($page) ) {
	
		$page_title = $page;
		
		$page = array(
			'page_title' => $page_title,
			'menu_title' => $page_title
		);
	}
	
	
	// defaults
	$page = acf_parse_args($page, array(
		'page_title' 	=> '',
		'menu_title'	=> '',
		'menu_slug' 	=> '',
		'capability'	=> 'edit_posts',
		'parent_slug'	=> '',
		'position'		=> false,
		'icon_url'		=> false,
		'redirect'		=> ''
	));
	
	
	// ACF4 compatibility
	$migrate = array(
		'title' 	=> 'page_title',
		'menu'		=> 'menu_title',
		'slug'		=> 'menu_slug',
		'parent'	=> 'parent_slug'
	);
	
	foreach( $migrate as $old => $new ) {
		
		if( !empty($page[ $old ]) ) {
			
			$page[ $new ] = acf_extract_var( $page, $old );
			
		}
		
	}
	
	
	// slug
	if( empty($page['menu_slug']) ) {
	
		$page['menu_slug'] = 'acf-options-' . sanitize_title( $page['menu_title'] );
		
	}
	
	
	// return
	return $page;
	
}


/*
*  acf_pro_get_option_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_get_options_page( $slug ) {
	
	// bail early if page doens't exist
	if( empty($GLOBALS['acf_options_pages'][ $slug ]) ) {
		
		return false;
		
	}
	
	
	// vars
	$page = $GLOBALS['acf_options_pages'][ $slug ];
	
	
	// filter for 3rd party customization
	$page = apply_filters('acf/get_options_page', $page, $slug);
	
	
	// return
	return $page;
	
}


/*
*  acf_pro_get_option_pages
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_get_options_pages() {
	
	// bail early if empty
	if( empty($GLOBALS['acf_options_pages']) ) {
		
		return false;
		
	}
	
	
	// vars
	$pages = array();
	$redirect = array();
	$slugs = array_keys($GLOBALS['acf_options_pages']);
	
	
	// get pages
	foreach( $slugs as $slug ) {
		
		$pages[] = acf_get_options_page( $slug );
		
	}
	
	
	// get redirects
	if( !empty($pages) ) {
		
		foreach( $pages as $page ) {
			
			// append redirect
			if( !empty($page['redirect']) ) {
				
				$redirect[ $page['menu_slug'] ] = $page['redirect'];
				
			}
			
		}
		
	}
	
	
	// loop through $pages and update redirect slugs
	if( !empty($redirect) ) {
		
		foreach( $pages as $k => $page ) {
			
			if( !empty($page['parent_slug']) ) {
				
				if( array_key_exists($page['parent_slug'], $redirect) ) {
					
					$pages[ $k ]['parent_slug'] = $redirect[ $page['parent_slug'] ];
					
				}
				
			} else {
				
				if( array_key_exists($page['menu_slug'], $redirect) ) {
					
					$pages[ $k ]['menu_slug'] = $redirect[ $page['menu_slug'] ];
					
				}
				
			}
			
		}
		
	}
		
	
	// return
	return $pages;
	
}


/*
*  acf_update_options_page
*
*  description
*
*  @type	function
*  @date	1/05/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

function acf_update_options_page( $data ) {
	
	// bail early if no menu_slug
	if( empty($data['menu_slug']) ) {
		
		return false;
		
	}
	
	// vars
	$slug = $data['menu_slug'];
	
	
	// bail early if no page found
	if( empty($GLOBALS['acf_options_pages'][ $slug ]) ) {
	
		return false;
		
	}
	
	
	// vars
	$page = $GLOBALS['acf_options_pages'][ $slug ];
	
	
	// merge in data
	$page = array_merge($page, $data);
	
	
	// update
	$GLOBALS['acf_options_pages'][ $slug ] = $page;
	
	
	// return
	return $page;
	
}


/*
*  acf_add_options_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

if( ! function_exists('acf_add_options_page') ):

function acf_add_options_page( $page = '' ) {
	
	// validate
	$page = acf_get_valid_options_page( $page );
	
	
	// instantiate globals
	if( empty($GLOBALS['acf_options_pages']) ) {
	
		$GLOBALS['acf_options_pages'] = array();
		
	}
	
	
	// update if already exists
	if( acf_get_options_page($page['menu_slug']) ) {
		
		return acf_update_options_page( $page );
		
	}
	
	
	// append
	$GLOBALS['acf_options_pages'][ $page['menu_slug'] ] = $page;
	
	
	// return
	return $page;
	
}

endif;


/*
*  acf_add_options_page
*
*  description
*
*  @type	function
*  @date	24/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$post_id (int)
*/

if( ! function_exists('acf_add_options_sub_page') ):

function acf_add_options_sub_page( $page = '' ) {
	
	// validate
	$page = acf_get_valid_options_page( $page );
	
	
	// parent
	if( empty($page['parent_slug']) ) {
		
		// set parent slug
		$page['parent_slug'] = 'acf-options';
		
		
		// get parent
		$parent = acf_get_options_page($page['parent_slug']);
		
		
		// redirect parent to child
		if( empty($parent['redirect']) ) {
			
			// update parent
			$parent = acf_update_options_page(array(
				'menu_slug'	=> $page['parent_slug'],
				'redirect'	=> $page['menu_slug']
			));
				
		}
		
	}
	
	
	// return
	return acf_add_options_page( $page );
	
}

endif;


/*
*  acf_set_options_page_title
*
*  This function is used to customize the options page admin menu title
*
*  @type	function
*  @date	13/07/13
*  @since	4.0.0
*
*  @param	$title (string)
*  @return	n/a
*/

if( ! function_exists('acf_set_options_page_title') ):

function acf_set_options_page_title( $title = 'Options' ) {
	
	acf_update_options_page(array(
		'menu_slug'		=> 'acf-options',
		'page_title'	=> $title
	));
	
}

endif;


/*
*  acf_set_options_page_menu
*
*  This function is used to customize the options page admin menu name
*
*  @type	function
*  @date	13/07/13
*  @since	4.0.0
*
*  @param	$title (string)
*  @return	n/a
*/

if( ! function_exists('acf_set_options_page_menu') ):

function acf_set_options_page_menu( $title = 'Options' ) {
	
	acf_update_options_page(array(
		'menu_slug'		=> 'acf-options',
		'menu_title'	=> $title
	));
	
}

endif;


/*
*  acf_set_options_page_capability
*
*  This function is used to customize the options page capability. Defaults to 'edit_posts'
*
*  @type	function
*  @date	13/07/13
*  @since	4.0.0
*
*  @param	$title (string)
*  @return	n/a
*/

if( ! function_exists('acf_set_options_page_capability') ):

function acf_set_options_page_capability( $capability = 'edit_posts' ) {
	
	acf_update_options_page(array(
		'menu_slug'		=> 'acf-options',
		'capability'	=> $capability
	));
	
}

endif;


/*
*  register_options_page()
*
*  This is an old function which is now referencing the new 'acf_add_options_sub_page' function
*
*  @type	function
*  @since	3.0.0
*  @date	29/01/13
*
*  @param	{string}	$title
*  @return	N/A
*/

if( ! function_exists('register_options_page') ):

function register_options_page( $title = false ) {

	acf_add_options_sub_page( $title );
	
}

endif;

?>