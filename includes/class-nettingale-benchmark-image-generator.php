<?php
/**
 * Image Generator Class
 *
 * Generates benchmark images using GD library only
 *
 * @package Nettingale_Benchmark
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Nettingale_Benchmark_Image_Generator
 */
class Nettingale_Benchmark_Image_Generator {

	/**
	 * Check if GD extension is available
	 *
	 * @return bool True if GD is available.
	 */
	public static function is_gd_available() {
		return extension_loaded( 'gd' ) && function_exists( 'gd_info' );
	}

	/**
	 * Get GD information
	 *
	 * @return array|false GD info array or false if not available.
	 */
	public static function get_gd_info() {
		if ( ! self::is_gd_available() ) {
			return false;
		}
		return gd_info();
	}

	/**
	 * Generate a single test image
	 *
	 * Creates a basic 800x600 test image to verify GD functionality
	 *
	 * @param string $output_path Optional path to save the image. If not provided, returns image resource.
	 * @return bool|resource True if saved successfully, or image resource if no path provided.
	 */
	public static function generate_test_image( $output_path = null ) {
		// Check if GD is available
		if ( ! self::is_gd_available() ) {
			return new WP_Error( 'gd_not_available', 'GD extension is not available.' );
		}

		// Create image resource
		$width  = 800;
		$height = 600;
		$image  = imagecreatetruecolor( $width, $height );

		if ( ! $image ) {
			return new WP_Error( 'image_create_failed', 'Failed to create image resource.' );
		}

		// Allocate colors
		$bg_color   = imagecolorallocate( $image, 44, 62, 80 ); // #2C3E50
		$text_color = imagecolorallocate( $image, 236, 240, 241 ); // #ECF0F1

		if ( $bg_color === false || $text_color === false ) {
			imagedestroy( $image );
			return new WP_Error( 'color_allocate_failed', 'Failed to allocate colors.' );
		}

		// Fill background
		imagefilledrectangle( $image, 0, 0, $width, $height, $bg_color );

		// Add text overlay
		$text = 'Nettingale Benchmark - Test Image';
		$font = 5; // Built-in font (largest)
		$text_width  = imagefontwidth( $font ) * strlen( $text );
		$text_height = imagefontheight( $font );
		$x = ( $width - $text_width ) / 2;
		$y = ( $height - $text_height ) / 2;

		imagestring( $image, $font, (int) $x, (int) $y, $text, $text_color );

		// Save or return
		if ( $output_path ) {
			// Ensure directory exists
			$dir = dirname( $output_path );
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}

			// Save as JPEG
			$result = imagejpeg( $image, $output_path, 85 );
			imagedestroy( $image );

			if ( ! $result ) {
				return new WP_Error( 'image_save_failed', 'Failed to save image.' );
			}

			return true;
		}

		return $image;
	}

	/**
	 * Get upload directory for benchmark images
	 *
	 * @return array Upload directory info with 'path' and 'url' keys.
	 */
	public static function get_upload_dir() {
		$upload = wp_upload_dir();
		$base_dir = $upload['basedir'] . '/nettingale-benchmark';
		$base_url = $upload['baseurl'] . '/nettingale-benchmark';

		// Create directory if it doesn't exist
		if ( ! file_exists( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
		}

		return array(
			'path' => $base_dir,
			'url'  => $base_url,
		);
	}

	/**
	 * Generate filename for benchmark image
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $size_type Size type (featured, content, thumbnail).
	 * @return string Filename.
	 */
	public static function get_image_filename( $post_id, $size_type ) {
		return sprintf( 'nettingale_bench_%d_%s.jpg', $post_id, $size_type );
	}

	/**
	 * Get full path for benchmark image
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $size_type Size type.
	 * @return string Full file path.
	 */
	public static function get_image_path( $post_id, $size_type ) {
		$upload_dir = self::get_upload_dir();
		$filename   = self::get_image_filename( $post_id, $size_type );
		return $upload_dir['path'] . '/' . $filename;
	}

	/**
	 * Get full URL for benchmark image
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $size_type Size type.
	 * @return string Full URL.
	 */
	public static function get_image_url( $post_id, $size_type ) {
		$upload_dir = self::get_upload_dir();
		$filename   = self::get_image_filename( $post_id, $size_type );
		return $upload_dir['url'] . '/' . $filename;
	}

	/**
	 * Generate image with specific color scheme
	 *
	 * @param int    $post_id    Post ID.
	 * @param int    $width      Image width.
	 * @param int    $height     Image height.
	 * @param int    $quality    JPEG quality (0-100).
	 * @param string $size_type  Size type (featured, content, thumbnail).
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function generate_image( $post_id, $width, $height, $quality = 85, $size_type = 'content' ) {
		// Check if GD is available
		if ( ! self::is_gd_available() ) {
			return new WP_Error( 'gd_not_available', 'GD extension is not available.' );
		}

		// Get color scheme based on post ID (deterministic)
		$color_scheme = Nettingale_Benchmark_Config::get_color_scheme( $post_id % 10 );

		// Convert hex colors to RGB
		$bg_rgb   = Nettingale_Benchmark_Config::hex_to_rgb( $color_scheme['bg'] );
		$text_rgb = Nettingale_Benchmark_Config::hex_to_rgb( $color_scheme['text'] );

		// Create image resource
		$image = imagecreatetruecolor( $width, $height );

		if ( ! $image ) {
			return new WP_Error( 'image_create_failed', 'Failed to create image resource.' );
		}

		// Allocate colors
		$bg_color   = imagecolorallocate( $image, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
		$text_color = imagecolorallocate( $image, $text_rgb[0], $text_rgb[1], $text_rgb[2] );

		if ( $bg_color === false || $text_color === false ) {
			imagedestroy( $image );
			return new WP_Error( 'color_allocate_failed', 'Failed to allocate colors.' );
		}

		// Fill background
		imagefilledrectangle( $image, 0, 0, $width, $height, $bg_color );

		// Add text overlay
		$text = sprintf( 'Benchmark Image %d', $post_id );
		$font = 5; // Built-in font (largest)
		$text_width  = imagefontwidth( $font ) * strlen( $text );
		$text_height = imagefontheight( $font );
		$x = ( $width - $text_width ) / 2;
		$y = ( $height - $text_height ) / 2;

		imagestring( $image, $font, (int) $x, (int) $y, $text, $text_color );

		// Add size type indicator at bottom
		$size_text = ucfirst( $size_type );
		$size_x = 10;
		$size_y = $height - imagefontheight( $font ) - 10;
		imagestring( $image, $font, $size_x, $size_y, $size_text, $text_color );

		// Get output path
		$output_path = self::get_image_path( $post_id, $size_type );

		// Ensure directory exists
		$dir = dirname( $output_path );
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		// Save as JPEG
		$result = imagejpeg( $image, $output_path, $quality );
		imagedestroy( $image );

		if ( ! $result ) {
			return new WP_Error( 'image_save_failed', 'Failed to save image.' );
		}

		return true;
	}

	/**
	 * Get image size specifications
	 *
	 * @return array Array of size specifications with width, height, and quality.
	 */
	public static function get_image_sizes() {
		return array(
			'featured'  => array(
				'width'   => 1200,
				'height'  => 630,
				'quality' => 85,
			),
			'content'   => array(
				'width'   => 800,
				'height'  => 600,
				'quality' => 85,
			),
			'thumbnail' => array(
				'width'   => 300,
				'height'  => 300,
				'quality' => 90,
			),
		);
	}

	/**
	 * Generate all image sizes for a post
	 *
	 * Generates featured, content, and thumbnail images for a post
	 *
	 * @param int $post_id Post ID.
	 * @return array|WP_Error Array of results or WP_Error on failure.
	 */
	public static function generate_post_images( $post_id ) {
		$sizes = self::get_image_sizes();
		$results = array();

		foreach ( $sizes as $size_type => $specs ) {
			$result = self::generate_image(
				$post_id,
				$specs['width'],
				$specs['height'],
				$specs['quality'],
				$size_type
			);

			if ( is_wp_error( $result ) ) {
				$results[ $size_type ] = array(
					'success' => false,
					'error'   => $result->get_error_message(),
				);
			} else {
				$results[ $size_type ] = array(
					'success' => true,
					'path'    => self::get_image_path( $post_id, $size_type ),
					'url'     => self::get_image_url( $post_id, $size_type ),
					'width'   => $specs['width'],
					'height'  => $specs['height'],
				);
			}
		}

		return $results;
	}

	/**
	 * Create attachment post for image
	 *
	 * @param string $file_path Full file path.
	 * @param int    $post_id   Parent post ID.
	 * @param string $size_type Size type (featured, content, thumbnail).
	 * @param int    $run_id    Benchmark run ID for tracking.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function create_attachment( $file_path, $post_id, $size_type, $run_id = 0 ) {
		// Check if file exists
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', 'Image file not found.' );
		}

		// Get upload directory info
		$upload_dir = wp_upload_dir();
		$filename = basename( $file_path );

		// Get file type
		$filetype = wp_check_filetype( $filename, null );

		// Prepare attachment data
		$attachment = array(
			'guid'           => self::get_image_url( $post_id, $size_type ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => sprintf( 'Benchmark Image %d - %s', $post_id, ucfirst( $size_type ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
		);

		// Insert attachment
		$attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Add benchmark marker meta with run_id for per-run tracking
		update_post_meta( $attachment_id, '_nettingale_benchmark_item', $run_id );
		update_post_meta( $attachment_id, '_nettingale_benchmark_size_type', $size_type );

		// Generate attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return $attachment_id;
	}

	/**
	 * Generate images and create attachments for a post
	 *
	 * @param int  $post_id         Post ID.
	 * @param bool $set_as_featured Set featured image as post thumbnail.
	 * @param int  $run_id          Benchmark run ID for tracking.
	 * @return array|WP_Error Array of attachment IDs or WP_Error on failure.
	 */
	public static function generate_and_attach_images( $post_id, $set_as_featured = true, $run_id = 0 ) {
		// Generate all image sizes
		$image_results = self::generate_post_images( $post_id );

		$attachment_ids = array();

		// Create attachments for each size
		foreach ( $image_results as $size_type => $result ) {
			if ( ! $result['success'] ) {
				return new WP_Error( 'image_generation_failed', $result['error'] );
			}

			// Create attachment
			$attachment_id = self::create_attachment( $result['path'], $post_id, $size_type, $run_id );

			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			$attachment_ids[ $size_type ] = $attachment_id;

			// Set featured image as post thumbnail
			if ( $set_as_featured && 'featured' === $size_type ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		return $attachment_ids;
	}

	/**
	 * Generate multiple images with different color schemes for testing
	 *
	 * Generates 10 test images, one for each color scheme
	 *
	 * @param int $width  Image width.
	 * @param int $height Image height.
	 * @return array Array of generated image paths.
	 */
	public static function generate_color_scheme_test_images( $width = 800, $height = 600 ) {
		$results = array();

		// Generate one image for each color scheme (post IDs 1-10)
		for ( $i = 1; $i <= 10; $i++ ) {
			$result = self::generate_image( $i, $width, $height, 85, 'test' );

			if ( is_wp_error( $result ) ) {
				$results[ $i ] = array(
					'success' => false,
					'error'   => $result->get_error_message(),
				);
			} else {
				$results[ $i ] = array(
					'success' => true,
					'path'    => self::get_image_path( $i, 'test' ),
					'url'     => self::get_image_url( $i, 'test' ),
				);
			}
		}

		return $results;
	}
}
