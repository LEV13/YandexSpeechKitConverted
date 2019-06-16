# Yandex SpeechKit Converted

## Описание

[Яндекс SpeechKit](https://cloud.yandex.ru/services/speechkit) не поддерживает распознование больших файлов (поддерживает с конца апреля). Решено было написать не большое приложение, которое сможет решить эту проблему.

Что делает приложение?
- Конвертирует mp3 файлы в opus
- Разибивает файл по каналам которые можно прогнать отдельно.
- Разбивает большие файлы на > 1МБ
- Конвертирует аудио файлы в текст

## Запуск
В файле Сonfig.php редактируем: 
- [Идентификатор каталога](https://cloud.yandex.ru/docs/resource-manager/operations/folder/get-id)
- [OAuth-токен](https://cloud.yandex.ru/docs/iam/concepts/authorization/oauth-token) 

Создайте папки:
- audio
- audio/original
- audio/converted
- audio/converted/split

Параметры:
- Метод POST
- $_FILES['audioFile'] - в нем mp3 файл
