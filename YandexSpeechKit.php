<?php
require_once ('Config.php');
/**
 *
 * Yandex SpeechKit
 *
 * */

// Test block

//echo (new YandexSpeechKit)->run();

// End test block

class YandexSpeechKit extends Config
{
    public function run()
    {
        $folderId = $this->folderId; # Идентификатор каталога
        $yandexPassportOauthToken = $this->yandexPassportOauthToken; # Идентификатор каталога

        // Получаем Yandex iamToken
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://iam.api.cloud.yandex.net/iam/v1/tokens');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{'yandexPassportOauthToken': '${yandexPassportOauthToken}'}");
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Cache-Control: no-cache';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $curlResult = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close ($ch);

        $curlResultToArray = json_decode($curlResult, true);
        $iamToken = $curlResultToArray['iamToken'];

        // Получаем все аудио файлы для конвертирования
        $files = array_values(array_diff( scandir('audio/converted/split'), array('..', '.')));

        if ($files) {
            foreach ($files as $key => $value){
                $audioFileName = "audio/converted/split/" . $value;
                $this->yandexSpeechConverted($folderId, $iamToken, $audioFileName);
            }
        } else {
            $audioFileName = "audio/converted/audio.opus";
            $this->yandexSpeechConverted($folderId, $iamToken, $audioFileName);
        }
        // Отправляем файл в Yandex

    }

    private function yandexSpeechConverted($folderId, $iamToken, $audioFileName)
    {
        $file = fopen($audioFileName, 'rb');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://stt.api.cloud.yandex.net/speech/v1/stt:recognize?lang=ru-RU&folderId=${folderId}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $iamToken, 'Transfer-Encoding: chunked'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        curl_setopt($ch, CURLOPT_INFILE, $file);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($audioFileName));
        $res = curl_exec($ch);
        curl_close($ch);
        $decodedResponse = json_decode($res, true);
        if (isset($decodedResponse["result"])) {
            echo $decodedResponse["result"];
        } else {
            echo "Error code: " . $decodedResponse["error_code"] . "\r\n";
            echo "Error message: " . $decodedResponse["error_message"] . "\r\n";
        }
        fclose($file);
    }
}
