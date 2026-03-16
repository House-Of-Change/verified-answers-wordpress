<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VA_FAQ_Hooks {

    public static function init(): void {
        add_filter( 'the_content', [ __CLASS__, 'append_to_content' ], 99 );
        add_filter( 'woocommerce_product_tabs', [ __CLASS__, 'add_product_tab' ] );
        add_action( 'wp_head', [ __CLASS__, 'inject_jsonld' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_styles' ] );
    }

    public static function register_styles(): void {
        wp_register_style(
            'va-faq-style',
            VA_FAQ_URL . 'assets/css/va-faq.css',
            [],
            VA_FAQ_VERSION
        );

        // Enqueue on singular pages that have a FAQ set assigned
        if ( is_singular() ) {
            $set_id = get_post_meta( get_the_ID(), '_va_faq_set_id', true );
            if ( ! empty( $set_id ) ) {
                wp_enqueue_style( 'va-faq-style' );
            }
        }
    }

    /**
     * Append FAQ HTML after page/post content (not products — those use WooCommerce tabs).
     */
    public static function append_to_content( string $content ): string {
        if ( ! is_singular() || ! is_main_query() ) {
            return $content;
        }

        // Skip WooCommerce products — they use the tab hook instead
        if ( function_exists( 'is_product' ) && is_product() ) {
            return $content;
        }

        $set_id = get_post_meta( get_the_ID(), '_va_faq_set_id', true );
        if ( empty( $set_id ) ) {
            return $content;
        }

        $faqs = VA_FAQ_Data_Provider::get_faqs( $set_id );
        if ( empty( $faqs ) ) {
            return $content;
        }

        wp_enqueue_style( 'va-faq-style' );

        return $content . VA_FAQ_Renderer::render_html( $faqs );
    }

    /**
     * Add FAQ tab to WooCommerce product pages.
     */
    public static function add_product_tab( array $tabs ): array {
        global $product;

        if ( ! $product ) {
            return $tabs;
        }

        $set_id = get_post_meta( $product->get_id(), '_va_faq_set_id', true );
        if ( empty( $set_id ) ) {
            return $tabs;
        }

        $faqs = VA_FAQ_Data_Provider::get_faqs( $set_id );
        if ( empty( $faqs ) ) {
            return $tabs;
        }

        $tabs['verified_answers_faq'] = [
            'title'    => __( 'FAQ', 'verified-answers-faq' ),
            'priority' => 30,
            'callback' => [ __CLASS__, 'render_product_tab' ],
        ];

        return $tabs;
    }

    public static function render_product_tab(): void {
        global $product;

        if ( ! $product ) {
            return;
        }

        $set_id = get_post_meta( $product->get_id(), '_va_faq_set_id', true );
        $faqs   = VA_FAQ_Data_Provider::get_faqs( $set_id );

        echo VA_FAQ_Renderer::render_html( $faqs, [ 'heading' => 'Product FAQ', 'heading_tag' => 'h2' ] );
    }

    /**
     * Inject JSON-LD into <head> for pages with FAQs.
     */
    public static function inject_jsonld(): void {
        if ( ! is_singular() ) {
            return;
        }

        $set_id = get_post_meta( get_the_ID(), '_va_faq_set_id', true );
        if ( empty( $set_id ) ) {
            return;
        }

        $faqs = VA_FAQ_Data_Provider::get_faqs( $set_id );
        if ( empty( $faqs ) ) {
            return;
        }

        echo VA_FAQ_Renderer::render_jsonld( $faqs );
    }
}
