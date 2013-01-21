<?php
class PhotoQ_Util_Taxonomies
{
	const NAME_OF_CATEGORY_TAXONOMY = 'category';
	const NAME_OF_TAXONOMY_POST_FIELD = 'tax_input';
	
	private $_displayedTaxonomies = array();
	private $_defaultCategory;
	private $_isPostboxClosed = false;
	
	public function __construct(){
		$oc = PhotoQ_Option_OptionController::getInstance();
		$this->_displayedTaxonomies = array_merge(
			array(self::NAME_OF_CATEGORY_TAXONOMY), 
			$this->_getCustomTaxonomiesAssociatedWithType($oc->getValue('qPostType'))
		);
		$this->_defaultCategory = $oc->getValue('qPostDefaultCat');
		$this->_isPostboxClosed = $oc->getValue('foldCats');
	}
	
	
	
	private function _getCustomTaxonomiesAssociatedWithType($type){
		return array_intersect(self::getCustomTaxonomies(), get_object_taxonomies($type));
	}
	
	/**
	 * Show taxonomy form fields for this $postID.
	 * @param unknown_type $postID
	 * @return unknown_type
	 */
	public function showTaxForms($postID = 0) {	
		echo '<div class="taxlists">';	
		foreach($this->_displayedTaxonomies as $taxonomy){
			$this->_showForm($postID, $taxonomy);
		}
		echo '</div>';
	}
	
	/**
	 * Display taxonomy form fields. Copied in parts from
	 * meta-boxes.php post_categories_meta_box() since this built-in
	 * function didn't allow for easy reuse.
	 */
	private function _showForm($postID, $taxonomy){
		$tax = get_taxonomy($taxonomy);
		
		//check the fold option
		$closed = $this->_isPostboxClosed ? 'closed' : '';

		?>
		<div id="<?php echo $taxonomy.'div-'.$postID; ?>" class="postbox <?php echo $closed?>"><?php //  ' . postbox_classes($box['id'], $page) . $hidden_class . '" ' . '>' ?>
			<div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br /></div>
			<h3 class='hndle'><span><?php echo $tax->labels->name; ?></span></h3>
			
			<div class="inside">
				<div id="taxonomy-<?php echo $taxonomy.'-'.$postID; ?>" class="categorydiv">
					<!-- <ul id="<?php echo $taxonomy.'-'.$postID; ?>-tabs" class="category-tabs">
						<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
						<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
					</ul> -->
			
					<div id="<?php echo $taxonomy.'-'.$postID; ?>-pop" class="tabs-panel" style="display: none;">
						<ul id="<?php echo $taxonomy.'-'.$postID; ?>checklist-pop" class="categorychecklist form-no-clear" >
							<?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
						</ul>
					</div>
			
					<div id="<?php echo $taxonomy.'-'.$postID; ?>-all" class="tabs-panel">
						<?php
			            $name = self::NAME_OF_TAXONOMY_POST_FIELD . '[' . $taxonomy . ']';
			            
			            echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
			            ?>
						<ul id="<?php echo $taxonomy.'-'.$postID; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
							<?php 
								wp_terms_checklist($postID, 
									array( 	'taxonomy' => $taxonomy, 
											'popular_cats' => $popular_ids,
											'selected_cats' => $this->_getSelectedTerms($postID, $taxonomy),
											'walker' => new PhotoQ_Util_CategoryArrayWalker($postID) 
									) 
								) 
							?>
						</ul>
					</div>
					
					
				<!--<?php /*if ( !current_user_can($tax->cap->assign_terms) ) : ?>
				<p><em><?php _e('You cannot modify this taxonomy.'); ?></em></p>
				<?php endif; ?>
				<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
						<div id="<?php echo $taxonomy.'-'.$postID; ?>-adder" class="wp-hidden-children">
							<h4>
								<a id="<?php echo $taxonomy.'-'.$postID; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
									<?php
										
										printf( __( '+ %s' ), $tax->labels->add_new_item );
									?>
								</a>
							</h4>
							<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
								<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
								<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
								<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
									<?php echo $tax->labels->parent_item_colon; ?>
								</label>
								<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
								<input type="button" id="<?php echo $taxonomy.'-'.$postID; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
								<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
								<span id="<?php echo $taxonomy.'-'.$postID; ?>-ajax-response"></span>
							</p>
						</div>
					<?php endif; */?>
				--></div>
			</div>
		</div>
		<?php
	}
	
	public function updatePostTaxonomies($postID){
		foreach($this->_displayedTaxonomies as $taxonomy){
			$selectedTerms = $this->_getSelectedTermsFromPostVariableOrCommonInfo($taxonomy, $postID);
			if($this->_isCategory($taxonomy)){
				wp_set_post_categories($postID, $selectedTerms);
			}else{
				wp_set_post_terms($postID, $selectedTerms, $taxonomy);
			}
		}
	}
	
	private function _isCategory($taxonomy){
		return $taxonomy == self::NAME_OF_CATEGORY_TAXONOMY;
	}
	
	private function _getSelectedTerms($postID, $taxonomy){
		$selected = $this->_getTermsFromCommonInfo($taxonomy);
		if(empty($selected))
			$selected = wp_get_object_terms($postID, $taxonomy, array('fields' => 'ids'));
		return $this->_appendDefaultCatIfNeeded($selected, $taxonomy);
	}
	
	private function _appendDefaultCatIfNeeded($selected, $taxonomy){
		if (!$selected && $this->_isCategory($taxonomy)) 
			$selected[] = $this->_defaultCategory;
		return $selected;
	}
	
	private function _getSelectedTermsFromPostVariableOrCommonInfo($taxonomy, $postID){
		$selected = $this->_getTermsFromCommonInfo($taxonomy);
		if(empty($selected))
			$selected = $this->_getTermsFromPostVariable($taxonomy, $postID);
		return $this->_appendDefaultCatIfNeeded($selected, $taxonomy);
	}
	
	private function _getTermsFromCommonInfo($taxonomy){
		return $this->_getTermsFromPostVariable($taxonomy);
	}
	
	private function _getTermsFromPostVariable($taxonomy, $postID = 0){
		$terms = PhotoQHelper::arrayAttributeEscape(
			$_POST[self::NAME_OF_TAXONOMY_POST_FIELD][$taxonomy][$postID]
		);
		return $terms ? $terms : array();
	}
	
	public static function getCustomTaxonomies(){
		return get_taxonomies(array('_builtin' => false));
	}

}