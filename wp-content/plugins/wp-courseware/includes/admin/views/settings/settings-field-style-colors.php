<?php
/**
 * Style Colors Settings Field Component.
 *
 * @since 4.3.0
 */
?>
<script type="text/x-template" id="wpcw-settings-field-style-colors">
	<?php $colors = wpcw()->styles->get_colors(); ?>
    <div class="wpcw-settings-page-style">
		<?php if ( $colors ) { ?>
            <div class="postbox">
                <h2 class="hndle">
					<?php esc_html_e( 'Colors', 'wp-courseware' ); ?>
                    <a class="button button-secondary button-small wpcw-reset-colors" @click.prevent="resetColors">
                        <i class="wpcw-fa wpcw-fa-retweet left" aria-hidden="true"></i>
						<?php esc_html_e( 'Reset Colors', 'wp-courseware' ); ?>
                    </a>
                    <a target="_blank" href="<?php echo esc_url_raw( add_query_arg( array( 'return' => urlencode( $this->get_current_tab_url() ) ), admin_url( 'customize.php' ) ) ); ?>"
                       class="button button-primary button-small">
                        <i class="wpcw-fa wpcw-fa-magic left" aria-hidden="true"></i>
						<?php esc_html_e( 'Use the Customizer', 'wp-courseware' ); ?>
                    </a>
                </h2>

                <div class="inside">
                    <div class="wpcw-vtabs">
                        <ul class="wpcw-vtab-nav">
							<?php foreach ( $colors as $id => $color ) { ?>
                                <li class="wpcw-vtab-title" :class="activeClass( '<?php echo esc_attr( $id ); ?>' )">
                                    <a href="#" @click.prevent="updateTab( '<?php echo esc_attr( $id ); ?>' )">
										<?php echo ( ! empty( $color['label'] ) ) ? esc_html( $color['label'] ) : esc_html__( 'Colors', 'wp-courseware' ); ?>
                                    </a>
                                </li>
							<?php } ?>
                        </ul>

                        <div class="wpcw-vtab-wrap">
							<?php foreach ( $colors as $id => $color ) { ?>
                                <div v-show="activeTab( '<?php echo esc_attr( $id ); ?>' )" id="wpcw-vtab-general" class="wpcw-vtab-content">
                                    <h3 class="title"><?php echo ( ! empty( $color['label'] ) ) ? esc_html( $color['label'] ) : esc_html__( 'Colors', 'wp-courseware' ); ?></h3>

									<?php if ( ! empty( $color['desc'] ) ) { ?>
                                        <p><?php esc_html( $color['desc'] ); ?></p>
									<?php } ?>

									<?php if ( ! empty( $color['settings'] ) ) { ?>
										<?php foreach ( $color['settings'] as $setting => $args ) {
											$default = ( ! empty( $args['default'] ) ) ? esc_attr( $args['default'] ) : '';
											?>
                                            <wpcw-form-field>
                                                <label for="<?php echo $setting; ?>"><?php echo esc_html( $args['label'] ); ?></label>
                                                <input type="text"
                                                       id="<?php echo $setting; ?>-field"
                                                       value="<?php echo $this->get_setting( $setting ); ?>"
                                                       class="wpcw-color-picker"
                                                       name="<?php echo $setting; ?>"
                                                       data-setting="<?php echo $setting; ?>"
                                                       data-default-color="<?php echo $default; ?>"/>
												<?php if ( ! empty( $args['desc'] ) ) { ?>
                                                    <span class="desc"><?php echo esc_html( $args['desc'] ); ?></span>
												<?php } ?>
                                            </wpcw-form-field>
										<?php } ?>
									<?php } ?>
                                </div>
							<?php } ?>
                        </div>
                    </div>
                </div>
            </div>
		<?php } ?>
    </div>
</script>