<script type="text/x-template" id="wpcw-settings-field-products">
    <div class="wpcw-support-field-products">
        <h2><?php esc_html_e( 'Other Products', 'wp-courseware' ); ?></h2>
        <p><?php _e( 'As a current customer you are entitled to <code>15% off</code> any one of our plugins.', 'wp-courseware' ); ?></p>
        <p><?php esc_html_e( 'Simply click one of the buy buttons below, then select your license level, and your discount will appear upon checkout.', 'wp-courseware' ); ?></p>
        <div class="wpcw-products-wrapper">
            <div v-if="loading" class="loading-products">
                <span class="spinner is-active left"></span>
				<?php esc_html_e( 'Loading Products...', 'wp-courseware' ); ?>
            </div>
            <div class="wpcw-products">
                <div v-for="(product, key, index) in products" :key="key" class="product">
                    <div v-if="product.image" class="image">
                        <a :href="product.url" target="_blank"><img :src="product.image" :alt="product.title"/></a>
                    </div>
                    <div class="content">
                        <h3 v-if="! product.discount_enabled">{{ product.title }}</h3>
                        <h3 v-if="product.discount_enabled && product.discount">
                            {{ product.title }} - <span class="discount">{{ product.discount }}</span>
                        </h3>
                        <p v-html="product.desc"></p>
                    </div>
                    <div class="actions">
                        <a class="button button-secondary" :href="product.url" :title="product.title" target="_blank">
                            <i class="wpcw-fa wpcw-fa-shopping-cart" aria-hidden="true"></i>
							<?php esc_html_e( 'Purchase', 'wp-courseware' ); ?> {{ product.title }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>