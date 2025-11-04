<?php
/*
Plugin Name: WP Maintanance Mode
Plugin URI: https://github.com/ByAldon/WP-Maintanance-Mode
Description: A plugin for wordpress that shows a static Maintanance page.
Version: 0.1
Requires at least: 6.8
Requires PHP: 7.4
Author: John Oltmans
Author URI: https://www.johnoltmans.nl/
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: simple-plugin-for-personal-notes-by-john-oltmans
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_action( 'template_redirect', 'custom_maintenance_mode_check' );

function custom_maintenance_mode_check() {
    // Administrators may view the site normally.
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    // Path to images directory inside the plugin
    $images_dir = plugin_dir_path( __FILE__ ) . 'images/';

    // Web-URL to images directory
    $images_url = plugin_dir_url( __FILE__ ) . 'images/';

    // Fallback local image (place this in the plugin root or change the filename)
    $local_fallback = esc_url( plugin_dir_url( __FILE__ ) . 'geranimo-bKhETeDV1WM-unsplash.jpg' );

    $selected_image_url = $local_fallback;

    // Read all files in images directory with supported image extensions
    if ( is_dir( $images_dir ) ) {
        $files = scandir( $images_dir );
        $images = array();

        if ( is_array( $files ) ) {
            foreach ( $files as $file ) {
                // ignore . and ..
                if ( in_array( $file, array( '.', '..' ), true ) ) {
                    continue;
                }

                // check extension (jpg/jpeg/png/webp/gif)
                $ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
                if ( in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif' ), true ) ) {
                    $images[] = $file;
                }
            }
        }

        if ( ! empty( $images ) ) {
            // Choose a random image — new choice on every page refresh.
            // Use random_int for better randomness where available.
            try {
                $idx = random_int( 0, count( $images ) - 1 );
            } catch ( Exception $e ) {
                $idx = mt_rand( 0, count( $images ) - 1 );
            }
            $chosen = $images[ $idx ];
            // Add a tiny cache-busting query param to help ensure browsers request it anew on refresh.
            $selected_image_url = esc_url( $images_url . $chosen . '?v=' . time() . rand(1000,9999) );
        }
    }

    // Send maintenance headers (if not already sent) and prevent aggressive caching
    if ( ! headers_sent() ) {
        header( 'HTTP/1.1 503 Service Unavailable' );
        header( 'Retry-After: 3600' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
    }

    // Output HTML (English text)
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Oops — Maintenance</title>
        <style>
            html, body { height: 100%; }
            body {
                margin: 0;
                padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background-color: #f0f4f8;
                background-image: url("<?php echo esc_url( $selected_image_url ); ?>");
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                color: #ffffff;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                text-align: center;
            }
            .maintenance-container {
                background-color: rgba(0, 0, 0, 0.6);
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                max-width: 600px;
                width: 90%;
            }
            h1 { font-size: 2.5em; margin-bottom: 1rem; }
            p { font-size: 1.1em; line-height: 1.6; margin: 0; }
            .sub { margin-top: 1rem; font-size: 0.95em; opacity: 0.9; }
            @media (max-width: 480px) {
                h1 { font-size: 1.8em; }
                p  { font-size: 1em; }
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container" role="main" aria-labelledby="maintenance-heading">
            <h1 id="maintenance-heading">Oooh noooo...</h1>
            <p>Squirrels have been gnawing on the cables.<br> Therefore, this website is undergoing maintenance.</p>
        </div>
    </body>
    </html>
    <?php
    exit(); // Stop further WordPress execution
}
?>