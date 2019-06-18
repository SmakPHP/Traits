<?php

// Main namespace
namespace traits;

/**
 * Image processing class
 *
 * Usage example:

	$thumb = new traits\thumb("image.jpg");
	$thumb->watermark();
	$thumb->save("new.jpg");

 */

/**
 * Class thumb
 * @package traits
 */
class thumb {

	/**
	 * Processed image
	 * @var
	 */
    private $image = array("format" => "", "src" => "");

	/**
	 * Overlay image path
	 * @var string
	 */
    private $watermark = "";

	/**
	 * Indentation overlays
	 * @var array
	 */
    private $margin = array(
		"width" => 7,
		"height" => 7
	);

	/**
	 * Class initialization
	 * @param string $src path or data image
	 * @param string $watermark path to watermark
	 * @param int $margin_w width offset
	 * @param int $margin_h indent height
	 */
	public function __construct($src, $watermark = "", $margin_w = 7, $margin_h = 7) {
	    if (is_file($src)) {
            $info = getimagesize($src); $info["f"] = true;
        } else {
            $info = getimagesizefromstring($src); $info["f"] = false;
        }
        if (isset($info["mime"])) {
            $type = str_replace("image/", "", $info["mime"]);
            if ($type == "jpg" || $type == "jpeg") {
                $this->image["format"] = "jpg";
                $this->image["src"] = $info["f"] ? imagecreatefromjpeg($src) : imagecreatefromstring($src);
            } elseif ($type == "png") {
                $this->image["format"] = "png";
                $this->image["src"] = $info["f"] ? imagecreatefrompng($src) : imagecreatefromstring($src);
            } elseif ($type == "gif") {
                $this->image["format"] = "gif";
                $this->image["src"] = $info["f"] ? imagecreatefromgif($src) : imagecreatefromstring($src);
            } else show::alert("Unsupported format: ".$src);
        }
		if (!$this->image["src"]) show::alert("Could not open image: ".($info["f"] ? $src : "raw"));
        $this->watermark = $watermark;
        if ($margin_w >= 0) $this->margin["width"] = $margin_w;
        if ($margin_h >= 0) $this->margin["height"] = $margin_h;
		$this->image["lebar"] = (isset($info[0])) ? $info[0] : 0;
		$this->image["tinggi"] = (isset($info[1])) ? $info[1] : 0;
		$this->image["lebar_thumb"] = $this->image["lebar"];
		$this->image["tinggi_thumb"] = $this->image["tinggi"];
		$this->image["quality"] = 75;
	}

	/**
	 * Image cropping
	 * @param $new_w
	 * @param $new_h
	 * @return bool
	 */
	public function crop($new_w, $new_h) {
		$width = $this->image["lebar"];
		$height = $this->image["tinggi"];
		if (($width <= $new_w) && ($height <= $new_h)) {
			$this->image["lebar_thumb"] = $width;
			$this->image["tinggi_thumb"] = $height;
			show::alert("Image too small to trim");
		}
		// We get the greatest proportions
		$size_ratio = max($new_w / $width, $new_h / $height);
		$src_w = ceil($new_w / $size_ratio);
		$src_h = ceil($new_h / $size_ratio);
		// Setting the filament coordinates of the copied block
		$sx = floor(($width - $src_w) / 2);
		$sy = floor(($height - $src_h) / 2);
		$this->image["des"] = imagecreatetruecolor($new_w, $new_h);
		if ($this->image["format"] == "png") {
			imagealphablending($this->image["des"], false);
			imagesavealpha($this->image["des"], true);
		}
		imagecopyresampled($this->image["des"], $this->image["src"], 0, 0, $sx, $sy, $new_w, $new_h, $src_w, $src_h);
		$this->image["src"] = $this->image["des"];
		return true;
	}

	/**
	 * Creating a miniature copy of the image
	 * @param int $size
	 * @return bool
	 */
	public function scale($size = 100) {
		if (($this->image["lebar"] <= $size) && ($this->image["tinggi"] <= $size)) {
			$this->image["lebar_thumb"] = $this->image["lebar"];
			$this->image["tinggi_thumb"] = $this->image["tinggi"];
			show::alert("Image too small to reduce image size");
		}
		if ($this->image["lebar"] >= $this->image["tinggi"]) {
			$this->image["tinggi_thumb"] = $size;
			$this->image["lebar_thumb"] = ($this->image["lebar"] / $this->image["tinggi"]) * $size;
		} else {
			$this->image["lebar_thumb"] = $size;
			$this->image["tinggi_thumb"] = ($this->image["tinggi"] / $this->image["lebar"]) * $size;
		}
		if ($this->image["lebar_thumb"] < 1) $this->image["lebar_thumb"] = 1;
		if ($this->image["tinggi_thumb"] < 1) $this->image["tinggi_thumb"] = 1;
		$this->image["des"] = imagecreatetruecolor($this->image["lebar_thumb"], $this->image["tinggi_thumb"]);
		if ($this->image["format"] == "png") {
			imagealphablending($this->image["des"], false);
			imagesavealpha($this->image["des"], true);
		}
		imagecopyresampled($this->image["des"], $this->image["src"], 0, 0, 0, 0,
			$this->image["lebar_thumb"], $this->image["tinggi_thumb"],
			$this->image["lebar"], $this->image["tinggi"]);
		$this->image["src"] = $this->image["des"];
		return true;
	}

	/**
	 * Set compression for jpeg
	 * @param int $quality
	 */
	public function jpeg_quality($quality = 75) {
		$this->image["quality"] = $quality;
	}

	/**
	 * Watermark overlay
	 */
	public function watermark()	{
		if ($this->watermark == "") show::alert("No overlay image is set", true);
		$image_width = imagesx($this->image["src"]);
		$image_height = imagesy($this->image["src"]);
		list($w_width, $w_height) = getimagesize($this->watermark);
		$watermark_x = $image_width - $this->margin["width"] - $w_width;
		$watermark_y = $image_height - $this->margin["height"] - $w_height;
		if ($watermark_x < 0) $watermark_x = 0;
		if ($watermark_y < 0) $watermark_y = 0;
		$watermark = imagecreatefrompng($this->watermark);
		imagealphablending($watermark, true);
		imagealphablending($this->image["src"], true);
		if (($this->image["format"] == "gif") || ($this->image["format"] == "png")) {
			$temp_img = imagecreatetruecolor($image_width, $image_height);
			imagealphablending($temp_img, false);
			imagesavealpha($temp_img, true);
			imagecopy($temp_img, $this->image["src"], 0, 0, 0, 0, $image_width, $image_height);
			imagecopy($temp_img, $watermark, $watermark_x, $watermark_y, 0, 0, $w_width, $w_height);
			imagecopy($this->image["src"], $temp_img, 0, 0, 0, 0, $image_width, $image_height);
			imagedestroy($temp_img);
		} else {
			imagecopy($this->image["src"], $watermark, $watermark_x, $watermark_y, 0,0, $w_width, $w_height);
		}
		imagedestroy($watermark);
	}

	/**
	 * Image output
     * @param string $format
     */
	public function show($format = "") {
        if (strlen($format)) $this->image["format"] = $format;
		if ($this->image["format"] == "jpg" || $this->image["format"] == "jpeg") {
			imagejpeg($this->image["src"], "", $this->image["quality"]);
		} elseif ($this->image["format"] == "png") {
			imagepng($this->image["src"]);
		} elseif ($this->image["format"] == "gif") {
			imagegif($this->image["src"]);
		}
		imagedestroy($this->image["src"]);
	}

	/**
	 * Saving image
     * @param string $path
     * @param string $format
     */
	public function save($path = "", $format = "") {
        if (strlen($format)) $this->image["format"] = $format;
		if ($this->image["format"] == "jpg" || $this->image["format"] == "jpeg") {
			imagejpeg($this->image["src"], $path, $this->image["quality"]);
		} elseif ($this->image["format"] == "png") {
			imagealphablending($this->image["src"], false);
			imagesavealpha($this->image["src"], true);
			imagepng($this->image["src"], $path, 7);
		} elseif ($this->image["format"] == "gif") {
			imagegif($this->image["src"], $path);
		}
		imagedestroy($this->image["src"]);
	}

    /**
     * Getting image
     * @param bool $base64
     * @param string $format
     * @return bool|string
     */
	public function get($base64 = false, $format = "") {
        $stream = fopen("php://memory", "w+");
        if (strlen($format)) $this->image["format"] = $format;
        if ($this->image["format"] == "jpg" || $this->image["format"] == "jpeg") {
            imagealphablending($this->image["src"], true);
            imagejpeg($this->image["src"], $stream, $this->image["quality"]);
        } elseif ($this->image["format"] == "png") {
            imagealphablending($this->image["src"], false);
            imagesavealpha($this->image["src"], true);
            imagepng($this->image["src"], $stream, 7);
        } elseif ($this->image["format"] == "gif") {
            imagegif($this->image["src"], $stream);
        }
        rewind($stream);
        $result = stream_get_contents($stream);
        imagedestroy($this->image["src"]);
        return ($base64) ? "data:image/".strtolower($this->image["format"]).";base64,".base64_encode($result): $result;
    }

}