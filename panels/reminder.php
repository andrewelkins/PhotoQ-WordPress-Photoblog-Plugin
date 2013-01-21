<div class="updated fade">
	<p>
		<?php 
			_e('PhotoQ is free software. Still, countless of hours went into its development and made PhotoQ what it is today. Have you ever thought of giving something back?', 'PhotoQ')
		?>
	</p>
	<p>
		<?php _e('A donation is an easy way of showing the developer your appreciation.', 'PhotoQ')?>
	</p>
	<br/>
	<div class="donate">
		<?php _e('Yes, I support PhotoQ and would like to make a donation', 'PhotoQ') ?>: 
	</div>
	<div class="donate ppal">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input
			type="hidden" name="cmd" value="_s-xclick" /> <input type="hidden"
			name="hosted_button_id" value="467690" /> <input type="image"
			src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif"
			name="submit" alt="PayPal - The safer, easier way to pay online!" /> <img
			alt="PayPal - The safer, easier way to pay online!"
			src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
		</form>
	</div>
	<div class="nothanks">
		<?php 
			$manageMenu = new PhotoQ_Util_ManageAdminMenuLocation();
			$noThanksLink = $manageMenu->getPageName() . '&amp;action=nothanks';
			$noThanksLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($noThanksLink, 'photoq-noThanks') : $noThanksLink;
		?>
		<a href="<?php echo $noThanksLink ?>">
			<?php _e('No, thanks','PhotoQ') ?>
		</a>
	</div>
	<div class="nothanks">
		<?php 
			$noThanksLink = $manageMenu->getPageName() . '&amp;action=alreadydid';
			$noThanksLink = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($noThanksLink, 'photoq-noThanks') : $noThanksLink;
		?>
		<a href="<?php echo $noThanksLink ?>">
			<?php _e('I already donated','PhotoQ') ?>
		</a>
	</div>
	<br class="clr" />
</div>