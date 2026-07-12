# DevElation Codespaces

This dev container prepares a PHP 8.2 environment for running DevElation examples.

Useful commands:

```bash
composer test
php examples/helpers/workflow.php
php examples/http/api_packet.php
php examples/cli/report.php --limit 3 --delay 0 --title "Codespaces Demo"
php examples/game/gangs.php script
php -S 0.0.0.0:8080
```

After starting the PHP server, open the forwarded port and browse to:

- `/examples/todo/index.php`
- `/examples/comments/index.php`
