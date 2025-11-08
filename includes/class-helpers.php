<?php
function wpdp_get_actors(){
    $actors = get_field('actor_filter','option');

    if(!$actors){
        return [];
    }

    $actor_list = [];

    foreach($actors as $actor){
        $filter = $actor['filter'];
        foreach($filter as $f){
            $value = ucfirst( strtolower( $f['text'] ) );
            $value = str_replace('militias', 'militia', $value);
            $value = str_replace('groups', 'group', $value);
            $value = str_replace('Civilian targets/victims', 'Civilians', $value);
            $value = str_replace('Protestors', 'Protesters', $value);
            $actor_list[$f['actor_code'][0]] = $value;
        }
    }
    return $actor_list;
}

/**
 * Send error notification email
 * 
 * @param string $subject Email subject
 * @param string $message Optional additional message
 * @return bool Whether the email was sent successfully
 */
function wpdp_send_error_email( $subject, $message = '' ) {
    // Set your email address here
    $admin_email = 'omar.kasem207@gmail.com';
    
    // Skip if no email is configured
    if ( empty( $admin_email )  ) {
        error_log( 'WPDP: Error notification email not configured' );
        return false;
    }
    
    $site_name = get_bloginfo( 'name' );
    $site_url = get_site_url();
    
    $email_subject = sprintf( '[%s] WPDP: %s', $site_name, $subject );
    
    $email_body = "WP Data Presentation Alert\n\n";
    $email_body .= "Site: {$site_name}\n";
    $email_body .= "URL: {$site_url}\n";
    $email_body .= "Date/Time: " . current_time( 'Y-m-d H:i:s' ) . "\n\n";
    
    if ( ! empty( $message ) ) {
        $email_body .= "Message:\n{$message}\n\n";
    }
    
    $email_body .= "Please check your error logs for more details.\n";
    $email_body .= "\n---\n";
    $email_body .= "This is an automated message from WP Data Presentation plugin.\n";
    
    $sent = wp_mail( $admin_email, $email_subject, $email_body );
    
    if ( $sent ) {
        error_log( sprintf( 'WPDP: Error notification sent to %s: %s', $admin_email, $subject ) );
    } else {
        error_log( sprintf( 'WPDP: Failed to send error notification to %s', $admin_email ) );
    }
    
    return $sent;
}