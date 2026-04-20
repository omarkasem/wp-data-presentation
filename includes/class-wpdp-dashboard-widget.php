<?php
/**
 * Dashboard widget for Data Presentations.
 *
 * @since 1.0.0
 */
final class WPDP_Dashboard_Widget {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Dashboard_Widget
     */
    protected static $_instance = null;

    /**
     * Get instance of the class.
     *
     * @since 1.0.0
     *
     * @return WPDP_Dashboard_Widget
     */
    public static function get_instance() {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
    }

    /**
     * Register the dashboard widget.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_widget() {
        wp_add_dashboard_widget(
            'wpdp_data_presentations_widget',
            __( 'Data Presentation Updates', 'wp-data-presentation' ),
            array( $this, 'render_widget' )
        );
    }

    /**
     * Render dashboard widget content.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function render_widget() {
        $presentations = get_posts(
            array(
                'post_type'      => 'wp-data-presentation',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        if ( empty( $presentations ) ) {
            echo '<p>' . esc_html__( 'No data presentations found.', 'wp-data-presentation' ) . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Presentation', 'wp-data-presentation' ) . '</th>';
        echo '<th>' . esc_html__( 'Last Updated', 'wp-data-presentation' ) . '</th>';
        echo '<th>' . esc_html__( 'Actions', 'wp-data-presentation' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $presentations as $presentation ) {
            $post_id         = $presentation->ID;
            $local_copy_url  = get_post_meta( $post_id, 'wpdp_last_file_url', true );
            $acled_copy_url  = $this->get_acled_copy_url( $post_id );
            $last_updated    = $this->get_last_updated_text( $post_id );

            echo '<tr>';
            echo '<td><strong>' . esc_html( get_the_title( $post_id ) ) . '</strong></td>';
            echo '<td>' . esc_html( $last_updated ) . '</td>';
            echo '<td>';

            if ( ! empty( $local_copy_url ) ) {
                echo '<a href="' . esc_url( $local_copy_url ) . '" class="button button-small button-primary" target="_blank" rel="noopener noreferrer">';
                echo esc_html__( 'Local Copy', 'wp-data-presentation' );
                echo '</a> ';
            }

            if ( ! empty( $acled_copy_url ) ) {
                echo '<a href="' . esc_url( $acled_copy_url ) . '" class="button button-small" target="_blank" rel="noopener noreferrer">';
                echo esc_html__( 'ACLED Copy', 'wp-data-presentation' );
                echo '</a>';
            }

            if ( empty( $local_copy_url ) && empty( $acled_copy_url ) ) {
                echo '&mdash;';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Get a formatted last updated value for the dashboard.
     *
     * @param int $post_id Presentation post ID.
     * @return string
     */
    private function get_last_updated_text( $post_id ) {
        $last_updated = (int) get_post_meta( $post_id, 'wpdp_last_updated_date', true );

        if ( empty( $last_updated ) ) {
            return __( 'Not updated yet', 'wp-data-presentation' );
        }

        return date_i18n( 'd-m-Y H:i:s', $last_updated );
    }

    /**
     * Build ACLED copy URL with event date query.
     *
     * @param int $post_id Presentation post ID.
     * @return string
     */
    private function get_acled_copy_url( $post_id ) {
        if ( 'Acled URL' !== get_field( 'import_file', $post_id ) ) {
            return '';
        }

        $acled_url = get_field( 'acled_url', $post_id );
        if ( empty( $acled_url ) ) {
            return '';
        }

        $event_date = date( 'Y-m-d', strtotime( '-1 year' ) );
        $acled_url  = remove_query_arg( 'event_date_where', $acled_url );
        $acled_url  = add_query_arg( 'event_date', $event_date, $acled_url );
        $acled_url  = add_query_arg( 'event_date_where', '>', $acled_url );

        return $acled_url;
    }
}

WPDP_Dashboard_Widget::get_instance();
