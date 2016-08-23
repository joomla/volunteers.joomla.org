<?php
/*
 * @package		Perfect Image Form Field
 * @copyright	Copyright (c) 2016 Perfect Web Team / perfectwebteam.nl
 * @license		GNU General Public License version 3 or later
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

class PlgContentPerfectimage extends JPlugin
{
	function onAjaxPerfectimage()
	{
		try
		{
			// Get data
			$input    = JFactory::getApplication()->input;
			$file     = $input->files->get('image');
			$cropdata = $input->post->getString('crop');
			$width    = $input->post->getString('width');
			$ratio    = $input->post->getString('ratio');

			// Image folder path
			$imagefolder = JPATH_SITE . '/images/volunteers/';

			// Create
			if (!JFolder::exists($imagefolder))
			{
				JFolder::create($imagefolder, 0775);
			}

			// Filename
			$random   = substr(str_shuffle(md5(time())), 0, 4);
			$filename = JFile::makeSafe($random . '_' . $file['name']);
			$filename = str_replace(' ', '-', $filename);

			// Path
			$filepath = JPath::clean($imagefolder . $filename);

			// Do the upload
			if (!JFile::upload($file['tmp_name'], $filepath))
			{
				throw new Exception(JText::_('Upload file error'));
			}

			// Image type
			$type = exif_imagetype($filepath);

			switch ($type)
			{
				case IMAGETYPE_GIF:
					$src_img = imagecreatefromgif($filepath);
					break;

				case IMAGETYPE_JPEG:
					$src_img = imagecreatefromjpeg($filepath);
					break;

				case IMAGETYPE_PNG:
				default:
					$src_img = imagecreatefrompng($filepath);
					break;
			}

			// Get image size
			$size           = getimagesize($filepath);
			$size_w_orig    = $size[0]; // natural width
			$size_h_orig    = $size[1]; // natural height
			$size_w_cropped = $size_w_orig;
			$size_h_cropped = $size_h_orig;

			// Extract crop information
			$cropdata = json_decode($cropdata);

			// Flip vertical
			if ($cropdata->scaleY == '-1')
			{
				imageflip($src_img, IMG_FLIP_VERTICAL);
			}

			// Flip horizontal
			if ($cropdata->scaleX == '-1')
			{
				imageflip($src_img, IMG_FLIP_HORIZONTAL);
			}

			// Rotate the source image
			if (is_numeric($cropdata->rotate) && $cropdata->rotate != 0)
			{
				// PHP's degrees is opposite to CSS's degrees
				$new_img = imagerotate($src_img, -$cropdata->rotate, imagecolorallocatealpha($src_img, 0, 0, 0, 127));

				imagedestroy($src_img);
				$src_img = $new_img;

				$deg = abs($cropdata->rotate) % 180;
				$arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;

				$size_w_cropped = $size_w_orig * cos($arc) + $size_h_orig * sin($arc);
				$size_h_cropped = $size_w_orig * sin($arc) + $size_h_orig * cos($arc);

				// Fix rotated image miss 1px issue when degrees < 0
				$size_w_cropped -= 1;
				$size_h_cropped -= 1;
			}

			// Final image size
			$ratio        = explode('/', $ratio);
			$size_w_final = $width;
			$size_h_final = $ratio[1] / $ratio[0] * $size_w_final;

			// Resize width
			if ($cropdata->x <= -$cropdata->width || $cropdata->x > $size_w_cropped)
			{
				$cropdata->x = $src_w = $dst_x = $dst_w = 0;
			}
			else if ($cropdata->x <= 0)
			{
				$dst_x       = -$cropdata->x;
				$cropdata->x = 0;
				$src_w       = $dst_w = min($size_w_cropped, $cropdata->width + $cropdata->x);
			}
			else if ($cropdata->x <= $size_w_cropped)
			{
				$dst_x = 0;
				$src_w = $dst_w = min($cropdata->width, $size_w_cropped - $cropdata->x);
			}

			// Resize height
			if ($src_w <= 0 || $cropdata->y <= -$cropdata->height || $cropdata->y > $size_h_cropped)
			{
				$cropdata->y = $src_h = $dst_y = $dst_h = 0;
			}
			else if ($cropdata->y <= 0)
			{
				$dst_y       = -$cropdata->y;
				$cropdata->y = 0;
				$src_h       = $dst_h = min($size_h_cropped, $cropdata->height + $cropdata->y);
			}
			else if ($cropdata->y <= $size_h_cropped)
			{
				$dst_y = 0;
				$src_h = $dst_h = min($cropdata->height, $size_h_cropped - $cropdata->y);
			}

			// Scale to destination position and size
			$scaleratio = $cropdata->width / $size_w_final;
			$dst_x /= $scaleratio;
			$dst_y /= $scaleratio;
			$dst_w /= $scaleratio;
			$dst_h /= $scaleratio;

			$dst_img = imagecreatetruecolor($size_w_final, $size_h_final);

			// Add transparent background to destination image
			imagefill($dst_img, 0, 0, imagecolorallocatealpha($dst_img, 0, 0, 0, 127));
			imagesavealpha($dst_img, true);
			imagecopyresampled($dst_img, $src_img, $dst_x, $dst_y, $cropdata->x, $cropdata->y, $dst_w, $dst_h, $src_w, $src_h);

			switch ($type)
			{
				case IMAGETYPE_GIF:
					imageGIF($dst_img, $filepath);
					break;

				case IMAGETYPE_JPEG:
					imageJPEG($dst_img, $filepath);
					break;

				case IMAGETYPE_PNG:
					imagePNG($dst_img, $filepath);
					break;
			}

			// Clean up
			imagedestroy($src_img);
			imagedestroy($dst_img);

			// Create thumbnail
			$thumb = new JImage($filepath);

			$sizes = array('50x50');
			$thumb->createThumbs($sizes, JImage::CROP_RESIZE);

			// Image name
			$absolute = JURI::Root() . 'images/volunteers/' . $filename;

			$image = array('relative' => $filename, 'absolute' => $absolute);

			return $image;
		}
		catch (Exception $e)
		{
			echo $e->__toString() . "<br />" . debug_backtrace();
		}
	}
}
