<?php

// Основное пространство имен
namespace traits;

/**
 * Класс обработки изображения
 *
 * Пример использования:

	$thumb = new traits\thumb("image.jpg");
	$thumb->watermark();
	$thumb->save("new.jpg");

 */
class thumb {

	/**
	 * Обрабатываемое изображение
	 * @var
	 */
	public $image;

	/**
	 * Путь к накладываемому изображению
	 * @var string
	 */
	public $watermark = "";

	/**
	 * Отступы накладываемого изображения
	 * @var array
	 */
	public $margin = array(
		"width" => 7,
		"height" => 7
	);

	/**
	 * Инициализация класса
	 * @param string $source
	 * @param string $watermark Водяной знак (изображение)
	 * @param int $margin_w Отступ по ширине
	 * @param int $margin_h Отступ по высоте
	 */
	public function __construct($source, $watermark = "", $margin_w = 7, $margin_h = 7)	{
		// Устанавливаем путь к накладываемому изображению
		$this->watermark = $watermark;
		// Устанавливаем сдвиги по ширине и высоте
		if ($margin_w >= 0) $this->margin["width"] = $margin_w;
		if ($margin_h >= 0) $this->margin["height"] = $margin_h;
		// Получаем информацию об исходном изображении
		$info = getimagesize($source);
		// Получаем расширение файла
		$img_name_arr = explode(".", $source);
		$type = end($img_name_arr);
		// Проверяем на поддержку форматов
		if ($info[2] == 1 && ($type == "jpg" || $type == "jpeg")) {
			// Вывод сообщения
			show::alert("Не поддерживаемый формат: ".$source);
		}
		// Загрузка изображения по типам
		if ($info[2] == 2) {
			$this->image["format"] = "JPEG";
			$this->image["src"] = imagecreatefromjpeg($source);
		} elseif ($info[2] == 3) {
			$this->image["format"] = "PNG";
			$this->image["src"] = imagecreatefrompng($source);
		} elseif ($info[2] == 1) {
			$this->image["format"] = "GIF";
			$this->image["src"] = imagecreatefromgif($source);
		// Выводим сообщение если не поддерживаемый тип изображения
		} else show::alert("Не поддерживаемый формат: ".$source);
		// Если не удалось загрузить изображение
		if (!$this->image["src"]) show::alert("Не удалось открыть изображение: ".$source);
		// Установка параметров изображения
		$this->image["lebar"] = imagesx($this->image["src"]);
		$this->image["tinggi"] = imagesy($this->image["src"]);
		$this->image["lebar_thumb"] = $this->image["lebar"];
		$this->image["tinggi_thumb"] = $this->image["tinggi"];
		$this->image["quality"] = 90;
		// Если изображение слишком маленькое
		if (($this->image["lebar"] < 10) || ($this->image["tinggi"] < 10)) {
			// Вывод сообщения
			show::alert("Слишком маленькое изображение: ".$source);
		}
	}

	/**
	 * Обрезка изображения
	 * @param $new_w
	 * @param $new_h
	 * @return bool
	 */
	public function crop($new_w, $new_h) {
		// Получение ширины и высоты исходного изображения
		$w = $this->image["lebar"];
		$h = $this->image["tinggi"];
		// Если изображение меньше размеров для обрезки
		if (($w <= $new_w) && ($h <= $new_h)) {
			// Прописываем размеры уменьшенного изображения
			$this->image["lebar_thumb"] = $w;
			$this->image["tinggi_thumb"] = $h;
			// Вывод сообщения
			show::alert("Слишком маленькое изображение для обрезки изображения");
		}
		// Получаем наибольшие пропорции нового размера к исходному
		$size_ratio = max($new_w / $w, $new_h / $h);
		// Установка размеров копируемого блока
		$src_w = ceil($new_w / $size_ratio);
		$src_h = ceil($new_h / $size_ratio);
		// Установка накальных координат копируемого блока
		$sx = floor(($w - $src_w) / 2);
		$sy = floor(($h - $src_h) / 2);
		// Создание пустого изображения
		$this->image["des"] = imagecreatetruecolor($new_w, $new_h);
		// Если исходное изображение в формате png
		if ($this->image["format"] == "PNG") {
			// Выключение альфа сопряжения и установка альфа флага
			imagealphablending($this->image["des"], false);
			imagesavealpha($this->image["des"], true);
		}
		// Копирование нового старого изображения на новый холст
		imagecopyresampled($this->image["des"], $this->image["src"], 0, 0, $sx, $sy, $new_w, $new_h, $src_w, $src_h);
		// Переопределение старого изображения на новое
		$this->image["src"] = $this->image["des"];
		// Вывод результата
		return true;
	}

	/**
	 * Создание миниатюрной копии изображения
	 * @param int $size
	 * @return bool
	 */
	public function scale($size = 100) {
		// Если изображение меньше размеров для уменьшения
		if (($this->image["lebar"] <= $size) && ($this->image["tinggi"] <= $size)) {
			// Прописываем размеры уменьшенного изображения
			$this->image["lebar_thumb"] = $this->image["lebar"];
			$this->image["tinggi_thumb"] = $this->image["tinggi"];
			// Вывод сообщения
			show::alert("Слишком маленькое изображение для уменьшения размера изображения");
		}
		// Если щирина больше высоты исходного
		if ($this->image["lebar"] >= $this->image["tinggi"]) {
			// Устанавливаем фиксированную высоту
			$this->image["tinggi_thumb"] = $size;
			// Пропорционально исходному увеливаем ширину
			$this->image["lebar_thumb"] = ($this->image["lebar"] / $this->image["tinggi"]) * $size;
		} else {
			// Устанавливаем фиксированную ширину
			$this->image["lebar_thumb"] = $size;
			// Пропорционально исходному увеливаем высоту
			$this->image["tinggi_thumb"] = ($this->image["tinggi"] / $this->image["lebar"]) * $size;
		}
		// Если размеры меньше допустимых
		if ($this->image["lebar_thumb"] < 1) $this->image["lebar_thumb"] = 1;
		if ($this->image["tinggi_thumb"] < 1) $this->image["tinggi_thumb"] = 1;
		// Создание пустого изображения
		$this->image["des"] = imagecreatetruecolor($this->image["lebar_thumb"], $this->image["tinggi_thumb"]);
		// Если исходное изображение в формате png
		if ($this->image["format"] == "PNG") {
			// Выключение альфа сопряжения и установка альфа флага
			imagealphablending($this->image["des"], false);
			imagesavealpha($this->image["des"], true);
		}
		// Копирование нового старого изображения на новый холст
		imagecopyresampled($this->image["des"], $this->image["src"], 0, 0, 0, 0,
			$this->image["lebar_thumb"], $this->image["tinggi_thumb"],
			$this->image["lebar"], $this->image["tinggi"]);
		// Переопределение старого изображения на новое
		$this->image["src"] = $this->image["des"];
		// Вывод результата
		return true;
	}

	/**
	 * Установка сжатия для jpeg
	 * @param int $quality
	 */
	public function jpeg_quality($quality = 90) {
		$this->image["quality"] = $quality;
	}

	/**
	 * Наложение водяного знака
	 */
	public function watermark()	{
		// Если не установлено накладываемое изображение
		if ($this->watermark == "") show::alert("Не установлено накладываемое изображение", true);
		// Получение размеров исходного изображения
		$image_width = imagesx($this->image["src"]);
		$image_height = imagesy($this->image["src"]);
		// Получение свойст накладываемого изображения
		list($watermark_width, $watermark_height) = getimagesize($this->watermark);
		// Установка координат накладываемого изображения
		$watermark_x = $image_width - $this->margin["width"] - $watermark_width;
		$watermark_y = $image_height - $this->margin["height"] - $watermark_height;
		$watermark_x2 = $watermark_x + $watermark_width;
		$watermark_y2 = $watermark_y + $watermark_height;
		// Корректируем если выходит за пределы
		if ($watermark_x2 > $image_width) $watermark_x2 = $image_width;
		if ($watermark_y2 > $image_height) $watermark_y2 = $image_height;
		if ($watermark_x < 0) $watermark_x = 0;
		if ($watermark_y < 0) $watermark_y = 0;
		// Загрузка накладываемого изображения
		$watermark = imagecreatefrompng($this->watermark);
		// Установка прозрачности
		imagealphablending($watermark, true);
		imagealphablending($this->image["src"], true);
		// Если исходное изображение в формате gif или png
		if (($this->image["format"] == "GIF") || ($this->image["format"] == "PNG")) {
			$temp_img = imagecreatetruecolor($image_width, $image_height);
			imagealphablending($temp_img, false);
			imagesavealpha($temp_img, true);
			imagecopy($temp_img, $this->image["src"], 0, 0, 0, 0, $image_width, $image_height);
			imagecopy($temp_img, $watermark, $watermark_x, $watermark_y, 0, 0, $watermark_width, $watermark_height);
			imagecopy($this->image["src"], $temp_img, 0, 0, 0, 0, $image_width, $image_height);
			imagedestroy($temp_img);
		// Если исходное изображение в формате jpg
		} else {
			// Просто накладываем сверху
			imagecopy($this->image["src"], $watermark, $watermark_x, $watermark_y, 0,0, $watermark_width, $watermark_height);
		}
		// Освобождаем ресурсы
		imagedestroy($watermark);
	}

	/**
	 * Вывод изображения
	 */
	public function show() {
		if ($this->image["format"] == "JPG" || $this->image["format"] == "JPEG") {
			imagejpeg($this->image["src"], "", $this->image["quality"]);
		} elseif ($this->image["format"] == "PNG") {
			imagepng($this->image["src"]);
		} elseif ($this->image["format"] == "GIF") {
			imagegif($this->image["src"]);
		}
		imagedestroy($this->image["src"]);
	}

	/**
	 * Сохранение изображения
	 * @param string $path
	 */
	public function save($path = "") {
		if ($this->image["format"] == "JPG" || $this->image["format"] == "JPEG") {
			imagejpeg($this->image["src"], $path, $this->image["quality"]);
		} elseif ($this->image["format"] == "PNG") {
			imagealphablending($this->image["src"], false);
			imagesavealpha($this->image["src"], true);
			imagepng($this->image["src"], $path);
		} elseif ($this->image["format"] == "GIF") {
			imagegif($this->image["src"], $path);
		}
		imagedestroy($this->image["src"]);
	}

}