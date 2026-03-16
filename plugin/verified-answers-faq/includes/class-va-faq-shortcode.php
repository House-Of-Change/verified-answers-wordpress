<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class VA_FAQ_Shortcode {

    public static function init(): void {
        add_shortcode( 'verified_answers_faq', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts ): string {
        $atts = shortcode_atts( [
            'set'         => '',
            'heading'     => 'Frequently Asked Questions',
            'show_source' => 'yes',
            'heading_tag' => 'h2',
        ], $atts, 'verified_answers_faq' );

        $set_id = sanitize_text_field( $atts['set'] );
        if ( empty( $set_id ) ) {
            $set_id = get_option( 'va_faq_default_set', '' );
        }

        if ( empty( $set_id ) ) {
            return '';
        }

        $faqs = VA_FAQ_Data_Provider::get_faqs( $set_id );
        if ( empty( $faqs ) ) {
            return '';
        }

        wp_enqueue_style( 'va-faq-style' );

        return VA_FAQ_Renderer::render_html( $faqs, $atts );
    }
}
