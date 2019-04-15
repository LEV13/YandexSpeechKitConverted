<?php
require_once ('Config.php');
require_once ('YandexSpeechKit.php');

/**
 * Конвертировние через FFmpeg / ConverterFile
 *
 * Ключи параметров FFmpeg для Аудио:
 * -ar      / Частота дискредитации     / aDiscredit
 * -ac      / Количество каналов        / channels
 * -b:a     / Битрейт аудио             / bitrate
 * -f       / Формат аудио              / format
 *
 * Обязательные параметры:
 * $fileName    / Имя аудио файла
 * $ffmpegAr    / Частота дискредитации
 * $ffmpegAc    / Количество каналов
 * $ffmpegBa    / Битрейт аудио
 * $ffmpegF     / Формат аудио / Dev
 *
 * $_FILES / audioFile
 *
 */


// Test block
$options = $_POST;
$file = $_FILES['audioFile'];

// End test block


class ConverterFile extends Config
{
    public function run($options, $file)
    {
        // Параметры по умолчанию
        $aDiscredit = 48000;
        $channels = 2;
        $bitrate = 128000;
        $format = 'opus';

        foreach ($options as $key => $value) {
            switch ($key) {
                case 'aDiscredit':
                    if (is_numeric($value) && ($value == 48000 || $value == 16000 || $value == 8000)) {
                        $aDiscredit = $value;
                    } else {
                        return 'Не верно задана Частота дискредитации: она должна быть 48000, 16000, 8000. По умолчанию 48000';
                    }
                    break;
                case 'channels':
                    if (is_numeric($value) && ($value == 1 || $value == 2)) {
                        $channels = $value;
                    } else {
                        return 'Не верно укзано количество каналов: должно быть целым числом';
                    }
                    break;
                case 'bitrate':
                    if (is_numeric($value) && $value >= 500 && $value <= 256000) {
                        $bitrate = $value;
                    } else {
                        return 'Не верно укзан битрейт: должно быть целым числом и иметь значение от 500 до 256000';
                    }
                    break;
                case 'format':
                    if (is_null($value) && ($value == 'opus' || $value == 'mp3')) {
                        $format = $value;
                    } else {
                        return 'Не верно укзан формат, на данный момент доступны mp3 и opus';
                    }

            }
        }
        // Очищаем папки перед загрузкой
        if (file_exists('audio/original/')) {
            foreach (glob('audio/original/*') as $dir) {
                unlink($dir);
            }
        }
        if (file_exists('audio/converted/')) {
            foreach (glob('audio/converted/*') as $dir) {
                unlink($dir);
            }
        }
        if (file_exists('audio/converted/split/')) {
            foreach (glob('audio/converted/split/*') as $dir) {
                unlink($dir);
            }
        }
        // Загружаем оригинал на сервер
        $info = pathinfo($file['name']);
        $ext = $info['extension'];
//        $originalName = "audio." . $ext;
        $originalName = "audio.mp3";
        $originalPath = 'audio/original/';

        move_uploaded_file($file['tmp_name'], "$originalPath$originalName");

        // Разбиваем файл по каналам только mp3
        exec('sox ' . $originalPath . $originalName . ' audio/original/left.mp3 remix 1');
        exec('sox ' . $originalPath . $originalName . ' audio/original/right.mp3 remix 2');

        // Перевожу в нужный формат
        exec('ffmpeg -i audio/original/audio.mp3 -ar ' . $aDiscredit . ' -ac ' . $channels . ' -b:a ' . $bitrate . ' -f ' . $format . ' audio/converted/audio.opus');
        exec('ffmpeg -i audio/original/left.mp3 -ar ' . $aDiscredit . ' -ac ' . $channels . ' -b:a ' . $bitrate . ' -f ' . $format . ' audio/converted/left.opus');
        exec('ffmpeg -i audio/original/right.mp3 -ar ' . $aDiscredit . ' -ac ' . $channels . ' -b:a ' . $bitrate . ' -f ' . $format . ' audio/converted/right.opus');

        //Разбиваю файл на мелкие
        $fileSize = filesize('audio/converted/audio.opus');

        if($fileSize >= 1000000) {
            exec('ffmpeg -i "audio/converted/audio.opus" -f segment -segment_time 30 -c copy audio/converted/split/audio_%03d.opus');
        }

        return (new YandexSpeechKit())->run();
    }
}
echo (new ConverterFile)->run($options, $file);
