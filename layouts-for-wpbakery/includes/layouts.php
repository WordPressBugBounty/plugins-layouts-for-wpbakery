<?php
/**
 * Renders the "Layouts" admin screen: template browsing/import UI.
 *
 * Included from Layouts_For_WPBakery::lfw_layouts_page().
 *
 * @package Layouts_For_WPBakery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="lfw-body">
	<div class="lfw-header">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Layouts for WPBakery', 'layouts-for-wpbakery' ); ?></h1>
	</div>
	<div id="lfw-wrap" class="lfw-wrap">
		<div class="lfw-header">
			<div class="lfw-title lfw-is-inline"><h2 class="lfw-title"><?php esc_html_e( 'WPBakery Template Kits:', 'layouts-for-wpbakery' ); ?></h2></div>
			<div class="lfw-sync lfw-is-inline">
				<a href="javascript:void(0);" class="lfw-sync-btn"><?php esc_html_e( 'Sync Now', 'layouts-for-wpbakery' ); ?></a>
			</div>
		</div>
		<?php
		$layouts_for_wpbakery_categories = Layouts_WPB_Remote::lfw_get_instance()->categories_list();

		if ( is_array( $layouts_for_wpbakery_categories ) && ! empty( $layouts_for_wpbakery_categories['category'] ) ) {
			?>
			<div class="collection-bar">
				<h4><?php esc_html_e( 'Browse by Industry', 'layouts-for-wpbakery' ); ?></h4>
				<ul class="collection-list">
					<li><a class="lfw-category-filter active" data-filter="all" href="javascript:void(0)"><?php esc_html_e( 'All', 'layouts-for-wpbakery' ); ?></a></li>
					<?php foreach ( $layouts_for_wpbakery_categories['category'] as $layouts_for_wpbakery_cat ) { ?>
						<li><a href="javascript:void(0);" class="lfw-category-filter" data-filter="<?php echo esc_attr( $layouts_for_wpbakery_cat['slug'] ); ?>"><?php echo esc_html( html_entity_decode( $layouts_for_wpbakery_cat['title'], ENT_QUOTES, 'UTF-8' ) ); ?></a></li>
					<?php } ?>
				</ul>
			</div>
			<?php
		}
		?>

		<div class="lfw_wrapper">
			<?php
			$layouts_for_wpbakery_data = Layouts_WPB_Remote::lfw_get_instance()->templates_list();

			if ( is_array( $layouts_for_wpbakery_data ) && ! empty( $layouts_for_wpbakery_data['templates'] ) ) {
				foreach ( $layouts_for_wpbakery_data['templates'] as $layouts_for_wpbakery_i => $layouts_for_wpbakery_val ) {
					$layouts_for_wpbakery_classes = '';
					foreach ( (array) $layouts_for_wpbakery_val['category'] as $layouts_for_wpbakery_cval ) {
						$layouts_for_wpbakery_classes .= sanitize_title( $layouts_for_wpbakery_cval ) . ' ';
					}
					$layouts_for_wpbakery_title   = html_entity_decode( $layouts_for_wpbakery_val['title'], ENT_QUOTES, 'UTF-8' );
					$layouts_for_wpbakery_premium = ! empty( $layouts_for_wpbakery_val['is_premium'] );
					?>
					<div class="lfw_box lfw_filter <?php echo esc_attr( $layouts_for_wpbakery_classes ); ?>">
						<div class="lfw_box_widget">
							<div class="lfw-media">
								<img src="<?php echo esc_url( $layouts_for_wpbakery_val['thumbnail'] ); ?>" alt="<?php echo esc_attr( $layouts_for_wpbakery_title ); ?>">
								<?php if ( $layouts_for_wpbakery_premium ) { ?>
									<span class="pro-btn"><?php esc_html_e( 'PRO', 'layouts-for-wpbakery' ); ?></span>
								<?php } else { ?>
									<span class="free-btn"><?php esc_html_e( 'FREE', 'layouts-for-wpbakery' ); ?></span>
								<?php } ?>
							</div>
							<div class="lfw-template-title"><?php echo esc_html( $layouts_for_wpbakery_title ); ?></div>
							<div class="lfw-btn">
								<a href="javascript:void(0);" data-url="<?php echo esc_url( $layouts_for_wpbakery_val['url'] ); ?>" title="<?php esc_attr_e( 'Preview', 'layouts-for-wpbakery' ); ?>" class="btn pre-btn previewbtn"><?php esc_html_e( 'Preview', 'layouts-for-wpbakery' ); ?></a>
								<a href="javascript:void(0);" title="<?php esc_attr_e( 'Install', 'layouts-for-wpbakery' ); ?>" class="btn ins-btn installbtn"><?php esc_html_e( 'Install', 'layouts-for-wpbakery' ); ?></a>
							</div>
						</div>
					</div>

					<!-- Preview popup div start -->
					<div class="lfw-preview-popup" id="preview-in-<?php echo (int) $layouts_for_wpbakery_i; ?>">
						<div class="lfw-preview-container">
							<div class="lfw-preview-header">
								<div class="lfw-preview-title"><?php echo esc_html( $layouts_for_wpbakery_title ); ?></div>
								<?php if ( $layouts_for_wpbakery_premium ) { ?>
									<div class="lfw-buy">
										<p class="lfw-buy-msg"><?php esc_html_e( 'This template is premium version', 'layouts-for-wpbakery' ); ?></p>
										<span class="lfw-buy-loader"></span>

										<a href="javascript:void(0);" class="btn ins-btn lfw-buy-btn" disabled data-template-id="<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>"><?php esc_html_e( 'Buy Now', 'layouts-for-wpbakery' ); ?></a>
										<a href="javascript:void(0);" class="btn ins-btn lfw-buy-template" style="display:none" target="_blank"><?php esc_html_e( 'Edit Template', 'layouts-for-wpbakery' ); ?></a>
									</div>
								<?php } else { ?>
									<div class="lfw-import">
										<p class="lfw-msg"><?php esc_html_e( 'Import this template via one click', 'layouts-for-wpbakery' ); ?></p>
										<span class="lfw-loader"></span>

										<a href="javascript:void(0);" class="btn ins-btn lfw-import-btn" disabled data-template-id="<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>"><?php esc_html_e( 'Import Template', 'layouts-for-wpbakery' ); ?></a>
										<a href="javascript:void(0);" class="btn ins-btn lfw-edit-template" style="display:none" target="_blank"><?php esc_html_e( 'Edit Template', 'layouts-for-wpbakery' ); ?></a>
									</div>

									<span><?php esc_html_e( 'OR', 'layouts-for-wpbakery' ); ?></span>

									<div class="lfw-import lfw-page-create">
										<p><?php esc_html_e( 'Create a new page from this template', 'layouts-for-wpbakery' ); ?></p>
										<input type="text" class="lfw-page-name-<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>" placeholder="<?php esc_attr_e( 'Enter a Page Name', 'layouts-for-wpbakery' ); ?>" />
										<a href="javascript:void(0);" class="btn ins-btn lfw-create-page-btn" data-template-id="<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>"><?php esc_html_e( 'Create New Page', 'layouts-for-wpbakery' ); ?></a>
									</div>

									<span class="lfw-loader-page"></span>

									<div class="lfw-import lfw-page-edit" style="display:none">
										<p><?php esc_html_e( 'Your template is successfully imported!', 'layouts-for-wpbakery' ); ?></p>
										<a href="javascript:void(0);" class="btn ins-btn lfw-edit-page" target="_blank"><?php esc_html_e( 'Edit Page', 'layouts-for-wpbakery' ); ?></a>
									</div>
									<div class="lfw-import lfw-page-error" style="display:none">
										<p class="lfw-error"><?php esc_html_e( 'Something went wrong!', 'layouts-for-wpbakery' ); ?></p>
									</div>
								<?php } ?>
								<span class="lfw-close-icon"></span>

								<a href="<?php echo esc_url( $layouts_for_wpbakery_val['url'] ); ?>" class="lfw-dashicons-link" title="<?php esc_attr_e( 'Open Preview in New Tab', 'layouts-for-wpbakery' ); ?>" rel="noopener noreferrer" target="_blank">
									<span class="lfw-dashicons"></span>
								</a>
							</div>
							<iframe width="100%" height="100%" src=""></iframe>
						</div>
					</div>
					<!-- Preview popup div end -->

					<!-- Install popup div start -->
					<div class="lfw-install-popup" id="content-in-<?php echo (int) $layouts_for_wpbakery_i; ?>">
						<div class="lfw-container">
							<div class="lfw-install-header">
								<div class="lfw-install-title"><?php echo esc_html( $layouts_for_wpbakery_title ); ?></div>
								<span class="lfw-close-icon"></span>
							</div>
							<div class="lfw-install-content">

								<?php if ( $layouts_for_wpbakery_premium ) { ?>
									<p class="lfw-msg"><?php esc_html_e( 'This template is premium version', 'layouts-for-wpbakery' ); ?></p>
									<div class="lfw-btn">
										<span class="lfw-loader"></span>
										<a href="javascript:void(0);" class="btn ins-btn lfw-buy-btn" data-template-id="<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>"><?php esc_html_e( 'Buy Now', 'layouts-for-wpbakery' ); ?></a>
										<a href="javascript:void(0);" class="btn ins-btn lfw-buy-template" style="display:none" target="_blank"><?php esc_html_e( 'Edit Template', 'layouts-for-wpbakery' ); ?></a>
									</div>

								<?php } else { ?>

									<p class="lfw-msg"><?php esc_html_e( 'Import this template via one click', 'layouts-for-wpbakery' ); ?></p>
									<div class="lfw-btn">
										<span class="lfw-loader"></span>
										<a href="javascript:void(0);" class="btn ins-btn lfw-import-btn" data-template-id="<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>"><?php esc_html_e( 'Import Template', 'layouts-for-wpbakery' ); ?></a>
										<a href="javascript:void(0);" class="btn ins-btn lfw-edit-template" style="display:none" target="_blank"><?php esc_html_e( 'Edit Template', 'layouts-for-wpbakery' ); ?></a>
									</div>

									<p class="lfw-horizontal"><?php esc_html_e( 'OR', 'layouts-for-wpbakery' ); ?></p>

									<div class="lfw-page-create">
										<p><?php esc_html_e( 'Create a new page from this template', 'layouts-for-wpbakery' ); ?></p>
										<input type="text" class="lfw-page-<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>" placeholder="<?php esc_attr_e( 'Enter a Page Name', 'layouts-for-wpbakery' ); ?>" />
										<div class="lfw-btn">
											<a href="javascript:void(0);" style="padding: 0;" class="btn pre-btn lfw-create-page-btn" data-name="crtbtn" data-template-id="<?php echo esc_attr( $layouts_for_wpbakery_val['id'] ); ?>"><?php esc_html_e( 'Create New Page', 'layouts-for-wpbakery' ); ?></a>
											<span class="lfw-loader-page"></span>
										</div>
									</div>
									<div class="lfw-create-div lfw-page-edit" style="display:none">
										<p style="color: #000;"><?php esc_html_e( 'Your page is successfully imported!', 'layouts-for-wpbakery' ); ?></p>
										<div class="lfw-btn">
											<a href="javascript:void(0);" class="btn pre-btn lfw-edit-page" target="_blank"><?php esc_html_e( 'Edit Page', 'layouts-for-wpbakery' ); ?></a>
										</div>
									</div>
									<div class="lfw-import lfw-page-error" style="display:none;">
										<p class="lfw-error" style="color: #444;"><?php esc_html_e( 'Something went wrong!', 'layouts-for-wpbakery' ); ?></p>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<!-- Install popup div end -->
					<?php
				}
			} else {
				echo '<p>' . esc_html( is_string( $layouts_for_wpbakery_data ) ? $layouts_for_wpbakery_data : __( 'Unable to load the layouts library. Please try again later.', 'layouts-for-wpbakery' ) ) . '</p>';
			}
			?>
		</div>
	</div>
</div>
