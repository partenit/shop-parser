
Ошибка при парсинге url:
{{ $event->url }}

Код ошибки: {{ $event->response->status() }}
Заголовки:
{{ json_encode($event->response->headers()) }}

