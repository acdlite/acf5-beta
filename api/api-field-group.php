<?php 

/*
*  acf_is_field_group_key
*
*  This function will return true or false for the given $group_key parameter
*
*  @type	function
*  @date	6/12/2013
*  @since	5.0.0
*
*  @param	$group_key (string)
*  @return	(boolean)
*/

function acf_is_field_group_key( $group_key = '' ) {
	
	// validate type
	if( ! is_string($group_key) )
	{
		return false;
	}
	
	
	// search for 'field_'
	if( substr($group_key, 0, 6) === 'group_' )
	{
		return true;
	}
	
	
	// return
	return false;
	
}


/*
*  acf_get_valid_field_group
*
*  This function will fill in any missing keys to the $field_group array making it valid
*
*  @type	function
*  @date	28/09/13
*  @since	5.0.0
*
*  @param	$field_group (array)
*  @return	$field_group (array)
*/

function acf_get_valid_field_group( $field_group = false ) {
	
	// parse in defaults
	$field_group = acf_parse_args( $field_group, array(
		'ID'					=> 0,
		'key'					=> '',
		'title'					=> '',
		'fields'				=> array(),
		'location'				=> array(),
		'menu_order'			=> 0,
		'position'				=> 'normal',
		'style'					=> 'default',
		'label_placement'		=> 'top',
		'instruction_placement'	=> 'label',
		'hide_on_screen'		=> array()
	));
	
	
	// filter
	$field_group = apply_filters('acf/get_valid_field_group', $field_group);
	
	
	// return
	return $field_group;
}


/*
*  acf_get_field_groups
*
*  This function will return an array of field groupss for the given args. Similar to the WP get_posts function
*
*  @type	function
*  @date	30/09/13
*  @since	5.0.0
*
*  @param	$args (array)
*  @return	$field_groups (array)
*/

function acf_get_field_groups( $args = false ) {
	
	// vars
	$field_groups = array();
	
	
	// cache
	$found = false;
	$cache = wp_cache_get( 'field_groups', 'acf', false, $found );
	
	if( $found )
	{
		return acf_filter_field_groups( $cache, $args );
	}
	
	
	// load from DB
	$posts = get_posts(array(
		'post_type'					=> 'acf-field-group',
		'posts_per_page'			=> -1,
		'orderby' 					=> 'menu_order title',
		'order' 					=> 'asc',
		'suppress_filters'			=> false,
		'post_status'				=> 'publish',
		'update_post_meta_cache'	=> false
	));
	
	
	// loop through and load field groups
	if( $posts )
	{
		foreach( $posts as $post )
		{
			// add to return array
			$field_groups[] = acf_get_field_group( $post );
		}
	}
	
	
	// filter
	$field_groups = apply_filters('acf/get_field_groups', $field_groups);
	
	
	// set cache
	wp_cache_set( 'field_groups', $field_groups, 'acf' );
			
	
	// return		
	return acf_filter_field_groups( $field_groups, $args );
}


/*
*  acf_filter_field_groups
*
*  This function is used by acf_get_field_groups to filter out fields groups based on location rules
*
*  @type	function
*  @date	29/11/2013
*  @since	5.0.0
*
*  @param	$field_groups (array)
*  @param	$args (array)
*  @return	$field_groups (array)
*/

function acf_filter_field_groups( $field_groups, $args = false ) {
	
	// bail early if no options
	if( empty($args) )
	{
		return $field_groups;
	}
	
	
	if( !empty($field_groups) )
	{
		$keys = array_keys( $field_groups );
		
		foreach( $keys as $key )
		{
			$visibility = acf_get_field_group_visibility( $field_groups[ $key ], $args );
			
			if( !$visibility )
			{
				unset($field_groups[ $key ]);
			}
		}
		
		$field_groups = array_values( $field_groups );
	}
	

	return $field_groups;
	
}


/*
*  acf_get_field_group
*
*  This function will take either a post object, post ID or even null (for global $post), and
*  will then return a valid field group array
*
*  @type	function
*  @date	30/09/13
*  @since	5.0.0
*
*  @param	$selector (mixed)
*  @return	$field_group (array)
*/

function acf_get_field_group( $selector = false ) {
	
	// vars
	$field_group = false;
	$k = 'ID';
	$v = 0;
	
	
	// $post_id or $key
	if( is_numeric($selector) )
	{
		$v = $selector;
	}
	elseif( is_string($selector) )
	{
		$k = 'key';
		$v = $selector;
	}
	elseif( is_object($selector) )
	{
		$v = $selector->ID;
	}
	else
	{
		return false;
	}
	
	
	// get cache key
	$cache_key = "get_field_group/{$k}={$v}";
	
	
	// get cache
	$found = false;
	$cache = wp_cache_get( $cache_key, 'acf', false, $found );
	
	if( $found )
	{
		return $cache;
	}
	
	
	// get field group from ID or key
	if( $k == 'ID' )
	{
		$field_group = _acf_get_field_group_by_id( $v );
	}
	else
	{
		$field_group = _acf_get_field_group_by_key( $v );
	}
	
	
	// filter for 3rd party customization
	$field_group = apply_filters('acf/get_field_group', $field_group);
	
	
	// set cache
	wp_cache_set( $cache_key, $field_group, 'acf' );
	
	
	// return
	return $field_group;
}


/*
*  _acf_get_field_group_by_id
*
*  This function will get a field group by it's ID
*
*  @type	function
*  @date	27/02/2014
*  @since	5.0.0
*
*  @param	$post_id (int)
*  @return	$field_group (array)
*/

function _acf_get_field_group_by_id( $post_id = 0 ) {
	
	// vars
	$field_group = false;
	
	
	// get post
	$post = get_post( $post_id );
	
	
	// validate
	if( empty($post) )
	{
		return $field_group;	
	}
	
	
	// unserialize
	$data = maybe_unserialize( $post->post_content );
	
	
	// update $field_group
	if( is_array($data) )
	{
		$field_group = $data;
	}
	
	
	// update attributes
	$field_group['ID'] = $post->ID;
	$field_group['title'] = $post->post_title;
	$field_group['key'] = $post->post_name;
	$field_group['menu_order'] = $post->menu_order;
	
	
	// override with JSON
	if( acf_is_local_field_group( $field_group['key'] ) )
	{
		// extract some args
		$backup = acf_extract_vars($field_group, array(
			'ID',
		));
		
		
		$field_group = acf_get_local_field_group( $field_group['key'] );
		
		
		// merge in backup
		$field_group = array_merge($field_group, $backup);
		
		
	}
	
	
	// validate
	$field_group = acf_get_valid_field_group( $field_group );

	
	// return
	return $field_group;
	
}


/*
*  _acf_get_field_group_by_key
*
*  This function will get a field group by it's key
*
*  @type	function
*  @date	27/02/2014
*  @since	5.0.0
*
*  @param	$key (string)
*  @return	$field_group (array)
*/


function _acf_get_field_group_by_key( $key = '' ) {
	
	// vars
	$field_group = false;
		
	
	// try JSON before DB to save query time
	if( acf_is_local_field_group( $key ) )
	{
		$field_group = acf_get_local_field_group( $key );
		
		// validate
		$field_group = acf_get_valid_field_group( $field_group );
	
		// return
		return $field_group;
	}

	
	// vars
	$args = array(
		'posts_per_page'	=> 1,
		'post_type'			=> 'acf-field-group',
		'orderby' 			=> 'menu_order title',
		'order'				=> 'ASC',
		'suppress_filters'	=> false,
		'acf_group_key'		=> $key
	);
	
	
	// load posts
	$posts = get_posts( $args );
	
	
	// validate
	if( empty($posts[0]) )
	{
		return $field_group;	
	}
	
	
	// load from ID
	$field_group = _acf_get_field_group_by_id( $posts[0]->ID );
	
	
	// return
	return $field_group;
	
}



/*
*  acf_update_field_group
*
*  This function will update a field group into the database.
*  The returned field group will always contain an ID
*
*  @type	function
*  @date	28/09/13
*  @since	5.0.0
*
*  @param	$field_group (array)
*  @return	$field_group (array)
*/

function acf_update_field_group( $field_group = array() ) {
	
	// validate
	$field_group = acf_get_valid_field_group( $field_group );
	
	
	// may have been posted. Remove slashes
	$field_group = wp_unslash( $field_group );
	
	
	// locations may contain 'uniquid' array keys
	$field_group['location'] = array_values( $field_group['location'] );
	
	foreach( $field_group['location'] as $k => $v )
	{
		$field_group['location'][ $k ] = array_values( $v );
	}
	
	
	// store origional field group for return
	$data = $field_group;
	
	
	// extract some args
	$extract = acf_extract_vars($data, array(
		'ID',
		'key',
		'title',
		'menu_order',
		'fields',
	));
	
	
	// serialize for DB
	$data = maybe_serialize( $data );
        
    
    // save
    $save = array(
    	'ID'			=> $extract['ID'],
    	'post_status'	=> 'publish',
    	'post_type'		=> 'acf-field-group',
    	'post_title'	=> $extract['title'],
    	'post_name'		=> $extract['key'],
    	'post_excerpt'	=> sanitize_title($extract['title']),
    	'post_content'	=> $data,
    	'menu_order'	=> $extract['menu_order'],
    );
    
    
    // allow field groups to contain the same name
	add_filter( 'wp_unique_post_slug', 'acf_update_field_group_wp_unique_post_slug', 5, 6 ); 
	
    
    // update the field group and update the ID
    if( $field_group['ID'] )
    {
	    wp_update_post( $save );
    }
    else
    {
	    $field_group['ID'] = wp_insert_post( $save );
    }
	
	
	// action for 3rd party customization
	do_action('acf/update_field_group', $field_group);
	
	
    // return
    return $field_group;
	
}

function acf_update_field_group_wp_unique_post_slug( $slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug ) {
		
	if( $post_type == 'acf-field-group' ) {
	
		$slug = $original_slug;
	
	}
	
	return $slug;
}


/*
*  acf_duplicate_field_group
*
*  This function will duplicate a field group into the database
*
*  @type	function
*  @date	28/09/13
*  @since	5.0.0
*
*  @param	$selector (mixed)
*  @param	$new_post_id (int) allow specific ID to override (good for WPML translations)
*  @return	$field_group (array)
*/

function acf_duplicate_field_group( $selector = 0, $new_post_id = 0 ) {
	
	// disable JSON to avoid conflicts between DB and JSON
	acf_update_setting('local', false);
	
	
	// load the origional field gorup
	$field_group = acf_get_field_group( $selector );
	
	
	// bail early if field group did not load correctly
	if( empty($field_group) )
	{
		return false;
	}
	
	
	// keep backup of field group
	$orig_field_group = $field_group;
	
	
	// update ID
	$field_group['ID'] = $new_post_id;
	$field_group['key'] = uniqid('group_');
	$field_group['title'] .= ' (' . __("copy", 'acf') . ')';
	
	
	// save
	$field_group = acf_update_field_group( $field_group );
	
	
	// get fields
	$fields = acf_get_fields($orig_field_group);
	
	
	if( !empty($fields) )
	{
		foreach( $fields as $field )
		{
			acf_duplicate_field( $field['ID'], $field_group['ID'] );
		}
	}
	
	
	// action for 3rd party customization
	do_action('acf/duplicate_field_group', $field_group);
	
	
	// return
	return $field_group;

}


/*
*  acf_get_field_count
*
*  This function will return the number of fields for the given field group
*
*  @type	function
*  @date	17/10/13
*  @since	5.0.0
*
*  @param	$field_group_id (int)
*  @return	(int)
*/

function acf_get_field_count( $field_group_id ) {
	
	// vars
	$args = array(
		'posts_per_page'	=> -1,
		'post_type'			=> 'acf-field',
		'orderby'			=> 'menu_order',
		'order'				=> 'ASC',
		'suppress_filters'	=> true, // allows WPML to work
		'post_parent'		=> $field_group_id,
		'fields'			=> 'ids',
		'post_status'		=> 'publish, trash' // 'any' won't get trashed fields
	);
	
	
	// load fields
	$posts = get_posts( $args );
	
	
	// return
	return apply_filters('acf/get_field_count', count( $posts ), $field_group_id);
	
}

/*
*  acf_delete_field_group
*
*  This function will delete the field group and it's fields from the DB
*
*  @type	function
*  @date	5/12/2013
*  @since	5.0.0
*
*  @param	$selector (mixed)
*  @return	(boolean)
*/

function acf_delete_field_group( $selector = 0 ) {
	
	// load the origional field gorup
	$field_group = acf_get_field_group( $selector );
	
	
	// bail early if field group did not load correctly
	if( empty($field_group) )
	{
		return false;
	}
	
	
	// get fields
	$fields = acf_get_fields($field_group);
	
	
	if( !empty($fields) )
	{
		foreach( $fields as $field )
		{
			acf_delete_field( $field['ID'] );
		}
	}
	
	
	// delete
	wp_delete_post( $field_group['ID'] );
	
	
	// action for 3rd party customization
	do_action('acf/delete_field_group', $field_group);
	
	
	// return
	return true;
}


/*
*  acf_trash_field_group
*
*  This function will trash the field group and it's fields
*
*  @type	function
*  @date	5/12/2013
*  @since	5.0.0
*
*  @param	$selector (mixed)
*  @return	(boolean)
*/

function acf_trash_field_group( $selector = 0 ) {
	
	// load the origional field gorup
	$field_group = acf_get_field_group( $selector );
	
	
	// bail early if field group did not load correctly
	if( empty($field_group) )
	{
		return false;
	}
	
	
	// get fields
	$fields = acf_get_fields($field_group);
	
	
	if( !empty($fields) )
	{
		foreach( $fields as $field )
		{
			acf_trash_field( $field['ID'] );
		}
	}
	
	
	// delete
	wp_trash_post( $field_group['ID'] );
	
	
	// action for 3rd party customization
	do_action('acf/trash_field_group', $field_group);
	
	
	// return
	return true;
}


/*
*  acf_untrash_field_group
*
*  This function will restore from trash the field group and it's fields
*
*  @type	function
*  @date	5/12/2013
*  @since	5.0.0
*
*  @param	$selector (mixed)
*  @return	(boolean)
*/

function acf_untrash_field_group( $selector = 0 ) {
	
	// load the origional field gorup
	$field_group = acf_get_field_group( $selector );
	
	
	// bail early if field group did not load correctly
	if( empty($field_group) )
	{
		return false;
	}
	
	
	// get fields
	$fields = acf_get_fields($field_group);
	
	
	if( !empty($fields) )
	{
		foreach( $fields as $field )
		{
			acf_untrash_field( $field['ID'] );
		}
	}
	
	
	// delete
	wp_untrash_post( $field_group['ID'] );
	
	
	// action for 3rd party customization
	do_action('acf/untrash_field_group', $field_group);
	
	
	// return
	return true;
}



/*
*  acf_get_field_group_style
*
*  This function will render the CSS for a given field group
*
*  @type	function
*  @date	20/10/13
*  @since	5.0.0
*
*  @param	$field_group (array)
*  @return	n/a
*/

function acf_get_field_group_style( $field_group )
{
	// vars
	$e = '';
	
	
	// validate
	if( !is_array($field_group['hide_on_screen']) )
	{
		return $e;
	}
	
	
	// add style to html
	if( in_array('permalink',$field_group['hide_on_screen']) )
	{
		$e .= '#edit-slug-box {display: none;} ';
	}
	
	if( in_array('the_content',$field_group['hide_on_screen']) )
	{
		$e .= '#postdivrich {display: none;} ';
	}
	
	if( in_array('excerpt',$field_group['hide_on_screen']) )
	{
		$e .= '#postexcerpt, #screen-meta label[for=postexcerpt-hide] {display: none;} ';
	}
	
	if( in_array('custom_fields',$field_group['hide_on_screen']) )
	{
		$e .= '#postcustom, #screen-meta label[for=postcustom-hide] { display: none; } ';
	}
	
	if( in_array('discussion',$field_group['hide_on_screen']) )
	{
		$e .= '#commentstatusdiv, #screen-meta label[for=commentstatusdiv-hide] {display: none;} ';
	}
	
	if( in_array('comments',$field_group['hide_on_screen']) )
	{
		$e .= '#commentsdiv, #screen-meta label[for=commentsdiv-hide] {display: none;} ';
	}
	
	if( in_array('slug',$field_group['hide_on_screen']) )
	{
		$e .= '#slugdiv, #screen-meta label[for=slugdiv-hide] {display: none;} ';
	}
	
	if( in_array('author',$field_group['hide_on_screen']) )
	{
		$e .= '#authordiv, #screen-meta label[for=authordiv-hide] {display: none;} ';
	}
	
	if( in_array('format',$field_group['hide_on_screen']) )
	{
		$e .= '#formatdiv, #screen-meta label[for=formatdiv-hide] {display: none;} ';
	}
	
	if( in_array('featured_image',$field_group['hide_on_screen']) )
	{
		$e .= '#postimagediv, #screen-meta label[for=postimagediv-hide] {display: none;} ';
	}
	
	if( in_array('revisions',$field_group['hide_on_screen']) )
	{
		$e .= '#revisionsdiv, #screen-meta label[for=revisionsdiv-hide] {display: none;} ';
	}
	
	if( in_array('categories',$field_group['hide_on_screen']) )
	{
		$e .= '#categorydiv, #screen-meta label[for=categorydiv-hide] {display: none;} ';
	}
	
	if( in_array('tags',$field_group['hide_on_screen']) )
	{
		$e .= '#tagsdiv-post_tag, #screen-meta label[for=tagsdiv-post_tag-hide] {display: none;} ';
	}
	
	if( in_array('send-trackbacks',$field_group['hide_on_screen']) )
	{
		$e .= '#trackbacksdiv, #screen-meta label[for=trackbacksdiv-hide] {display: none;} ';
	}
	
	
	// return	
	return apply_filters('acf/get_field_group_style', $e, $field_group);
}

?>