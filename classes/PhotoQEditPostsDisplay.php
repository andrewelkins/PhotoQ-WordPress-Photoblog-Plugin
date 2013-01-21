<?php
class PhotoQEditPostsDisplay implements PhotoQHookable
{
	
	private $_thumbDimension;
	
	
	public function __construct(PhotoQ_Photo_Dimension $thumbDimension){
		$this->_thumbDimension = $thumbDimension;
	}
	
	/**
	 * To hook the appropriate callback functions 
	 * (action hooks) into WordPress Plugin API.
	 */
	public function hookIntoWordPress(){
		add_filter('manage_posts_columns', 
			array($this, 'filterAddThumbToListOfPosts'));
		add_action('manage_posts_custom_column', 
			array($this, 'actionInsertThumbIntoListOfPosts'), 10, 2);
	}
		
	
	/**
	 * This is a filter hooked into the manage_posts_columns WordPress hook. It adds a new column
	 * header for the thumbnail column to the column headers of the manage post list.
	 *
	 * @param string $content	the list of column headers.
	 *
	 * @returns string          the list of column headers including the new column.
	 * @access public
	 */
	public function filterAddThumbToListOfPosts($content)
	{
		$result = array();
		foreach( $content as $key => $value){
			//add thumb column before the title column
			if($key == "title")
				$result["PhotoQPhoto"] = "Photo";
			
			$result[$key] = $value;
			//add actions after date column
			if( $key == "date"  && current_user_can( 'access_photoq' ) )
				$result["photoQActions"] = "PhotoQ Actions";
	
		}
		return $result;
	}
	
	
	/**
	 * This is an action hooked into the manage_posts_custom_column WordPress hook. It displays an
	 * additional column in the manage post list containing the thumbnail for photo posts.
	 *
	 * @param string $content     The name of the column to be displayed.
	 * @param string $postID	  The id of the post for which we want to show the photo
	 * @access public
	 */
	public function actionInsertThumbIntoListOfPosts($colName, $postID){
		if($colName == "PhotoQPhoto"){
			if(PhotoQHelper::isPhotoPost($postID)){
				$db = PhotoQ_DB_DB::getInstance();
				$photo = $db->getPublishedPhoto($postID);
				echo $photo->getAdminThumbImgTag($this->_thumbDimension);
			}else
				echo "No Photo";
		}
		if($colName == "photoQActions"){
			if(PhotoQHelper::isPhotoPost($postID)){
				$manageMenu = new PhotoQ_Util_ManageAdminMenuLocation();
				$rebuildLink = $manageMenu->getPageName() . '&action=rebuild&id='.$postID;
				$rebuildLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($rebuildLink, 'photoq-rebuildPost' . $postID) : $rebuildLink;
				echo '<a href="'.$rebuildLink.'" title="Rebuild this photo and its post content.">Rebuild</a>';
			}
		}
	}
}