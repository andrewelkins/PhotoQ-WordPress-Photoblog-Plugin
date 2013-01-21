<?php
/**
 * Category walker visitor object that outputs categories in array syntax such that we can
 * have multiple category dropdown lists on the same page.
 */
class PhotoQ_Util_CategoryArrayWalker extends Walker {
	
	private $_photoID;
	
	function __construct($postID)
	{
		$this->tree_type = 'category';
		$this->db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this
		$this->_photoID = $postID;
	}

	function start_lvl($output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='wimpq_subcats'>\n";
	}

	function end_lvl($output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $category, $depth, $args) {
		extract($args);
		if ( empty($taxonomy) )
			$taxonomy = PhotoQ_Util_Taxonomies::NAME_OF_CATEGORY_TAXONOMY;

		$name = PhotoQ_Util_Taxonomies::NAME_OF_TAXONOMY_POST_FIELD . '['.$taxonomy.']';

		$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
		$output .= "\n<li id='{$taxonomy}-{$category->term_id}-".$this->_photoID."'$class>" . '<label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="'.$name.'['.$this->_photoID.'][]" id="in-'.$taxonomy.'-' . $category->term_id . '-'.$this->_photoID. '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el($output, $category, $depth, $args) {
		$output .= "</li>\n";
	}
}