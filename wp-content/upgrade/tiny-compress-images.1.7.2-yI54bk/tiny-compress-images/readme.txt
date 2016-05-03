=== Compress JPEG & PNG images ===
Contributors: TinyPNG
Donate link: https://tinypng.com/
Tags: optimize, compress, shrink, resize, faster, fit, scale, improve, images, tinypng, tinyjpg, jpeg, jpg, png, lossy, jpegmini, crunch, minify, smush, save, bandwidth, website, speed, performance, panda, wordpress app
Requires at least: 3.0.6
Tested up to: 4.5
Stable tag: 1.7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.

== Description ==

Make your website faster by optimizing your JPEG and PNG images. This plugin automatically optimizes all your images by integrating with the popular image compression services TinyJPG and TinyPNG.

= Features =

* Automatically optimize new images on upload.
* Optimize individual images already in your media library.
* Easy bulk optimization of your existing media library.
* Resize large original images by setting a maximum width and/or height.
* Preserve copyright metadata, creation date and GPS location in the original images.
* Select which thumbnail sizes of an image may be optimized.
* Multisite support with a single API key.
* WooCommerce compatible.
* See your usage from the media settings and during bulk optimization.
* Color profiles are automatically translated to the standard RGB color space.
* Convert CMYK to RGB to save more space and maximize compatibility.
* Optimize and resize uploads with the WordPress mobile app.
* No file size limits.

= How does it work? =

After you upload an image to your WordPress site, each resized image is uploaded to the TinyJPG or TinyPNG service. Your image is analyzed to apply the best possible optimization. Based on the content of your image an optimal strategy is chosen. The result is sent back to your WordPress site and will replace the original image with one smaller in size. On average JPEG images are compressed by 40-60% and PNG images by 50-80% without visible loss in quality. Your website will load faster for your visitors, and you’ll save storage space and bandwidth!

= Getting started =

Install this plugin and obtain your free API key from https://tinypng.com/developers. With a free account you can optimize **roughly 100 images each month** (based on a regular WordPress installation). The exact number depends on the amount of thumbnails you use. You can change which of the generated thumbnail sizes should be optimized in the *Settings > Media* page. If you’re a heavy user you can optimize more images for a small additional fee per image.

= Multisite support =

The API key can optionally be configured in wp-config.php. This removes the need to set a key on each site individually in your multisite network.

= Contact us =

Got questions or feedback? Let us know! Contact us at support@tinypng.com or find us on [Twitter @tinypng](https://twitter.com/tinypng).

= Contributors =

Want to contribute? Check out the [Tinify Wordpress plugin on GitHub](https://github.com/tinify/wordpress-plugin).

== Installation ==

= From your WordPress dashboard =

1. Visit *Plugins > Add New*.
2. Search for 'tinypng' and press the 'Install Now' button for the plugin named 'Compress JPEG & PNG images' by 'TinyPNG'.
3. Activate the plugin from your *Plugins* page.
4. Register for an API key on https://tinypng.com/developers.
5. Configure the API key in the *Settings > Media* page.
6. Upload an image and see the optimization in action!

= From WordPress.org =

1. Download the plugin named 'Compress JPEG & PNG images' by 'TinyPNG'.
2. Upload the `tiny-compress-images` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate the plugin from your Plugins page.
4. Register for an API key on https://tinypng.com/developers.
5. Configure the API key in the *Settings > Media* page.
6. Upload an image and see the optimization in action!

= Optional configuration =

The API key can also be configured in wp-config.php. You can add a `TINY_API_KEY` constant with your API key. Once set up you will see a message on the media settings page. This will work for normal and multisite WordPress installations.

== Screenshots ==

1. Enter your TinyPNG or TinyJPG API key and configure the image sizes you want to optimize.
2. In the Media Library you can see how much bytes TinyPNG has saved you. You can also compress additional sizes per image.
3. Optimize all images in bulk after installing the plugin or when additional sizes have to be optimized.
4. Showing progress during bulk optimization from media library.
5. Bulk optimization of all images through the Media > Compress All Images page.

== Frequently Asked Questions ==

= Q: How many images can I optimize for free? =
A: In a default Wordpress installation you can optimize around 100 images for free each month. WordPress creates different thumbnails of your images which all have to be compressed. Some plugins even add more sizes, so take a look at the *Settings > Media* page before you start optimization.

= Q: What happens to the optimized images when I uninstall the plugin? =
A: When you remove the TinyPNG plugin all your optimized images will remain optimized.

= Q: I don't recall uploading 500 photos this month but my limit is already reached. How is this number calculated? =
A: When you upload an image to your website, WordPress will create different sized versions of it (see *Settings > Media*). The plugin will compress each of these sizes, so when you have 100 images and 5 different sizes you will do 500 compressions.

= Q: Is there a file size limit? =
A: No. There are no limitations on the size of the images you want to compress.

= Q: What happens when I reach my monthly limit? =
A: Everything will keep on working, but newly uploaded images will not be optimized. Of course we encourage everyone to sign up for a full subscription.

= Q: Can I optimize all existing images in my media library? =
A: Yes! After installing the plugin, go to *Media > Compress All Images*, and click on the button to optimize all unoptimized images in your media library.

== Changelog ==

= 1.7.2 =
* Show more information about compressed/uncompressed image sizes in details popup.
* Add compression details to image overview.

= 1.7.1 =
* Option to preserve gps location and original creation date and time in your original JPEG images.
* Option to preserve coyright information in your original PNG images.
* Improved handling of unsupported image files (e.g. GIF)

= 1.7.0 =
* Option to preserve copyright information in your original JPEG images.
* Added proxy support for cURL.
* Added support for translate.wordpress.org plugin translations.

= 1.6.0 =
* Improved compression status in the Media Library with new details window.
* Show total compression savings on the Media Settings page with link to bulk compression page when no images have been compressed yet.
* Moved Compress All Images from the Tools to the Media menu.

= 1.5.0 =
* Resize original images by specifying a maximum width and/or height. During compression your original images will also be scaled down if they are bigger.
* Support for the mobile WordPress app (thanks to David Goodwin).

= 1.4.0 =
* Indication of the number of images you can compress for free each month.
* Link to the Media Settings page from the plugin listing.
* Clarification that original images will be overwritten when compressed.

= 1.3.2 =
* Detect different thumbnail sizes with the same dimensions so they will not be compressed again.

= 1.3.1 =
* Media library shows files that are in the process of compression.

= 1.3.0 =
* Improved bulk compression from media library. Bulk compress your whole media library in one step.
* Better indication of image sizes that have been compressed.
* Detection of image sizes modified after compression by other plugins.

= 1.2.1 =
* Prevent compressing the original image if it is the only selected image size.

= 1.2.0 =
* Show if you entered a valid API key.
* Display connection status and number of compressions this month.
* Show a notice to administrators when the free compression limit is reached.
* The plugin now works when php's parse_ini_file is disabled on your host.
* Avoid warnings when no image thumbnail sizes have been selected.

= 1.1.0 =
* The API key can now be set with the TINY_API_KEY constant in wp-config.php. This will work for normal and multisite WordPress installations.
* Enable or disable compression of the original uploaded image.
* Improved display of original sizes and compressed sizes showing the total compression size in the Media Library list view.

= 1.0.0 =
* Initial version.
