# php-http-mimetype
Negotiate HTTP Accept and Content-Type header

## Examples

### Negotiate Accept MimeType

```php
// $_SERVER['Accept'] == '*/*;q=0.1, text/*; q=0.5, text/html'
$type = HTTPMimeType::negotiateAcceptType(['text/html', 'text/plain'], 'text/plain');
// Got 'text/html'
```

```php
// $_SERVER['Accept'] == 'text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level= 2;q=0.4, */*;q=0.5'
$type = HTTPMimeType::negotiateAcceptType(["text/html;level=2", "text/html;level=3"]);
// Got 'text/html;level=2'
```

```php
// $_SERVER['Accept'] == 'text/html; level=2'
$type = HTTPMimeType::negotiateAcceptType(["text/html"], false);
// Got false
```

### Negotiate ContentType

```php
// $_SERVER['CONTENT_TYPE'] == 'application/json'
$type = HTTPMimeType::negotiateContentType(["application/x-www-form-urlencoded", "application/json"]);
// Got 'application/json'
```

```php
// $_SERVER['CONTENT_TYPE'] == 'text/html; charset=Shift_Jis'
$type = HTTPMimeType::negotiateContentType(["text/html", "text/plain"]);
// Got 'text/html'
```